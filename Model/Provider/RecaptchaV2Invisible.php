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
 * Google reCAPTCHA v2 — invisible badge (executed programmatically on submit).
 */
class RecaptchaV2Invisible extends AbstractProvider
{
    protected const DEFAULT_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    protected const DEFAULT_TOKEN_TTL_MS = 110000;

    public function getCode(): string
    {
        return self::CODE_RECAPTCHA_V2_INVISIBLE;
    }

    public function getLabel(): Phrase
    {
        return __('Google reCAPTCHA v2 (invisible badge)');
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
            'size' => 'invisible',
            'badge' => $this->providerConfig->getWidgetOption($this->getCode(), 'badge', $scopeCode) ?: 'bottomright',
            'widgetMode' => 'explicit',
        ];
    }
}
