<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Controller\Router;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Hryvinskyi\InvisibleCaptcha\Model\Http\CaptchaContext;
use Hryvinskyi\InvisibleCaptcha\Model\RefIdGenerator;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * Verify action handed back by {@see VerificationRouter}. Verifies the submitted
 * token against the route-gate provider (or its fallback), issues the HMAC cookie
 * and marks the HTTP context verified inline.
 */
class Verify implements ActionInterface, CsrfAwareActionInterface
{
    /**
     * @param ConfigInterface $config
     * @param ProviderPoolInterface $providerPool
     * @param CookieManager $cookieManager
     * @param RequestChecker $requestChecker
     * @param CaptchaContext $captchaContext
     * @param RefIdGenerator $refIdGenerator
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ProviderPoolInterface $providerPool,
        private readonly CookieManager $cookieManager,
        private readonly RequestChecker $requestChecker,
        private readonly CaptchaContext $captchaContext,
        private readonly RefIdGenerator $refIdGenerator,
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResponseInterface|Json
    {
        /** @var Json $result */
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setHeader('Pragma', 'no-cache', true);

        $refId = $this->extractRefId();
        $clientIp = $this->requestChecker->getClientIp();

        if (!$this->requestChecker->isConfigured()) {
            $this->logResult($refId, 'not_configured', $clientIp);

            return $result->setHttpResponseCode(503)
                ->setData(['success' => false, 'message' => __('Not configured')]);
        }

        $primary = $this->providerPool->getRouteGateProvider();
        $fallback = $this->providerPool->getFallbackProvider();

        [$passed, $providerCode] = $this->attempt($primary, $clientIp);

        if ($providerCode === '' && $fallback !== null && $fallback->getCode() !== $primary->getCode()) {
            [$passed, $providerCode] = $this->attempt($fallback, $clientIp);
        }

        if ($providerCode === '') {
            $this->logResult($refId, 'fail:no_token', $clientIp);

            return $result->setData([
                'success' => false,
                'message' => __('Verification failed. Please try again.'),
            ]);
        }

        if (!$passed) {
            $this->logResult($refId, 'fail:' . $providerCode, $clientIp);

            return $result->setData([
                'success' => false,
                'message' => __('Verification failed. Please try again.'),
            ]);
        }

        $this->cookieManager->setVerified();
        $this->captchaContext->markVerified();
        $this->logResult($refId, 'pass:' . $providerCode, $clientIp);

        return $result->setData(['success' => true]);
    }

    /**
     * Attempt verification for a provider if its token is present.
     *
     * @return array{0:bool,1:string} [passed, providerCode|''] — providerCode is '' when no token was submitted.
     */
    private function attempt(ProviderInterface $provider, string $clientIp): array
    {
        $token = (string)$this->request->getParam($provider->getResponseParamName(), '');
        if ($token === '') {
            return [false, ''];
        }

        $request = $provider->createVerificationRequest()
            ->setResponse($token)
            ->setRemoteIp($clientIp !== '' ? $clientIp : null);

        $verification = $provider->getVerifier()->verify($request);

        return [$verification->isSuccess(), $provider->getCode()];
    }

    /**
     * Read the correlation token submitted with the verify POST.
     */
    private function extractRefId(): string
    {
        $raw = trim((string)$this->request->getParam('ref', ''));
        if ($raw === '' || !$this->refIdGenerator->isValid($raw)) {
            return '-';
        }

        return $raw;
    }

    /**
     * Record the verify outcome alongside the ref for log correlation.
     */
    private function logResult(string $refId, string $resultLabel, string $clientIp): void
    {
        if (!$this->config->isDebug()) {
            return;
        }

        $this->logger->info(sprintf(
            '[InvisibleCaptcha] verify | ref=%s | result=%s | ip=%s',
            $refId,
            $resultLabel,
            $clientIp
        ));
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
