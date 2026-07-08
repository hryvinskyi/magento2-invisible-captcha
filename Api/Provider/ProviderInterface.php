<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerifierInterface;
use Magento\Framework\Phrase;

/**
 * A captcha vendor/variant: reCAPTCHA v2 checkbox, v2 invisible, v3, Enterprise
 * or Cloudflare Turnstile. Encapsulates everything provider-specific so the
 * form-level and route-level subsystems stay provider-agnostic.
 */
interface ProviderInterface
{
    public const CODE_RECAPTCHA_V2_CHECKBOX = 'recaptcha_v2_checkbox';
    public const CODE_RECAPTCHA_V2_INVISIBLE = 'recaptcha_v2_invisible';
    public const CODE_RECAPTCHA_V3 = 'recaptcha_v3';
    public const CODE_RECAPTCHA_ENTERPRISE = 'recaptcha_enterprise';
    public const CODE_TURNSTILE = 'turnstile';

    /**
     * Stable machine code (one of self::CODE_*).
     */
    public function getCode(): string;

    /**
     * Human-readable admin label.
     */
    public function getLabel(): Phrase;

    /**
     * Whether the provider returns a risk score that should be threshold-checked
     * (reCAPTCHA v3 and Enterprise). v2 / Turnstile return pass/fail only.
     */
    public function isScoreBased(): bool;

    /**
     * Whether the provider supports / requires a per-form "action" name.
     */
    public function supportsAction(): bool;

    /**
     * The native response parameter the vendor widget emits
     * (g-recaptcha-response / cf-turnstile-response).
     */
    public function getResponseParamName(): string;

    /**
     * Public site key for the configured scope.
     */
    public function getSiteKey(?string $scopeCode = null): string;

    /**
     * Secret / API key for the configured scope (decrypted).
     */
    public function getSecretKey(?string $scopeCode = null): string;

    /**
     * Whether site & secret keys are present for the configured scope.
     */
    public function isConfigured(?string $scopeCode = null): bool;

    /**
     * Server-side siteverify / assessment endpoint URL.
     */
    public function getVerifyUrl(?string $scopeCode = null): string;

    /**
     * Browser API script URL (api.js / enterprise.js / turnstile api.js).
     */
    public function getClientScriptUrl(?string $scopeCode = null): string;

    /**
     * The verifier used to validate this provider's tokens server-side.
     */
    public function getVerifier(): VerifierInterface;

    /**
     * Client-side render configuration consumed by the JS provider strategy.
     *
     * @param array<string, mixed> $context Optional per-call context (e.g. ['action' => '...']).
     * @return array<string, mixed>
     */
    public function getRenderConfig(?string $scopeCode = null, array $context = []): array;

    /**
     * Recommended client token refresh interval, in milliseconds.
     */
    public function getTokenTtlMs(): int;

    /**
     * Build a verification request for this provider, prefilled with secret,
     * verify URL and any provider-specific extras.
     */
    public function createVerificationRequest(?string $scopeCode = null): \Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
}
