<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Strategy;

/**
 * Extracts the captcha token from the inbound request. Implementations differ
 * by transport (standard request param vs JSON body) and read a configurable
 * field name (the neutral wrapper field, default "hryvinskyi_invisible_token").
 */
interface TokenStrategyInterface
{
    public function getToken(): ?string;
}
