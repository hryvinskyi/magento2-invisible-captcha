<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Observer;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ChallengeRenderer;
use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Hryvinskyi\InvisibleCaptcha\Model\RefIdGenerator;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Route-level gate: on a matched, unverified request it replaces the response
 * with the inline captcha challenge before the controller dispatches.
 */
class RouteGate implements ObserverInterface
{
    private const USER_AGENT_LOG_LIMIT = 200;
    private const CHALLENGE_HEADER = 'X-InvisibleCaptcha-Challenge';

    /**
     * @param ConfigInterface $config
     * @param CookieManager $cookieManager
     * @param RequestChecker $requestChecker
     * @param ChallengeRenderer $challengeRenderer
     * @param RefIdGenerator $refIdGenerator
     * @param ActionFlag $actionFlag
     * @param HttpResponse $response
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly CookieManager $cookieManager,
        private readonly RequestChecker $requestChecker,
        private readonly ChallengeRenderer $challengeRenderer,
        private readonly RefIdGenerator $refIdGenerator,
        private readonly ActionFlag $actionFlag,
        private readonly HttpResponse $response,
        private readonly RequestInterface $request,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        if (!$this->requestChecker->needsChallenge()) {
            return;
        }

        if ($this->cookieManager->isVerified()) {
            return;
        }

        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

        $refId = $this->refIdGenerator->generate();
        $mode = $this->isAjaxRequest() ? 'ajax' : 'inline';
        $this->logIssued($refId, $mode);
        $this->emitInlineChallenge($refId, $mode === 'ajax');
    }

    /**
     * Detect XHR / API requests that must not receive an HTML challenge body.
     */
    private function isAjaxRequest(): bool
    {
        if (method_exists($this->request, 'isXmlHttpRequest') && $this->request->isXmlHttpRequest()) {
            return true;
        }

        if (method_exists($this->request, 'isAjax') && $this->request->isAjax()) {
            return true;
        }

        $accept = (string)$this->request->getHeader('Accept');
        if ($accept !== '' && str_contains($accept, 'application/json')) {
            return true;
        }

        return strcasecmp((string)$this->request->getHeader('X-Requested-With'), 'XMLHttpRequest') === 0;
    }

    /**
     * Emit the inline-challenge HTML in place of the requested page.
     *
     * @throws LocalizedException
     */
    private function emitInlineChallenge(string $refId, bool $isAjax): void
    {
        $this->response->setHttpResponseCode(403);
        $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
        $this->response->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        if ($isAjax) {
            $this->response->setHeader(self::CHALLENGE_HEADER, '1', true);
        }
        $this->applyNoStoreHeaders();
        $this->response->setBody($this->challengeRenderer->render($refId));
    }

    /**
     * Apply the cache-busting header set required at every edge tier.
     */
    private function applyNoStoreHeaders(): void
    {
        $this->response->setHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0', true);
        $this->response->setHeader('Pragma', 'no-cache', true);
        $this->response->setHeader('Expires', '0', true);
        $this->response->setHeader('X-Accel-Expires', '0', true);
    }

    /**
     * Record that a challenge was emitted so verify-time logs can be correlated.
     */
    private function logIssued(string $refId, string $mode): void
    {
        if (!$this->config->isDebug()) {
            return;
        }

        $this->logger->info(sprintf(
            '[InvisibleCaptcha] challenge issued | ref=%s | mode=%s | url=%s | ip=%s | ua=%s',
            $refId,
            $mode,
            (string)$this->request->getRequestUri(),
            $this->requestChecker->getClientIp(),
            substr((string)$this->request->getHeader('User-Agent'), 0, self::USER_AGENT_LOG_LIMIT)
        ));
    }
}
