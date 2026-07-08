<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Migration;

use Hryvinskyi\InvisibleCaptcha\Api\Migration\CoreConfigGatewayInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Migration\RecaptchaMigratorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @inheritDoc
 *
 * Holds only mapping/derivation policy: it never touches the database directly,
 * delegating every read/existence-check/write to {@see CoreConfigGatewayInterface}
 * and carrying per-run state in a {@see MigrationRun}, so it stays stateless and
 * unit-testable without the DB.
 */
class RecaptchaMigrator implements RecaptchaMigratorInterface
{
    private const PROVIDER_PREFIX = 'hryvinskyi_invisible_captcha/providers/';
    private const FORM_PREFIX = 'hryvinskyi_invisible_captcha/form_protection/';
    private const XML_ENABLED = 'hryvinskyi_invisible_captcha/general/enabled';
    private const XML_ACTIVE_PROVIDER = 'hryvinskyi_invisible_captcha/general/active_provider';
    private const XML_FORM_ENABLED = 'hryvinskyi_invisible_captcha/form_protection/enabled';

    /** Native reCAPTCHA sections, in credential-preference order (frontend wins). */
    private const SECTIONS = ['recaptcha_frontend', 'recaptcha_backend'];

    /** Native `type_for` selector value => this module's provider code. */
    private const TYPE_MAP = [
        'recaptcha_v3' => ProviderInterface::CODE_RECAPTCHA_V3,
        'invisible' => ProviderInterface::CODE_RECAPTCHA_V2_INVISIBLE,
        'recaptcha' => ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX,
    ];

    /** Tie-break when a scope mixes providers across forms (higher wins). */
    private const PROVIDER_PRIORITY = [
        ProviderInterface::CODE_RECAPTCHA_V3 => 3,
        ProviderInterface::CODE_RECAPTCHA_V2_INVISIBLE => 2,
        ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX => 1,
    ];

    /**
     * Native credential group => [provider code, [native field => provider field]].
     *
     * `private_key` is copied verbatim: both modules store it through
     * {@see \Magento\Config\Model\Config\Backend\Encrypted}, so the ciphertext is
     * valid as-is under the same crypt key. Native-only fields with no counterpart
     * here (position, lang, v2-invisible theme) are intentionally dropped.
     *
     * @var array<string, array{provider: string, fields: array<string, string>}>
     */
    private const CREDENTIAL_MAP = [
        'type_recaptcha_v3' => [
            'provider' => ProviderInterface::CODE_RECAPTCHA_V3,
            'fields' => ['public_key' => 'site_key', 'private_key' => 'secret_key'],
        ],
        'type_recaptcha' => [
            'provider' => ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX,
            'fields' => ['public_key' => 'site_key', 'private_key' => 'secret_key', 'theme' => 'theme', 'size' => 'size'],
        ],
        'type_invisible' => [
            'provider' => ProviderInterface::CODE_RECAPTCHA_V2_INVISIBLE,
            'fields' => ['public_key' => 'site_key', 'private_key' => 'secret_key'],
        ],
    ];

