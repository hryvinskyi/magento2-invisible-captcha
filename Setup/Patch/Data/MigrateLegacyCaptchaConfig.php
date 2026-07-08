<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Setup\Patch\Data;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migrates stored settings from the v2.x InvisibleCaptcha config tree and the
 * (now merged) TurnstileProtection config tree into the unified v3 tree.
 *
 * Runs once. Existing v3 values are never overwritten. The legacy reCAPTCHA v3
 * secret was stored in plaintext, so it is encrypted on migration; the Turnstile
 * and reCAPTCHA-fallback secrets were already encrypted and are copied verbatim.
 */
class MigrateLegacyCaptchaConfig implements DataPatchInterface
{
    /** Old reCAPTCHA-v3 (v2.x InvisibleCaptcha) path => new path. */
    private const IC_MAP = [
        'hryvinskyi_invisible_captcha/general/enabledCaptcha' => 'hryvinskyi_invisible_captcha/general/enabled',
        'hryvinskyi_invisible_captcha/general/captchaSiteKey' => 'hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key',
        'hryvinskyi_invisible_captcha/general/useLazyLoad' => 'hryvinskyi_invisible_captcha/general/use_lazy_load',
        'hryvinskyi_invisible_captcha/general/disableSubmitForm' => 'hryvinskyi_invisible_captcha/general/disable_submit_form',
        'hryvinskyi_invisible_captcha/general/hideBadge' => 'hryvinskyi_invisible_captcha/providers/recaptcha_v3/hide_badge',
        'hryvinskyi_invisible_captcha/general/hideBadgeText' => 'hryvinskyi_invisible_captcha/providers/recaptcha_v3/hide_badge_text',
        'hryvinskyi_invisible_captcha/general/debug' => 'hryvinskyi_invisible_captcha/general/debug',
        'hryvinskyi_invisible_captcha/frontend/enabled' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled',
        'hryvinskyi_invisible_captcha/frontend/enabledCustomerLogin' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_login',
        'hryvinskyi_invisible_captcha/frontend/scoreThresholdCustomerLogin' => 'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_customer_login',
        'hryvinskyi_invisible_captcha/frontend/enabledCustomerCreate' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_create',
        'hryvinskyi_invisible_captcha/frontend/scoreThresholdCustomerCreate' => 'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_customer_create',
        'hryvinskyi_invisible_captcha/frontend/enabledCustomerForgot' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_forgot',
        'hryvinskyi_invisible_captcha/frontend/scoreThresholdCustomerForgot' => 'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_customer_forgot',
        'hryvinskyi_invisible_captcha/frontend/enabledContact' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_contact',
        'hryvinskyi_invisible_captcha/frontend/scoreThresholdContact' => 'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_contact',
        'hryvinskyi_invisible_captcha/frontend/enabledNewsletter' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_newsletter',
        'hryvinskyi_invisible_captcha/frontend/scoreThresholdNewsletter' => 'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_newsletter',
        'hryvinskyi_invisible_captcha/frontend/enabledSendFriend' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_send_friend',
        'hryvinskyi_invisible_captcha/frontend/scoreThresholdSendFriend' => 'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_send_friend',
        'hryvinskyi_invisible_captcha/frontend/enabledProductReview' => 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_product_review',
        'hryvinskyi_invisible_captcha/frontend/scoreThresholdProductReview' => 'hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_product_review',
        'hryvinskyi_invisible_captcha/backend/enabled' => 'hryvinskyi_invisible_captcha/form_protection/backend/enabled',
        'hryvinskyi_invisible_captcha/backend/enabledLogin' => 'hryvinskyi_invisible_captcha/form_protection/backend/enabled_login',
        'hryvinskyi_invisible_captcha/backend/scoreThresholdLogin' => 'hryvinskyi_invisible_captcha/form_protection/backend/score_threshold_login',
        'hryvinskyi_invisible_captcha/backend/enabledForgot' => 'hryvinskyi_invisible_captcha/form_protection/backend/enabled_forgot',
        'hryvinskyi_invisible_captcha/backend/scoreThresholdForgot' => 'hryvinskyi_invisible_captcha/form_protection/backend/score_threshold_forgot',
    ];

