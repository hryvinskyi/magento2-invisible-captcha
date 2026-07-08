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
 * Verifier for reCAPTCHA Enterprise, which uses the
 * `projects/{projectId}/assessments` REST API (JSON body + API key) rather than
 * the form-encoded siteverify contract.
 *
 * The provider prepares the request: secret = API key, verifyUrl = assessments
 * endpoint (already carrying ?key=API_KEY), and extra[siteKey, projectId].
 */
class EnterpriseVerifier implements VerifierInterface
{
    private const LOG_LABEL = 'Enterprise';

    /**
     * @param HttpClientInterface $httpClient
     * @param ValidatorList $validatorList
     * @param Json $json
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ValidatorList $validatorList,
        private readonly Json $json,
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger
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

        $siteKey = (string)$request->getExtraValue('siteKey', '');
        if ($request->getSecret() === '' || $siteKey === '') {
            return VerificationResult::failure(['missing-input-secret']);
        }

        $event = ['token' => $request->getResponse(), 'siteKey' => $siteKey];
        if ($request->getExpectedAction()) {
            $event['expectedAction'] = $request->getExpectedAction();
        }
        if ($request->getRemoteIp()) {
            $event['userIpAddress'] = $request->getRemoteIp();
        }

        try {
            $raw = $this->httpClient->post(
                $request->getVerifyUrl(),
                $this->json->serialize(['event' => $event]),
                ['Content-Type' => 'application/json']
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

        $tokenProperties = (array)($payload['tokenProperties'] ?? []);
        $riskAnalysis = (array)($payload['riskAnalysis'] ?? []);

        $valid = (bool)($tokenProperties['valid'] ?? false);
        $errorCodes = [];
        if (!$valid) {
            $reason = (string)($tokenProperties['invalidReason'] ?? 'invalid-input-response');
            $errorCodes[] = $this->mapInvalidReason($reason);
        }

        $result = new VerificationResult(
            $valid,
            $errorCodes,
            isset($tokenProperties['hostname']) ? (string)$tokenProperties['hostname'] : null,
            isset($tokenProperties['createTime']) ? (string)$tokenProperties['createTime'] : null,
            isset($riskAnalysis['score']) ? (float)$riskAnalysis['score'] : null,
            isset($tokenProperties['action']) ? (string)$tokenProperties['action'] : null
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
     * Map a reCAPTCHA Enterprise invalidReason to a module error code.
     */
    private function mapInvalidReason(string $reason): string
    {
        return match ($reason) {
            'EXPIRED', 'DUPE' => 'timeout-or-duplicate',
            'MALFORMED', 'INVALID_REASON_UNSPECIFIED' => 'invalid-input-response',
            'MISSING' => 'missing-input-response',
            'BROWSER_ERROR' => 'bad-request',
            default => 'invalid-input-response',
        };
    }

    /**
     * Write a debug entry when debug mode is enabled.
     */
    private function log(string $message): void
    {
        if ($this->config->isDebug()) {
            $this->logger->info(sprintf('[InvisibleCaptcha][%s] %s', self::LOG_LABEL, $message));
        }
    }
}
