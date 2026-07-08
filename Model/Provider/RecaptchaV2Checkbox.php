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
 * Google reCAPTCHA v2 — "I'm not a robot" checkbox.
 */
class RecaptchaV2Checkbox extends AbstractProvider
{
    protected const DEFAULT_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    protected const DEFAULT_TOKEN_TTL_MS = 110000;

    public function getCode(): string
    {
        return self::CODE_RECAPTCHA_V2_CHECKBOX;
    }

    public function getLabel(): Phrase
    {
        return __('Google reCAPTCHA v2 ("I\'m not a robot" checkbox)');
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
        return 'g-recaptcha-response';
    }

    public function getClientScriptUrl(?string $scopeCode = null): string
    {
        return 'https://www.google.com/recaptcha/api.js';
    }

    public function getRenderConfig(?string $scopeCode = null, array $context = []): array
    {
        return $this->baseRenderConfig($scopeCode, $context) + [
            'theme' => $this->providerConfig->getWidgetOption($this->getCode(), 'theme', $scopeCode) ?: 'light',
            'size' => $this->providerConfig->getWidgetOption($this->getCode(), 'size', $scopeCode) ?: 'normal',
            'widgetMode' => 'explicit',
        ];
    }
}