    /** Old TurnstileProtection path => new path. */
    private const TP_MAP = [
        'hryvinskyi_turnstile/general/enabled' => 'hryvinskyi_invisible_captcha/route_protection/enabled',
        'hryvinskyi_turnstile/general/site_key' => 'hryvinskyi_invisible_captcha/providers/turnstile/site_key',
        'hryvinskyi_turnstile/general/cookie_lifetime' => 'hryvinskyi_invisible_captcha/route_protection/cookie_lifetime',
        'hryvinskyi_turnstile/general/widget_size' => 'hryvinskyi_invisible_captcha/providers/turnstile/widget_size',
        'hryvinskyi_turnstile/general/widget_appearance' => 'hryvinskyi_invisible_captcha/providers/turnstile/widget_appearance',
        'hryvinskyi_turnstile/general/fallback_enabled' => 'hryvinskyi_invisible_captcha/route_protection/fallback_enabled',
        'hryvinskyi_turnstile/general/recaptcha_site_key' => 'hryvinskyi_invisible_captcha/providers/recaptcha_v2_checkbox/site_key',
        'hryvinskyi_turnstile/general/fallback_delay' => 'hryvinskyi_invisible_captcha/route_protection/fallback_delay',
        'hryvinskyi_turnstile/protection/rules' => 'hryvinskyi_invisible_captcha/route_protection/rules',
        'hryvinskyi_turnstile/protection/layered_nav_ignored_params' => 'hryvinskyi_invisible_captcha/route_protection/layered_nav_ignored_params',
        'hryvinskyi_turnstile/protection/excluded_ips' => 'hryvinskyi_invisible_captcha/route_protection/excluded_ips',
        'hryvinskyi_turnstile/protection/excluded_user_agents' => 'hryvinskyi_invisible_captcha/route_protection/excluded_user_agents',
        'hryvinskyi_turnstile/protection/ajax_marker_params' => 'hryvinskyi_invisible_captcha/route_protection/ajax_marker_params',
        'hryvinskyi_turnstile/protection/background_ajax_marker_params' => 'hryvinskyi_invisible_captcha/route_protection/background_ajax_marker_params',
        'hryvinskyi_turnstile/protection/filter_anchor_selector' => 'hryvinskyi_invisible_captcha/route_protection/filter_anchor_selector',
        'hryvinskyi_turnstile/protection/filter_param_pattern' => 'hryvinskyi_invisible_captcha/route_protection/filter_param_pattern',
        'hryvinskyi_turnstile/advanced/turnstile_api_url' => 'hryvinskyi_invisible_captcha/providers/turnstile/verify_url',
        'hryvinskyi_turnstile/advanced/recaptcha_api_url' => 'hryvinskyi_invisible_captcha/providers/recaptcha_v2_checkbox/verify_url',
    ];

    /** Already-encrypted secret paths copied verbatim. */
    private const TP_SECRET_MAP = [
        'hryvinskyi_turnstile/general/secret_key' => 'hryvinskyi_invisible_captcha/providers/turnstile/secret_key',
        'hryvinskyi_turnstile/general/recaptcha_secret_key' => 'hryvinskyi_invisible_captcha/providers/recaptcha_v2_checkbox/secret_key',
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        foreach (self::IC_MAP as $old => $new) {
            $this->copyPath($old, $new);
        }
        foreach (self::TP_MAP as $old => $new) {
            $this->copyPath($old, $new);
        }
        foreach (self::TP_SECRET_MAP as $old => $new) {
            $this->copyPath($old, $new);
        }

        // Legacy reCAPTCHA v3 secret was plaintext — encrypt on migration.
        $this->copyPath(
            'hryvinskyi_invisible_captcha/general/captchaSecretKey',
            'hryvinskyi_invisible_captcha/providers/recaptcha_v3/secret_key',
            true
        );

        $this->applyDerivedDefaults();

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * Copy every scope row for a legacy path to the new path, never clobbering a
     * value already present at the new path. Optionally encrypts the value.
     */
    private function copyPath(string $oldPath, string $newPath, bool $encrypt = false): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $rows = $connection->fetchAll(
            $connection->select()->from($table, ['scope', 'scope_id', 'value'])->where('path = ?', $oldPath)
        );

        foreach ($rows as $row) {
            $value = (string)$row['value'];
            if ($value === '') {
                continue;
            }
            if ($encrypt) {
                $value = $this->encryptor->encrypt($value);
            }
            $this->writeIfAbsent($newPath, (string)$row['scope'], (int)$row['scope_id'], $value);
        }
    }

