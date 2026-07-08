<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Migration;

/**
 * Migrates configuration from Magento's native Google reCAPTCHA modules
 * (`recaptcha_frontend/*`, `recaptcha_backend/*`) into the unified
 * `hryvinskyi_invisible_captcha/*` tree.
 *
 * Implementations copy provider credentials (the native module stores both keys
 * through {@see \Magento\Config\Model\Config\Backend\Encrypted} — the secret key
 * is copied verbatim because this module's field is encrypted too, while the
 * site key is decrypted because this module keeps it in plain text), translate
 * the per-form `type_for/*` selectors into this module's per-form enable flags +
 * score thresholds, and derive the active provider and master switches so the
 * merchant's existing protection posture is preserved. Each migrated native
 * selector is then cleared, so the built-in reCAPTCHA stops challenging that
 * form and this module takes over — native credentials are left in place.
 */
interface RecaptchaMigratorInterface
{
    /** A new value was written to the target path. */
    public const STATUS_MIGRATED = 'migrated';

    /** The target path already held a value and was left untouched (no --force). */
    public const STATUS_SKIPPED_EXISTS = 'skipped_exists';

    /** The target path already held a value and was replaced (--force). */
    public const STATUS_OVERWRITTEN = 'overwritten';

    /** A migrated native `type_for/*` selector row was cleared (native reCAPTCHA disabled). */
    public const STATUS_SOURCE_DISABLED = 'source_disabled';

    /**
     * An encrypted native value could not be decrypted with this installation's
     * crypt key (typically a DB imported from another environment) and was NOT
     * migrated. Fix the crypt key in app/etc/env.php or re-enter the value, then
     * re-run.
     */
    public const STATUS_SKIPPED_UNDECRYPTABLE = 'skipped_undecryptable';

    /**
     * Perform the migration.
     *
     * @param bool $dryRun When true, compute and return the change set without writing anything.
     * @param bool $force When true, overwrite values already present at target paths.
     * @return \Hryvinskyi\InvisibleCaptcha\Model\Migration\ChangeRecord[] Ordered change
     *     records (source is null for derived values; secret values are masked).
     */
    public function migrate(bool $dryRun = false, bool $force = false): array;
}
