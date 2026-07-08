<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Migration;

use Hryvinskyi\InvisibleCaptcha\Api\Migration\CoreConfigGatewayInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Migration\RecaptchaMigratorInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Migration\ChangeRecord;
use Hryvinskyi\InvisibleCaptcha\Model\Migration\RecaptchaMigrator;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The migrator holds only mapping/derivation policy, so it is exercised purely
 * against a fake {@see CoreConfigGatewayInterface} — no DB, no Select mocking.
 */
class RecaptchaMigratorTest extends TestCase
{
    /** @var CoreConfigGatewayInterface&MockObject */
    private CoreConfigGatewayInterface $gateway;
    /** @var EncryptorInterface&MockObject */
    private EncryptorInterface $encryptor;
    private RecaptchaMigrator $migrator;

    /** @var array<string, string> Captured write() calls keyed "scope|id|path". */
    private array $written = [];
    /** @var string[] Captured delete() calls as "scope|id|path". */
    private array $deleted = [];
    private bool $targetsExist = false;

    protected function setUp(): void
    {
        $this->written = [];
        $this->deleted = [];
        $this->targetsExist = false;

        $this->gateway = $this->createMock(CoreConfigGatewayInterface::class);
        $this->gateway->method('exists')->willReturnCallback(fn (): bool => $this->targetsExist);
        $this->gateway->method('write')->willReturnCallback(
            function (string $path, string $value, string $scope, int $scopeId): void {
                $this->written[$scope . '|' . $scopeId . '|' . $path] = $value;
            }
        );
        $this->gateway->method('delete')->willReturnCallback(
            function (string $path, string $scope, int $scopeId): void {
                $this->deleted[] = $scope . '|' . $scopeId . '|' . $path;
            }
        );

        // Native keys carry the Magento crypt envelope; "0:3:ENCPUB" decrypts to
        // the plain site key, anything else is treated as undecryptable.
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->encryptor->method('decrypt')->willReturnCallback(
            static fn (string $value): string => $value === '0:3:ENCPUB' ? 'PUBKEY' : ''
        );

        $this->migrator = new RecaptchaMigrator($this->gateway, $this->encryptor);
    }

    /**
     * Native config indexed as the gateway returns it: [scope][scopeId][path] => value.
     * The frontend v3 site key is stored encrypted (as the native module does);
     * the backend invisible one is legacy plaintext to cover the passthrough path.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function nativeTree(): array
    {
        return [
            'default' => [
                0 => [
                    'recaptcha_frontend/type_recaptcha_v3/public_key' => '0:3:ENCPUB',
                    'recaptcha_frontend/type_recaptcha_v3/private_key' => 'ENC_SECRET',
                    'recaptcha_frontend/type_recaptcha_v3/score_threshold' => '0.7',
                    'recaptcha_frontend/type_for/contact' => 'recaptcha_v3',
                    'recaptcha_frontend/type_for/customer_login' => 'recaptcha_v3',
                    'recaptcha_frontend/type_for/place_order' => 'recaptcha_v3',
                    'recaptcha_backend/type_invisible/public_key' => 'BE_PUB',
                    'recaptcha_backend/type_invisible/private_key' => 'BE_ENC',
                    'recaptcha_backend/type_for/user_login' => 'invisible',
                ],
            ],
        ];
    }

    /** Native selector rows the tree above should hand over (clear). */
    private const EXPECTED_DISABLED = [
        'default|0|recaptcha_frontend/type_for/contact',
        'default|0|recaptcha_frontend/type_for/customer_login',
        'default|0|recaptcha_frontend/type_for/place_order',
        'default|0|recaptcha_backend/type_for/user_login',
    ];

    /**
     * @param ChangeRecord[] $records
     * @return array<string, ChangeRecord>
     */
    private function indexByTarget(array $records): array
    {
        $indexed = [];
        foreach ($records as $record) {
            $indexed[$record->scope . '|' . $record->scopeId . '|' . $record->target] = $record;
        }

        return $indexed;
    }

