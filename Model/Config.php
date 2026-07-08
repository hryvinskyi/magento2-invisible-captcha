<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

/**
 * Unified configuration reader for the merged captcha module.
 */
class Config implements ConfigInterface
{
    /* General */
    private const XML_ENABLED = 'hryvinskyi_invisible_captcha/general/enabled';
    private const XML_ACTIVE_PROVIDER = 'hryvinskyi_invisible_captcha/general/active_provider';
    private const XML_DEBUG = 'hryvinskyi_invisible_captcha/general/debug';
    private const XML_LAZY_LOAD = 'hryvinskyi_invisible_captcha/general/use_lazy_load';
    private const XML_DISABLE_SUBMIT = 'hryvinskyi_invisible_captcha/general/disable_submit_form';

    /* Form protection */
    private const XML_FORM_ENABLED = 'hryvinskyi_invisible_captcha/form_protection/enabled';
    private const XML_FORM_FRONTEND_ENABLED = 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled';
    private const XML_FORM_BACKEND_ENABLED = 'hryvinskyi_invisible_captcha/form_protection/backend/enabled';
    private const XML_FORM_PREFIX = 'hryvinskyi_invisible_captcha/form_protection/';

    /* Route protection */
    private const XML_ROUTE_ENABLED = 'hryvinskyi_invisible_captcha/route_protection/enabled';
    private const XML_ROUTE_PROVIDER_OVERRIDE = 'hryvinskyi_invisible_captcha/route_protection/provider_override';
    private const XML_ROUTE_FALLBACK_ENABLED = 'hryvinskyi_invisible_captcha/route_protection/fallback_enabled';
    private const XML_ROUTE_FALLBACK_PROVIDER = 'hryvinskyi_invisible_captcha/route_protection/fallback_provider';
    private const XML_ROUTE_FALLBACK_DELAY = 'hryvinskyi_invisible_captcha/route_protection/fallback_delay';
    private const XML_ROUTE_COOKIE_LIFETIME = 'hryvinskyi_invisible_captcha/route_protection/cookie_lifetime';
    private const XML_ROUTE_RULES = 'hryvinskyi_invisible_captcha/route_protection/rules';
    private const XML_ROUTE_LAYERED_IGNORED = 'hryvinskyi_invisible_captcha/route_protection/layered_nav_ignored_params';
    private const XML_ROUTE_EXCLUDED_IPS = 'hryvinskyi_invisible_captcha/route_protection/excluded_ips';
    private const XML_ROUTE_EXCLUDED_UA = 'hryvinskyi_invisible_captcha/route_protection/excluded_user_agents';
    private const XML_ROUTE_AJAX_MARKERS = 'hryvinskyi_invisible_captcha/route_protection/ajax_marker_params';
    private const XML_ROUTE_BG_AJAX_MARKERS = 'hryvinskyi_invisible_captcha/route_protection/background_ajax_marker_params';
    private const XML_ROUTE_FILTER_ANCHOR = 'hryvinskyi_invisible_captcha/route_protection/filter_anchor_selector';
    private const XML_ROUTE_FILTER_PARAM = 'hryvinskyi_invisible_captcha/route_protection/filter_param_pattern';

    /* Appearance (nested under route protection) */
    private const XML_APPEARANCE_PRIMARY = 'hryvinskyi_invisible_captcha/route_protection/appearance/primary_color';
    private const XML_APPEARANCE_PRIMARY_DEEP = 'hryvinskyi_invisible_captcha/route_protection/appearance/primary_color_deep';
    private const XML_APPEARANCE_PRIMARY_SOFT = 'hryvinskyi_invisible_captcha/route_protection/appearance/primary_color_soft';

    /* Advanced */
    private const XML_HTTP_TIMEOUT = 'hryvinskyi_invisible_captcha/advanced/http_timeout';

    /* Support email chain */
    private const XML_SUPPORT_EMAIL = 'trans_email/ident_support/email';
    private const XML_GENERAL_EMAIL = 'trans_email/ident_general/email';

    private const DEFAULT_COOKIE_LIFETIME = 14400;
    private const DEFAULT_FALLBACK_DELAY = 10;
    private const DEFAULT_HTTP_TIMEOUT = 2.0;

