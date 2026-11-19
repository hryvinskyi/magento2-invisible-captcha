<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Observer;

use Hryvinskyi\InvisibleCaptcha\Api\AjaxRequestDetectorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ChallengeRendererInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RefIdGeneratorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RequestCheckerInterface;
use Hryvinskyi\InvisibleCaptcha\Api\VerificationCookieInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;
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
     * @param VerificationCookieInterface $cookieManager
     * @param RequestCheckerInterface $requestChecker
     * @param ClientIpResolverInterface $clientIpResolver
     * @param ChallengeRendererInterface $challengeRenderer
     * @param RefIdGeneratorInterface $refIdGenerator
     * @param ActionFlag $actionFlag
     * @param HttpResponse $response
     * @param RequestInterface $request
     * @param AjaxRequestDetectorInterface $ajaxRequestDetector
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly VerificationCookieInterface $cookieManager,
        private readonly RequestCheckerInterface $requestChecker,
        private readonly ClientIpResolverInterface $clientIpResolver,
        private readonly ChallengeRendererInterface $challengeRenderer,
        private readonly RefIdGeneratorInterface $refIdGenerator,
        private readonly ActionFlag $actionFlag,
        private readonly HttpResponse $response,
        private readonly RequestInterface $request,
        private readonly AjaxRequestDetectorInterface $ajaxRequestDetector,
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

        // ActionFlag declares a string value and the front controller reads it as a boolean.
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, '1');

        $refId = $this->refIdGenerator->generate();
        $mode = $this->ajaxRequestDetector->isAjax($this->request) ? 'ajax' : 'inline';
        $this->logIssued($refId, $mode);
        $this->emitInlineChallenge($refId, $mode === 'ajax');
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
            $this->request instanceof HttpRequest ? $this->request->getRequestUri() : '',
            $this->clientIpResolver->resolve(),
            substr($this->userAgent(), 0, self::USER_AGENT_LOG_LIMIT)
        ));
    }

    /**
     * The request's User-Agent; empty when it has none.
     */
    private function userAgent(): string
    {
        if (!$this->request instanceof HttpRequest) {
            return '';
        }

        $userAgent = $this->request->getHeader('User-Agent');

        return is_string($userAgent) ? $userAgent : '';
    }
}
