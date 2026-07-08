<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Unified configuration contract for the merged captcha module.
 *
 * Covers three concerns:
 *  - general module settings (master switch, active provider, debug, lazy load),
 *  - form-level protection toggles & score thresholds (capability A),
 *  - route-level gating settings & rule engine inputs (capability B).
 *
 * All accessors resolve against the store scope and fall back through
 * website/default automatically.
 */
interface ConfigInterface
{
    /** Form identifiers used by {@see self::isFormEnabled()} / {@see self::getFormScoreThreshold()}. */
    public const FORM_CUSTOMER_LOGIN = 'customer_login';
    public const FORM_CUSTOMER_CREATE = 'customer_create';
    public const FORM_CUSTOMER_FORGOT = 'customer_forgot';
    public const FORM_CUSTOMER_EDIT = 'customer_edit';
    public const FORM_CONTACT = 'contact';
    public const FORM_NEWSLETTER = 'newsletter';
    public const FORM_SEND_FRIEND = 'send_friend';
    public const FORM_PRODUCT_REVIEW = 'product_review';
    public const FORM_WISHLIST = 'wishlist';
    public const FORM_COUPON_CODE = 'coupon_code';
    public const FORM_PLACE_ORDER = 'place_order';
    public const FORM_STORE_PICKUP = 'store_pickup';
    public const FORM_PAYPAL_PAYFLOWPRO = 'paypal_payflowpro';
    public const FORM_RESEND_CONFIRMATION_EMAIL = 'resend_confirmation_email';
    public const FORM_ADMIN_LOGIN = 'admin_login';
    public const FORM_ADMIN_FORGOT = 'admin_forgot';

    /** Form areas. */
    public const AREA_FRONTEND = 'frontend';
    public const AREA_ADMINHTML = 'adminhtml';

    /* ---------------------------------------------------------------- General */

    /**
     * Master on/off switch for the whole module.
     */
    public function isEnabled(?string $scopeCode = null): bool;

    /**
     * Provider code selected as the default/active provider (see ProviderInterface::CODE_*).
     */
    public function getActiveProvider(?string $scopeCode = null): string;

    /**
     * Whether debug logging is enabled.
     */
    public function isDebug(?string $scopeCode = null): bool;

    /**
     * Whether the client should lazily load the provider API script.
     */
    public function isLazyLoad(?string $scopeCode = null): bool;

    /**
     * Whether form submit buttons should be disabled until the captcha token is ready.
     */
    public function isDisableSubmitForm(?string $scopeCode = null): bool;

    /* ------------------------------------------------------- Form protection */

    /**
     * Whether form-level protection (capability A) is enabled at all.
     */
    public function isFormProtectionEnabled(?string $scopeCode = null): bool;

    /**
     * Whether form-level protection is enabled for the given area ('frontend'|'adminhtml').
     */
    public function isFormAreaEnabled(string $area, ?string $scopeCode = null): bool;

    /**
     * Whether protection is enabled for a specific form (see self::FORM_* constants).
     */
    public function isFormEnabled(string $form, ?string $scopeCode = null): bool;

    /**
     * Per-form reCAPTCHA score threshold (only meaningful for score-based providers).
     */
    public function getFormScoreThreshold(string $form, ?string $scopeCode = null): float;

    /* ------------------------------------------------------ Route protection */

    /**
     * Whether route-level gating (capability B) is enabled.
     */
    public function isRouteProtectionEnabled(?string $scopeCode = null): bool;

    /**
     * Provider code that overrides the active provider for the route-gate
     * challenge page; empty string means "use the active provider".
     */
    public function getRouteProviderOverride(?string $scopeCode = null): string;

    /**
     * Whether a secondary (fallback) provider is offered on the challenge page.
     */
    public function isRouteFallbackEnabled(?string $scopeCode = null): bool;

    /**
     * Provider code used as the challenge-page fallback.
     */
    public function getRouteFallbackProvider(?string $scopeCode = null): string;

    /**
     * Seconds the challenge page waits before revealing the fallback widget.
     */
    public function getRouteFallbackDelay(?string $scopeCode = null): int;

    /**
     * Verified-cookie lifetime in seconds.
     */
    public function getCookieLifetime(?string $scopeCode = null): int;

    /**
     * Decoded protection-rules expression (Cloudflare-style condition list).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProtectionRulesConfig(?string $scopeCode = null): array;

    /**
     * Param names excluded from the "active param count" filter field.
     *
     * @return string[]
     */
    public function getLayeredNavIgnoredParams(?string $scopeCode = null): array;

    /**
     * IPs that bypass route-level gating.
     *
     * @return string[]
     */
    public function getExcludedIps(?string $scopeCode = null): array;

    /**
     * User-agent substrings that bypass route-level gating.
     *
     * @return string[]
     */
    public function getExcludedUserAgents(?string $scopeCode = null): array;

    /**
     * Param names stripped from the post-verify navigation URL.
     *
     * @return string[]
     */
    public function getAjaxMarkerParams(?string $scopeCode = null): array;

    /**
     * Param names marking a background (non-user-initiated) AJAX preload.
     *
     * @return string[]
     */
    public function getBackgroundAjaxMarkerParams(?string $scopeCode = null): array;

    /**
     * CSS selector for the layered-navigation anchor captured before challenge.
     */
    public function getFilterAnchorSelector(?string $scopeCode = null): string;

    /**
     * JS regex used to capture attribute names from AJAX query params.
     */
    public function getFilterParamPattern(?string $scopeCode = null): string;

    /**
     * Store support email shown on the challenge failure screen.
     */
    public function getSupportEmail(?string $scopeCode = null): string;

    /* ----------------------------------------------------------- Appearance */

    /**
     * Accent color for the route-protection challenge page (`--primary`).
     * Empty string when unset — callers fall back to the bundled default.
     */
    public function getChallengePrimaryColor(?string $scopeCode = null): string;

    /**
     * Darker accent shade for hover/active states (`--primary-deep`).
     */
    public function getChallengePrimaryColorDeep(?string $scopeCode = null): string;

    /**
     * Translucent accent tint for glow/pulse effects (`--primary-soft`).
     */
    public function getChallengePrimaryColorSoft(?string $scopeCode = null): string;

    /* ------------------------------------------------------------- Advanced */

    /**
     * Wall-clock budget (seconds) for an outbound siteverify request.
     */
    public function getHttpTimeout(?string $scopeCode = null): float;
}
