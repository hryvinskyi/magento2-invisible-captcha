<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Provider;

/**
 * Per-provider credential & widget-option accessor. Reads
 * `hryvinskyi_invisible_captcha/providers/<code>/*` and decrypts secrets.
 */
interface ProviderConfigInterface
{
    /**
     * Public site key for a provider.
     */
    public function getSiteKey(string $providerCode, ?string $scopeCode = null): string;

    /**
     * Secret key (decrypted). For Enterprise this is the Google API key.
     */
    public function getSecretKey(string $providerCode, ?string $scopeCode = null): string;

    /**
     * Google Cloud project id (Enterprise only).
     */
    public function getProjectId(string $providerCode, ?string $scopeCode = null): string;

    /**
     * Provider-specific widget option (e.g. theme, size, appearance, badge),
     * or null when unset.
     */
    public function getWidgetOption(string $providerCode, string $key, ?string $scopeCode = null): ?string;
}
