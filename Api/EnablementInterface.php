<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Decides whether captcha verification is active for a specific protected form
 * (module enabled AND form-protection enabled AND area enabled AND form flag).
 */
interface EnablementInterface
{
    public function isEnabled(?string $scopeCode = null): bool;
}
