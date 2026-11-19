<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Observer;

use Hryvinskyi\InvisibleCaptcha\Api\Http\CaptchaContextInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RequestCheckerInterface;
use Hryvinskyi\InvisibleCaptcha\Controller\Router\VerificationRouter;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Pushes route-gate verification state into the HTTP context so it influences
 * the X-Magento-Vary cookie used by Varnish to key the cache. Skips the verify
 * endpoint, whose action manages its own context inline.
 */
class SetCaptchaHttpContext implements ObserverInterface
{
    /**
     * @param RequestCheckerInterface $requestChecker
     * @param CaptchaContextInterface $captchaContext
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestCheckerInterface $requestChecker,
        private readonly CaptchaContextInterface $captchaContext,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->requestChecker->isConfigured()) {
            return;
        }

        if ($this->isVerifyEndpoint()) {
            return;
        }

        $this->captchaContext->setFromCookie();
    }

    /**
     * Whether the current request targets the captcha verify endpoint.
     */
    private function isVerifyEndpoint(): bool
    {
        if (!$this->request instanceof HttpRequest) {
            return false;
        }

        return trim($this->request->getPathInfo(), '/') === VerificationRouter::VERIFY_PATH;
    }
}