    /**
     * [native section => [native type_for field => [ [area, enable field, score field], ... ]]].
     *
     * A native selector may fan out to several targets: reCAPTCHA's single
     * `place_order` gate protects both checkout and in-store pickup, which are
     * separate forms here.
     *
     * @var array<string, array<string, array<int, array{0:string,1:string,2:string}>>>
     */
    private const FORM_MAP = [
        'recaptcha_frontend' => [
            'customer_login' => [['frontend', 'enabled_customer_login', 'score_threshold_customer_login']],
            'customer_create' => [['frontend', 'enabled_customer_create', 'score_threshold_customer_create']],
            'customer_forgot_password' => [['frontend', 'enabled_customer_forgot', 'score_threshold_customer_forgot']],
            'customer_edit' => [['frontend', 'enabled_customer_edit', 'score_threshold_customer_edit']],
            'contact' => [['frontend', 'enabled_contact', 'score_threshold_contact']],
            'newsletter' => [['frontend', 'enabled_newsletter', 'score_threshold_newsletter']],
            'sendfriend' => [['frontend', 'enabled_send_friend', 'score_threshold_send_friend']],
            'product_review' => [['frontend', 'enabled_product_review', 'score_threshold_product_review']],
            'wishlist' => [['frontend', 'enabled_wishlist', 'score_threshold_wishlist']],
            'coupon_code' => [['frontend', 'enabled_coupon_code', 'score_threshold_coupon_code']],
            'place_order' => [
                ['frontend', 'enabled_place_order', 'score_threshold_place_order'],
                ['frontend', 'enabled_store_pickup', 'score_threshold_store_pickup'],
            ],
            'paypal_payflowpro' => [['frontend', 'enabled_paypal_payflowpro', 'score_threshold_paypal_payflowpro']],
            'resend_confirmation_email' => [['frontend', 'enabled_resend_confirmation_email', 'score_threshold_resend_confirmation_email']],
        ],
        'recaptcha_backend' => [
            'user_login' => [['backend', 'enabled_login', 'score_threshold_login']],
            'user_forgot_password' => [['backend', 'enabled_forgot', 'score_threshold_forgot']],
        ],
    ];

    /** Provider field ids that hold encrypted secrets (masked in the change log). */
    private const SECRET_FIELDS = ['secret_key'];

    /**
     * @param CoreConfigGatewayInterface $gateway
     */
    public function __construct(
        private readonly CoreConfigGatewayInterface $gateway
    ) {
    }

    /**
     * @inheritDoc
     */
    public function migrate(bool $dryRun = false, bool $force = false): array
    {
        $run = new MigrationRun($dryRun, $force);
        $prefixes = array_map(static fn (string $section): string => $section . '/', self::SECTIONS);
        $data = $this->gateway->fetchTree($prefixes);

        $this->migrateCredentials($run, $data);
        $this->migrateFormsAndDerived($run, $data);

        return $run->records();
    }

    /**
     * Copy provider credentials for every configured scope. Frontend is processed
     * before backend, so shared provider paths take the frontend value and the
     * backend row only fills a gap.
     *
     * @param array<string, array<int, array<string, string>>> $data
     */
    private function migrateCredentials(MigrationRun $run, array $data): void
    {
        foreach (self::SECTIONS as $section) {
            foreach (self::CREDENTIAL_MAP as $group => $spec) {
                foreach ($spec['fields'] as $nativeField => $providerField) {
                    $sourcePath = $section . '/' . $group . '/' . $nativeField;
                    $targetPath = self::PROVIDER_PREFIX . $spec['provider'] . '/' . $providerField;
                    $isSecret = in_array($providerField, self::SECRET_FIELDS, true);

                    foreach ($data as $scope => $byId) {
                        foreach ($byId as $scopeId => $paths) {
                            $value = $paths[$sourcePath] ?? '';
                            if ($value === '') {
                                continue;
                            }
                            $this->write($run, $sourcePath, $targetPath, $scope, $scopeId, $value, $isSecret);
                        }
                    }
                }
            }
        }
    }

