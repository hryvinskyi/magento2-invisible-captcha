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
    private RecaptchaMigrator $migrator;

    /** @var array<string, string> Captured write() calls keyed "scope|id|path". */
    private array $written = [];
    private bool $targetsExist = false;

    protected function setUp(): void
    {
        $this->written = [];
        $this->targetsExist = false;

        $this->gateway = $this->createMock(CoreConfigGatewayInterface::class);
        $this->gateway->method('exists')->willReturnCallback(fn (): bool => $this->targetsExist);
        $this->gateway->method('write')->willReturnCallback(
            function (string $path, string $value, string $scope, int $scopeId): void {
                $this->written[$scope . '|' . $scopeId . '|' . $path] = $value;
            }
        );

        $this->migrator = new RecaptchaMigrator($this->gateway);
    }

    /**
     * Native config indexed as the gateway returns it: [scope][scopeId][path] => value.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function nativeTree(): array
    {
        return [
            'default' => [
                0 => [
                    'recaptcha_frontend/type_recaptcha_v3/public_key' => 'PUBKEY',
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

        // Credentials copied to provider tree.
        $this->assertSame('PUBKEY', $this->written['default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key']);
        $this->assertSame('BE_PUB', $this->written['default|0|hryvinskyi_invisible_captcha/providers/recaptcha_v2_invisible/site_key']);

        // Secret is masked in the change log but copied verbatim to the DB.
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
    }

    public function testReturnsEmptyWhenNoNativeConfig(): void
    {
        $this->gateway->method('fetchTree')->willReturn([]);

        $this->assertSame([], $this->migrator->migrate(false, false));
        $this->assertSame([], $this->written);
    }

    public function testDryRunWritesNothingButReportsChanges(): void
    {
        $this->gateway->method('fetchTree')->willReturn($this->nativeTree());

        $records = $this->migrator->migrate(true, false);

        $this->assertNotEmpty($records);
        $this->assertSame([], $this->written, 'dry run must not persist anything');
        foreach ($records as $record) {
            $this->assertSame(RecaptchaMigratorInterface::STATUS_MIGRATED, $record->status);
        }
    }

    public function testExistingValuesAreSkippedWithoutForce(): void
    {
        $this->gateway->method('fetchTree')->willReturn($this->nativeTree());
        $this->targetsExist = true;

        $records = $this->migrator->migrate(false, false);

        $this->assertNotEmpty($records);
        $this->assertSame([], $this->written, 'existing targets must not be overwritten without --force');
        foreach ($records as $record) {
            $this->assertSame(RecaptchaMigratorInterface::STATUS_SKIPPED_EXISTS, $record->status);
        }
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
    }
}
