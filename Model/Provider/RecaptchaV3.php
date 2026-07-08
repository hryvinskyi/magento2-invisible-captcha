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
 * Google reCAPTCHA v3 — invisible, score-based with per-action verification.
 */
class RecaptchaV3 extends AbstractProvider
{
    protected const DEFAULT_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    protected const DEFAULT_TOKEN_TTL_MS = 90000;

    public function getCode(): string
    {
        return self::CODE_RECAPTCHA_V3;
    }

    public function getLabel(): Phrase
    {
        return __('Google reCAPTCHA v3 (invisible, score-based)');
    }

    public function isScoreBased(): bool
    {
        return true;
    }

    public function supportsAction(): bool
    {
        return true;
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
        $hideBadge = $this->providerConfig->getWidgetOption($this->getCode(), 'hide_badge', $scopeCode) === '1';

        return $this->baseRenderConfig($scopeCode, $context) + [
            'badge' => 'bottomright',
            'hideBadge' => $hideBadge,
            'hideBadgeText' => (string)($this->providerConfig->getWidgetOption($this->getCode(), 'hide_badge_text', $scopeCode) ?? ''),
            'widgetMode' => 'score',
        ];
    }
}