    /**
     * Form key => [group, enabled field id, score threshold field id].
     *
     * @var array<string, array{0:string,1:string,2:string}>
     */
    private const FORM_MAP = [
        self::FORM_CUSTOMER_LOGIN => ['frontend', 'enabled_customer_login', 'score_threshold_customer_login'],
        self::FORM_CUSTOMER_CREATE => ['frontend', 'enabled_customer_create', 'score_threshold_customer_create'],
        self::FORM_CUSTOMER_FORGOT => ['frontend', 'enabled_customer_forgot', 'score_threshold_customer_forgot'],
        self::FORM_CUSTOMER_EDIT => ['frontend', 'enabled_customer_edit', 'score_threshold_customer_edit'],
        self::FORM_CONTACT => ['frontend', 'enabled_contact', 'score_threshold_contact'],
        self::FORM_NEWSLETTER => ['frontend', 'enabled_newsletter', 'score_threshold_newsletter'],
        self::FORM_SEND_FRIEND => ['frontend', 'enabled_send_friend', 'score_threshold_send_friend'],
        self::FORM_PRODUCT_REVIEW => ['frontend', 'enabled_product_review', 'score_threshold_product_review'],
        self::FORM_WISHLIST => ['frontend', 'enabled_wishlist', 'score_threshold_wishlist'],
        self::FORM_COUPON_CODE => ['frontend', 'enabled_coupon_code', 'score_threshold_coupon_code'],
        self::FORM_PLACE_ORDER => ['frontend', 'enabled_place_order', 'score_threshold_place_order'],
        self::FORM_STORE_PICKUP => ['frontend', 'enabled_store_pickup', 'score_threshold_store_pickup'],
        self::FORM_PAYPAL_PAYFLOWPRO => ['frontend', 'enabled_paypal_payflowpro', 'score_threshold_paypal_payflowpro'],
        self::FORM_RESEND_CONFIRMATION_EMAIL => ['frontend', 'enabled_resend_confirmation_email', 'score_threshold_resend_confirmation_email'],
        self::FORM_ADMIN_LOGIN => ['backend', 'enabled_login', 'score_threshold_login'],
        self::FORM_ADMIN_FORGOT => ['backend', 'enabled_forgot', 'score_threshold_forgot'],
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $serializer
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Json $serializer
    ) {
    }

    /* ---------------------------------------------------------------- General */

    public function isEnabled(?string $scopeCode = null): bool
    {
        return $this->flag(self::XML_ENABLED, $scopeCode);
    }

    public function getActiveProvider(?string $scopeCode = null): string
    {
        $value = $this->value(self::XML_ACTIVE_PROVIDER, $scopeCode);

        return $value !== '' ? $value : ProviderInterface::CODE_RECAPTCHA_V3;
    }

    public function isDebug(?string $scopeCode = null): bool
    {
        return $this->flag(self::XML_DEBUG, $scopeCode);
    }

    public function isLazyLoad(?string $scopeCode = null): bool
    {
        return $this->flag(self::XML_LAZY_LOAD, $scopeCode);
    }

    public function isDisableSubmitForm(?string $scopeCode = null): bool
    {
        return $this->flag(self::XML_DISABLE_SUBMIT, $scopeCode);
    }

    /* ------------------------------------------------------- Form protection */

    public function isFormProtectionEnabled(?string $scopeCode = null): bool
    {
        return $this->flag(self::XML_FORM_ENABLED, $scopeCode);
    }

    public function isFormAreaEnabled(string $area, ?string $scopeCode = null): bool
    {
        $path = $area === self::AREA_ADMINHTML ? self::XML_FORM_BACKEND_ENABLED : self::XML_FORM_FRONTEND_ENABLED;

        return $this->flag($path, $scopeCode);
    }

    public function isFormEnabled(string $form, ?string $scopeCode = null): bool
    {
        if (!isset(self::FORM_MAP[$form])) {
            return false;
        }
        [$group, $field] = self::FORM_MAP[$form];

        return $this->flag(self::XML_FORM_PREFIX . $group . '/' . $field, $scopeCode);
    }

    public function getFormScoreThreshold(string $form, ?string $scopeCode = null): float
    {
        if (!isset(self::FORM_MAP[$form])) {
            return 0.5;
        }
        [$group, , $field] = self::FORM_MAP[$form];
        $value = (float)$this->value(self::XML_FORM_PREFIX . $group . '/' . $field, $scopeCode);

        return $value > 0 ? $value : 0.5;
    }

    /* ------------------------------------------------------ Route protection */

    public function isRouteProtectionEnabled(?string $scopeCode = null): bool
    {
        return $this->flag(self::XML_ROUTE_ENABLED, $scopeCode);
    }