    public function testMigratesCredentialsFormsAndDerivedValues(): void
    {
        $this->gateway->method('fetchTree')->willReturn($this->nativeTree());

        $records = $this->migrator->migrate(false, false);
        $byTarget = $this->indexByTarget($records);

        // Encrypted native site key is decrypted; legacy plaintext one passes through.
        $this->assertSame('PUBKEY', $this->written['default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key']);
        $this->assertSame('BE_PUB', $this->written['default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v2_invisible/site_key']);

        // Secret is masked in the change log but copied verbatim (stays encrypted).
        $secretKey = 'default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v3/secret_key';
        $this->assertSame('********', $byTarget[$secretKey]->value);
        $this->assertSame('ENC_SECRET', $this->written[$secretKey]);
        $this->assertSame('BE_ENC', $this->written['default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v2_invisible/secret_key']);

        // Per-form enable flags.
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/frontend/enabled_contact']);
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_login']);
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/backend/enabled_login']);

        // place_order fans out to both place order and in-store pickup.
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/frontend/enabled_place_order']);
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/frontend/enabled_store_pickup']);

        // v3 section-wide threshold fanned out per enabled v3 form.
        $this->assertSame('0.7', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_contact']);
        $this->assertSame('0.7', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/frontend/score_threshold_place_order']);
        // Non-score provider (invisible) gets no threshold row.
        $this->assertArrayNotHasKey('default|0|hryvinskyi_invisible_captcha/form_protection/backend/score_threshold_login', $this->written);

        // Master switches + derived active provider (v3 has the majority of votes).
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/general/enabled']);
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/enabled']);
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/frontend/enabled']);
        $this->assertSame('1', $this->written['default|0|hryvinskyi_invisible_captcha/form_protection/backend/enabled']);
        $this->assertSame('recaptcha_v3', $this->written['default|0|hryvinskyi_invisible_captcha/general/active_provider']);

        // Status hand-over: every migrated native selector is cleared and reported.
        $this->assertEqualsCanonicalizing(self::EXPECTED_DISABLED, $this->deleted);
        foreach (self::EXPECTED_DISABLED as $key) {
            $this->assertSame(RecaptchaMigratorInterface::STATUS_SOURCE_DISABLED, $byTarget[$key]->status);
        }
    }

    public function testUndecryptableSiteKeyIsReportedNotWritten(): void
    {
        $this->gateway->method('fetchTree')->willReturn([
            'default' => [
                0 => ['recaptcha_frontend/type_recaptcha_v3/public_key' => '0:3:GARBAGE'],
            ],
        ]);

        $records = $this->migrator->migrate(false, false);

        $this->assertSame([], $this->written, 'a ciphertext that cannot be decrypted must never be written as a site key');
        // ...but the failure is surfaced in the change log instead of dropped silently.
        $this->assertCount(1, $records);
        $this->assertSame(RecaptchaMigratorInterface::STATUS_SKIPPED_UNDECRYPTABLE, $records[0]->status);
        $this->assertSame('recaptcha_frontend/type_recaptcha_v3/public_key', $records[0]->source);
        $this->assertSame('hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key', $records[0]->target);
    }

    public function testReturnsEmptyWhenNoNativeConfig(): void
    {
        $this->gateway->method('fetchTree')->willReturn([]);

        $this->assertSame([], $this->migrator->migrate(false, false));
        $this->assertSame([], $this->written);
        $this->assertSame([], $this->deleted);
    }

    public function testDryRunWritesNothingButReportsChanges(): void
    {
        $this->gateway->method('fetchTree')->willReturn($this->nativeTree());

        $records = $this->migrator->migrate(true, false);

        $this->assertNotEmpty($records);
        $this->assertSame([], $this->written, 'dry run must not persist anything');
        $this->assertSame([], $this->deleted, 'dry run must not delete native rows');

        $statuses = array_unique(array_map(static fn (ChangeRecord $r): string => $r->status, $records));
        $this->assertEqualsCanonicalizing(
            [RecaptchaMigratorInterface::STATUS_MIGRATED, RecaptchaMigratorInterface::STATUS_SOURCE_DISABLED],
            $statuses,
            'dry run previews both target writes and native-selector disables'
        );
    }

    public function testExistingValuesAreSkippedWithoutForceButNativeIsStillDisabled(): void
    {
        $this->gateway->method('fetchTree')->willReturn($this->nativeTree());
        $this->targetsExist = true;

        $records = $this->migrator->migrate(false, false);

        $this->assertNotEmpty($records);
        $this->assertSame([], $this->written, 'existing targets must not be overwritten without --force');
        // The hand-over still happens: this module is already configured, the
        // native selectors are cleared so it takes over.
        $this->assertEqualsCanonicalizing(self::EXPECTED_DISABLED, $this->deleted);

        $statuses = array_unique(array_map(static fn (ChangeRecord $r): string => $r->status, $records));
        $this->assertEqualsCanonicalizing(
            [RecaptchaMigratorInterface::STATUS_SKIPPED_EXISTS, RecaptchaMigratorInterface::STATUS_SOURCE_DISABLED],
            $statuses
        );
    }

    public function testForceOverwritesExistingValues(): void
    {
        $this->gateway->method('fetchTree')->willReturn($this->nativeTree());
        $this->targetsExist = true;

        $records = $this->migrator->migrate(false, true);
        $byTarget = $this->indexByTarget($records);

        $this->assertSame(
            RecaptchaMigratorInterface::STATUS_OVERWRITTEN,
            $byTarget['default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key']->status
        );
        $this->assertSame('PUBKEY', $this->written['default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key']);
        $this->assertEqualsCanonicalizing(self::EXPECTED_DISABLED, $this->deleted);
    }
}
