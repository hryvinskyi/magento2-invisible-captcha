<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\AjaxRequestDetectorInterface;
use Magento\Framework\App\RequestInterface;

class AjaxRequestDetector implements AjaxRequestDetectorInterface
{
    /**
     * @inheritDoc
     */
    public function isAjax(RequestInterface $request): bool
    {
        if (method_exists($request, 'isXmlHttpRequest') && $request->isXmlHttpRequest()) {
            return true;
        }

        if (method_exists($request, 'isAjax') && $request->isAjax()) {
            return true;
        }

        $accept = (string)$request->getHeader('Accept');
        if ($accept !== '' && str_contains($accept, 'application/json')) {
            return true;
        }

        return strcasecmp((string)$request->getHeader('X-Requested-With'), 'XMLHttpRequest') === 0;
    }
}
