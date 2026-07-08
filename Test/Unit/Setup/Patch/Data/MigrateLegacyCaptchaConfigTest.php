<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Setup\Patch\Data;

use Hryvinskyi\InvisibleCaptcha\Setup\Patch\Data\MigrateLegacyCaptchaConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MigrateLegacyCaptchaConfigTest extends TestCase
{
    /** @var ModuleDataSetupInterface&MockObject */
    private ModuleDataSetupInterface $moduleDataSetup;
    /** @var ScopeConfigInterface&MockObject */
    private ScopeConfigInterface $scopeConfig;
    /** @var EncryptorInterface&MockObject */
    private EncryptorInterface $encryptor;
    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;
    /** @var Select&MockObject */
    private Select $select;
    private MigrateLegacyCaptchaConfig $patch;

    protected function setUp(): void
    {
        $this->select = $this->createMock(Select::class);
        $this->select->method('from')->willReturnSelf();
        $this->select->method('where')->willReturnSelf();

        $this->connection = $this->createMock(AdapterInterface::class);
        $this->connection->method('select')->willReturn($this->select);

        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->moduleDataSetup->method('getConnection')->willReturn($this->connection);
        $this->moduleDataSetup->method('getTable')->willReturnArgument(0);

        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor = $this->createMock(EncryptorInterface::class);

        $this->patch = new MigrateLegacyCaptchaConfig(
            $this->moduleDataSetup,
            $this->scopeConfig,
            $this->encryptor
        );
    }

    public function testIsDataPatch(): void
    {
        $this->assertInstanceOf(DataPatchInterface::class, $this->patch);
    }

    public function testGetDependenciesIsEmpty(): void
    {
        $this->assertSame([], MigrateLegacyCaptchaConfig::getDependencies());
    }

    public function testGetAliasesIsEmpty(): void
    {
        $this->assertSame([], $this->patch->getAliases());
    }

    public function testApplyWithNoLegacyDataWrapsInSetupTransactionAndWritesNothing(): void
    {
        // No legacy rows exist anywhere.
        $this->connection->method('fetchAll')->willReturn([]);
        $this->connection->method('fetchOne')->willReturn(false);

        $this->moduleDataSetup->expects($this->once())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetup->expects($this->once())->method('endSetup')->willReturnSelf();
        // Nothing to migrate => no inserts and no encryption.
        $this->connection->expects($this->never())->method('insert');
        $this->encryptor->expects($this->never())->method('encrypt');

        $result = $this->patch->apply();

        $this->assertSame($this->patch, $result);
    }

    public function testApplyMigratesAndEncryptsLegacyPlaintextSecret(): void
    {
        // Every legacy path resolves to a single stored row.
        $this->connection->method('fetchAll')->willReturn([
            ['scope' => 'default', 'scope_id' => 0, 'value' => 'legacy-value'],
        ]);
        // The "already exists at new path" probe never finds a target row.
        $this->connection->method('fetchOne')->willReturn(false);

        // The legacy reCAPTCHA v3 secret is copied with encryption.
        $this->encryptor->expects($this->atLeastOnce())
            ->method('encrypt')
            ->with('legacy-value')
            ->willReturn('encrypted-value');

        $this->moduleDataSetup->expects($this->once())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetup->expects($this->once())->method('endSetup')->willReturnSelf();
        $this->connection->expects($this->atLeastOnce())->method('insert');

        $result = $this->patch->apply();

        $this->assertSame($this->patch, $result);
    }
}
