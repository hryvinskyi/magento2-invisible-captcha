<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

use Magento\Framework\App\RequestInterface;

/**
 * Detects XHR / background API requests — the ones that must not receive an
 * HTML challenge body. Shared by the route gate (response shape) and the
 * `is_ajax` rule field (matching), so both apply identical semantics.
 */
interface AjaxRequestDetectorInterface
{
    /**
     * Whether the request is an AJAX / background call: an XMLHttpRequest,
     * a request flagged via Magento's ajax markers, or one that accepts a
     * JSON response.
     *
     * @param RequestInterface $request
     * @return bool
     */
    public function isAjax(RequestInterface $request): bool;
}
