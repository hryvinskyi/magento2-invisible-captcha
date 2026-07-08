<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Observer;

use Hryvinskyi\InvisibleCaptcha\Controller\Router\VerificationRouter;
use Hryvinskyi\InvisibleCaptcha\Model\Http\CaptchaContext;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
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
     * @param RequestChecker $requestChecker
     * @param CaptchaContext $captchaContext
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestChecker $requestChecker,
        private readonly CaptchaContext $captchaContext,
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

        $path = trim((string)$this->request->getPathInfo(), '/');
        if ($path === VerificationRouter::VERIFY_PATH) {
            return;
        }

        $this->captchaContext->setFromCookie();
    }
}