    public function getRouteProviderOverride(?string $scopeCode = null): string
    {
        return $this->value(self::XML_ROUTE_PROVIDER_OVERRIDE, $scopeCode);
    }

    public function isRouteFallbackEnabled(?string $scopeCode = null): bool
    {
        return $this->flag(self::XML_ROUTE_FALLBACK_ENABLED, $scopeCode);
    }

    public function getRouteFallbackProvider(?string $scopeCode = null): string
    {
        return $this->value(self::XML_ROUTE_FALLBACK_PROVIDER, $scopeCode);
    }

    public function getRouteFallbackDelay(?string $scopeCode = null): int
    {
        $value = (int)$this->value(self::XML_ROUTE_FALLBACK_DELAY, $scopeCode);

        return $value > 0 ? $value : self::DEFAULT_FALLBACK_DELAY;
    }

    public function getCookieLifetime(?string $scopeCode = null): int
    {
        $value = (int)$this->value(self::XML_ROUTE_COOKIE_LIFETIME, $scopeCode);

        return $value > 0 ? $value : self::DEFAULT_COOKIE_LIFETIME;
    }

    public function getProtectionRulesConfig(?string $scopeCode = null): array
    {
        $raw = $this->value(self::XML_ROUTE_RULES, $scopeCode);
        if (trim($raw) === '') {
            return [];
        }

        try {
            $decoded = $this->serializer->unserialize($raw);
        } catch (\InvalidArgumentException $e) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function getLayeredNavIgnoredParams(?string $scopeCode = null): array
    {
        return $this->parseList($this->value(self::XML_ROUTE_LAYERED_IGNORED, $scopeCode));
    }

    public function getExcludedIps(?string $scopeCode = null): array
    {
        return $this->parseList($this->value(self::XML_ROUTE_EXCLUDED_IPS, $scopeCode));
    }

    public function getExcludedUserAgents(?string $scopeCode = null): array
    {
        return $this->parseList($this->value(self::XML_ROUTE_EXCLUDED_UA, $scopeCode));
    }

    public function getAjaxMarkerParams(?string $scopeCode = null): array
    {
        return $this->parseList($this->value(self::XML_ROUTE_AJAX_MARKERS, $scopeCode));
    }

    public function getBackgroundAjaxMarkerParams(?string $scopeCode = null): array
    {
        return $this->parseList($this->value(self::XML_ROUTE_BG_AJAX_MARKERS, $scopeCode));
    }

    public function getFilterAnchorSelector(?string $scopeCode = null): string
    {
        return $this->value(self::XML_ROUTE_FILTER_ANCHOR, $scopeCode);
    }

    public function getFilterParamPattern(?string $scopeCode = null): string
    {
        return $this->value(self::XML_ROUTE_FILTER_PARAM, $scopeCode);
    }

    public function getSupportEmail(?string $scopeCode = null): string
    {
        $email = $this->value(self::XML_SUPPORT_EMAIL, $scopeCode);
        if ($email === '') {
            $email = $this->value(self::XML_GENERAL_EMAIL, $scopeCode);
        }

        return $email;
    }

    /* ----------------------------------------------------------- Appearance */

    public function getChallengePrimaryColor(?string $scopeCode = null): string
    {
        return $this->value(self::XML_APPEARANCE_PRIMARY, $scopeCode);
    }

    public function getChallengePrimaryColorDeep(?string $scopeCode = null): string
    {
        return $this->value(self::XML_APPEARANCE_PRIMARY_DEEP, $scopeCode);
    }

    public function getChallengePrimaryColorSoft(?string $scopeCode = null): string
    {
        return $this->value(self::XML_APPEARANCE_PRIMARY_SOFT, $scopeCode);
    }

    /* ------------------------------------------------------------- Advanced */

    public function getHttpTimeout(?string $scopeCode = null): float
    {
        $value = (float)$this->value(self::XML_HTTP_TIMEOUT, $scopeCode);

        return $value > 0 ? $value : self::DEFAULT_HTTP_TIMEOUT;
    }

    /* ------------------------------------------------------------- Internals */

    private function flag(string $path, ?string $scopeCode): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    private function value(string $path, ?string $scopeCode): string
    {
        return (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    /**
     * Parse a newline-separated textarea value into a trimmed, non-empty list.
     *
     * @return string[]
     */
    private function parseList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }
}
