<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Verification\Verifier;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\HttpClientInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerifierInterface;
use Hryvinskyi\InvisibleCaptcha\Exception\HttpClientException;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\ValidatorList;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationResult;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Verifier for the form-encoded "siteverify" contract shared by Google
 * reCAPTCHA v2 (checkbox/invisible), v3 and Cloudflare Turnstile:
 * POST `secret` + `response` (+ optional `remoteip`) → JSON
 * `{ success, error-codes, hostname?, challenge_ts?, score?, action? }`.
 *
 * After parsing, a self-gating validator list applies host / action / score /
 * timeout checks (those that aren't applicable for a given provider no-op).
 * Fails closed on any transport or decode error.
 */
class SiteVerifyVerifier implements VerifierInterface
{
    private const LOG_LABEL = 'SiteVerify';

    /**
     * @param HttpClientInterface $httpClient
     * @param ValidatorList $validatorList
     * @param Json $json
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param string $logLabel Provider label used in debug log lines.
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ValidatorList $validatorList,
        private readonly Json $json,
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger,
        private readonly string $logLabel = self::LOG_LABEL
    ) {
    }

    /**
     * @inheritDoc
     */
    public function verify(VerificationRequestInterface $request): VerificationResultInterface
    {
        if ($request->getResponse() === '') {
            return VerificationResult::failure(['missing-input-response']);
        }

        if ($request->getSecret() === '') {
            return VerificationResult::failure(['missing-input-secret']);
        }

        $params = [
            'secret' => $request->getSecret(),
            'response' => $request->getResponse(),
        ];
        if ($request->getRemoteIp()) {
            $params['remoteip'] = $request->getRemoteIp();
        }

        try {
            $raw = $this->httpClient->post(
                $request->getVerifyUrl(),
                http_build_query($params),
                ['Content-Type' => 'application/x-www-form-urlencoded']
            );
        } catch (HttpClientException $e) {
            $this->log('Transport error: ' . $e->getMessage());

            return VerificationResult::failure(['connection-failed']);
        }

        try {
            $payload = $this->json->unserialize($raw);
        } catch (\InvalidArgumentException $e) {
            return VerificationResult::failure(['invalid-json']);
        }

        if (!is_array($payload)) {
            return VerificationResult::failure(['invalid-json']);
        }

        $result = new VerificationResult(
            (bool)($payload['success'] ?? false),
            array_values((array)($payload['error-codes'] ?? [])),
            isset($payload['hostname']) ? (string)$payload['hostname'] : null,
            isset($payload['challenge_ts']) ? (string)$payload['challenge_ts'] : null,
            isset($payload['score']) ? (float)$payload['score'] : null,
            isset($payload['action']) ? (string)$payload['action'] : null
        );

        return $this->applyValidators($request, $result);
    }

    /**
     * Run the validator list and fold any failures into the result.
     */
    private function applyValidators(
        VerificationRequestInterface $request,
        VerificationResultInterface $result
    ): VerificationResultInterface {
        $errors = [];
        foreach ($this->validatorList->getList() as $validator) {
            $error = $validator->validate($request, $result);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        if ($errors === []) {
            $this->log(sprintf('%s | score=%s | action=%s', 'PASS=' . ($result->isSuccess() ? '1' : '0'), (string)$result->getScore(), (string)$result->getAction()));

            return $result;
        }

        return new VerificationResult(
            false,
            array_values(array_unique(array_merge($result->getErrorCodes(), $errors))),
            $result->getHostname(),
            $result->getChallengeTs(),
            $result->getScore(),
            $result->getAction()
        );
    }

    /**
     * Write a debug entry when debug mode is enabled.
     */
    private function log(string $message): void
    {
        if ($this->config->isDebug()) {
            $this->logger->info(sprintf('[InvisibleCaptcha][%s] %s', $this->logLabel, $message));
        }
    }
}
