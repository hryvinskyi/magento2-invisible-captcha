<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Magento\Framework\Phrase;

/**
 * Cloudflare Turnstile — privacy-friendly, pass/fail managed challenge.
 */
class Turnstile extends AbstractProvider
{
    protected const DEFAULT_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    protected const DEFAULT_TOKEN_TTL_MS = 250000;

    public function getCode(): string
    {
        return self::CODE_TURNSTILE;
    }

    public function getLabel(): Phrase
    {
        return __('Cloudflare Turnstile');
    }

    public function isScoreBased(): bool
    {
        return false;
    }

    public function supportsAction(): bool
    {
        return false;
    }

    public function getResponseParamName(): string
    {
        return 'cf-turnstile-response';
    }

    public function getClientScriptUrl(?string $scopeCode = null): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }

    public function getRenderConfig(?string $scopeCode = null, array $context = []): array
    {
        return $this->baseRenderConfig($scopeCode, $context) + [
            'size' => $this->providerConfig->getWidgetOption($this->getCode(), 'widget_size', $scopeCode) ?: 'flexible',
            'appearance' => $this->providerConfig->getWidgetOption($this->getCode(), 'widget_appearance', $scopeCode)
                ?: 'interaction-only',
            'widgetMode' => 'explicit',
        ];
    }
}
