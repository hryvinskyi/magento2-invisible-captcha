<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Provider;

use Hryvinskyi\InvisibleCaptcha\Exception\ProviderNotFoundException;

/**
 * Registry of available providers and resolver for the active / route-gate /
 * fallback providers. The provider set is the module's primary extension seam
 * (DI array keyed by provider code).
 */
interface ProviderPoolInterface
{
    /**
     * @return ProviderInterface[] Keyed by provider code.
     */
    public function getAll(): array;

    /**
     * @throws ProviderNotFoundException When the code is unknown.
     */
    public function get(string $code): ProviderInterface;

    /**
     * Whether a provider with the given code is registered.
     */
    public function has(string $code): bool;

    /**
     * The provider selected for form-level protection / default rendering.
     */
    public function getActive(?string $scopeCode = null): ProviderInterface;

    /**
     * The provider used for the route-level challenge page (may override active).
     */
    public function getRouteGateProvider(?string $scopeCode = null): ProviderInterface;

    /**
     * Optional secondary provider offered on the challenge page, or null.
     */
    public function getFallbackProvider(?string $scopeCode = null): ?ProviderInterface;
}