    /**
     * Translate the per-form `type_for/*` selectors and derive the active provider,
     * score thresholds and master switches — per scope.
     *
     * @param array<string, array<int, array<string, string>>> $data
     */
    private function migrateFormsAndDerived(MigrationRun $run, array $data): void
    {
        foreach ($data as $scope => $byId) {
            foreach ($byId as $scopeId => $paths) {
                $enabledAreas = [];
                $providerVotes = [];

                foreach (self::FORM_MAP as $section => $forms) {
                    foreach ($forms as $nativeField => $targets) {
                        $type = $paths[$section . '/type_for/' . $nativeField] ?? '';
                        if (!isset(self::TYPE_MAP[$type])) {
                            continue;
                        }
                        $providerCode = self::TYPE_MAP[$type];
                        $providerVotes[$providerCode] = ($providerVotes[$providerCode] ?? 0) + 1;

                        // reCAPTCHA v3 keeps one section-wide threshold; fan it out per form.
                        $threshold = $providerCode === ProviderInterface::CODE_RECAPTCHA_V3
                            ? $this->resolveValue($data, $scope, $scopeId, $section . '/type_recaptcha_v3/score_threshold')
                            : '';

                        foreach ($targets as [$area, $enableField, $scoreField]) {
                            $enabledAreas[$area] = true;
                            $this->write(
                                $run,
                                $section . '/type_for/' . $nativeField,
                                self::FORM_PREFIX . $area . '/' . $enableField,
                                $scope,
                                $scopeId,
                                '1',
                                false
                            );
                            if ($threshold !== '') {
                                $this->write(
                                    $run,
                                    $section . '/type_recaptcha_v3/score_threshold',
                                    self::FORM_PREFIX . $area . '/' . $scoreField,
                                    $scope,
                                    $scopeId,
                                    $threshold,
                                    false
                                );
                            }
                        }
                    }
                }

                if ($enabledAreas === []) {
                    continue;
                }

                // Master switches + active provider so the migrated config is actually live.
                $this->write($run, null, self::XML_ENABLED, $scope, $scopeId, '1', false);
                $this->write($run, null, self::XML_FORM_ENABLED, $scope, $scopeId, '1', false);
                foreach (array_keys($enabledAreas) as $area) {
                    $this->write($run, null, self::FORM_PREFIX . $area . '/enabled', $scope, $scopeId, '1', false);
                }
                $this->write(
                    $run,
                    null,
                    self::XML_ACTIVE_PROVIDER,
                    $scope,
                    $scopeId,
                    $this->pickProvider($providerVotes),
                    false
                );
            }
        }
    }

    /**
     * Record and (unless dry-run) persist one path write, honouring the
     * never-clobber / --force contract and masking secret values in the log.
     */
    private function write(
        MigrationRun $run,
        ?string $source,
        string $target,
        string $scope,
        int $scopeId,
        string $value,
        bool $isSecret
    ): void {
        if (!$run->claim($scope, $scopeId, $target)) {
            return;
        }

        $exists = $this->gateway->exists($target, $scope, $scopeId);
        if ($exists && !$run->force) {
            $run->add($this->record($source, $target, $scope, $scopeId, $value, $isSecret, self::STATUS_SKIPPED_EXISTS));
            return;
        }

        $status = $exists ? self::STATUS_OVERWRITTEN : self::STATUS_MIGRATED;
        if (!$run->dryRun) {
            $this->gateway->write($target, $value, $scope, $scopeId);
        }

        $run->add($this->record($source, $target, $scope, $scopeId, $value, $isSecret, $status));
    }

    /**
     * Build a single change record, masking secret values.
     */
    private function record(?string $source, string $target, string $scope, int $scopeId, string $value, bool $isSecret, string $status): ChangeRecord
    {
        return new ChangeRecord($source, $target, $scope, $scopeId, $isSecret ? '********' : $value, $status);
    }

    /**
     * Most-voted provider for a scope; ties break by {@see self::PROVIDER_PRIORITY}.
     *
     * @param array<string, int> $votes
     */
    private function pickProvider(array $votes): string
    {
        $best = ProviderInterface::CODE_RECAPTCHA_V3;
        $bestVotes = -1;
        $bestPriority = -1;
        foreach ($votes as $code => $count) {
            $priority = self::PROVIDER_PRIORITY[$code] ?? 0;
            if ($count > $bestVotes || ($count === $bestVotes && $priority > $bestPriority)) {
                $best = $code;
                $bestVotes = $count;
                $bestPriority = $priority;
            }
        }

        return $best;
    }

    /**
     * Resolve a native value at the given scope, falling back to the default scope.
     *
     * @param array<string, array<int, array<string, string>>> $data
     */
    private function resolveValue(array $data, string $scope, int $scopeId, string $path): string
    {
        $value = $data[$scope][$scopeId][$path] ?? '';
        if ($value !== '') {
            return $value;
        }

        return $data[ScopeConfigInterface::SCOPE_TYPE_DEFAULT][0][$path] ?? '';
    }
}
