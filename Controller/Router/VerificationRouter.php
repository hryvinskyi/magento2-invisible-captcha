<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Controller\Router;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

/**
 * Intercepts the captcha verify endpoint before regular routing so the matched
 * action can mutate the HTTP context inline (keeping X-Magento-Vary correct on
 * the verify response).
 */
class VerificationRouter implements RouterInterface
{
    public const VERIFY_PATH = 'invisiblecaptcha/verify';

    /**
     * @param Verify $verifyAction
     */
    public function __construct(
        private readonly Verify $verifyAction
    ) {
    }

    /**
     * @inheritDoc
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        if (trim((string)$request->getPathInfo(), '/') !== self::VERIFY_PATH) {
            return null;
        }

        if ($request instanceof HttpRequest && strcasecmp($request->getMethod(), 'POST') !== 0) {
            return null;
        }

        $request->setModuleName('invisiblecaptcha')
            ->setControllerName('verification')
            ->setActionName('verify')
            ->setDispatched(true);

        return $this->verifyAction;
    }
}
