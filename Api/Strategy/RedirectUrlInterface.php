<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Strategy;

/**
 * Resolves the URL a redirect-style failure strategy should send the visitor to.
 */
interface RedirectUrlInterface
{
    public function getRedirectUrl(): string;
}