    /**
     * Insert a config row unless one already exists for that path/scope.
     */
    private function writeIfAbsent(string $path, string $scope, int $scopeId, string $value): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $exists = $connection->fetchOne(
            $connection->select()->from($table, 'config_id')
                ->where('path = ?', $path)
                ->where('scope = ?', $scope)
                ->where('scope_id = ?', $scopeId)
        );

        if ($exists !== false) {
            return;
        }

        $connection->insert($table, [
            'scope' => $scope,
            'scope_id' => $scopeId,
            'path' => $path,
            'value' => $value,
        ]);
    }

    /**
     * Derive the new selector defaults from the migrated state.
     */
    private function applyDerivedDefaults(): void
    {
        // If any legacy reCAPTCHA-v3 config existed, default the active provider to v3.
        if ($this->legacyValueExists('hryvinskyi_invisible_captcha/general/enabledCaptcha')
            || $this->legacyValueExists('hryvinskyi_invisible_captcha/general/captchaSiteKey')
        ) {
            $this->writeIfAbsent(
                'hryvinskyi_invisible_captcha/general/active_provider',
                'default',
                0,
                ProviderInterface::CODE_RECAPTCHA_V3
            );
        }

        // If form protection was used at all, turn on the parent form-protection switch.
        if ($this->legacyFlagEnabled('hryvinskyi_invisible_captcha/frontend/enabled')
            || $this->legacyFlagEnabled('hryvinskyi_invisible_captcha/backend/enabled')
        ) {
            $this->writeIfAbsent('hryvinskyi_invisible_captcha/form_protection/enabled', 'default', 0, '1');
        }

        // If Turnstile route protection was enabled, wire the route gate + fallback providers
        // and ensure the module master switch is on.
        if ($this->legacyFlagEnabled('hryvinskyi_turnstile/general/enabled')) {
            $this->writeIfAbsent('hryvinskyi_invisible_captcha/general/enabled', 'default', 0, '1');
            $this->writeIfAbsent('hryvinskyi_invisible_captcha/route_protection/provider_override', 'default', 0, ProviderInterface::CODE_TURNSTILE);
            $this->writeIfAbsent('hryvinskyi_invisible_captcha/route_protection/fallback_provider', 'default', 0, ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX);
        }

        // Merge legacy TP debug flag into the unified debug flag.
        if ($this->legacyFlagEnabled('hryvinskyi_turnstile/advanced/debug_mode')) {
            $this->writeIfAbsent('hryvinskyi_invisible_captcha/general/debug', 'default', 0, '1');
        }
    }

    /**
     * Whether a legacy path has any non-empty stored value at any scope.
     */
    private function legacyValueExists(string $path): bool
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        return (bool)$connection->fetchOne(
            $connection->select()->from($table, 'config_id')->where('path = ?', $path)
        );
    }

    /**
     * Whether a legacy flag path is enabled (=1) at any scope.
     */
    private function legacyFlagEnabled(string $path): bool
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $value = $connection->fetchOne(
            $connection->select()->from($table, 'value')->where('path = ?', $path)->where('value = ?', '1')
        );

        return $value !== false;
    }
}
