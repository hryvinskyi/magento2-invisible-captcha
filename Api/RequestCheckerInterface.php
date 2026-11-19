<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Decides whether the current request must pass the route-level challenge.
 *
 * The client address the decision reads comes from
 * {@see \Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface};
 * consumers that need the address themselves ask that contract directly.
 */
interface RequestCheckerInterface
{
    /**
     * Whether route-level protection is enabled and its provider configured
     * for the current scope.
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Whether the current request must pass the challenge: protection is
     * configured, no bypass (excluded IP, exempt crawler, excluded path)
     * applies, and the protection rules match.
     *
     * @return bool
     */
    public function needsChallenge(): bool;
}
