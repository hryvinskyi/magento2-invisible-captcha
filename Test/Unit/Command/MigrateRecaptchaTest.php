<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Command;

use Hryvinskyi\InvisibleCaptcha\Api\Migration\RecaptchaMigratorInterface;
use Hryvinskyi\InvisibleCaptcha\Command\MigrateRecaptcha;
use Hryvinskyi\InvisibleCaptcha\Model\Migration\ChangeRecord;
use Magento\Framework\App\Cache\Manager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateRecaptchaTest extends TestCase
{
    /** @var RecaptchaMigratorInterface&MockObject */
    private RecaptchaMigratorInterface $migrator;
    /** @var Manager&MockObject */
    private Manager $cacheManager;
    private MigrateRecaptcha $command;

    protected function setUp(): void
    {
        $this->migrator = $this->createMock(RecaptchaMigratorInterface::class);
        $this->cacheManager = $this->createMock(Manager::class);
        $this->command = new MigrateRecaptcha($this->migrator, $this->cacheManager);
    }

    /**
     * @return ChangeRecord[]
     */
    private function sampleRecords(): array
    {
        return [
            new ChangeRecord(
                'recaptcha_frontend/type_recaptcha_v3/public_key',
                'hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key',
                'default',
                0,
                'PUBKEY',
                RecaptchaMigratorInterface::STATUS_MIGRATED
            ),
            new ChangeRecord(
                null,
                'hryvinskyi_invisible_captcha/general/active_provider',
                'default',
                0,
                'recaptcha_v3',
                RecaptchaMigratorInterface::STATUS_SKIPPED_EXISTS
            ),
            new ChangeRecord(
                'recaptcha_frontend/type_for/contact',
                'recaptcha_frontend/type_for/contact',
                'default',
                0,
                '',
                RecaptchaMigratorInterface::STATUS_SOURCE_DISABLED
            ),
        ];
    }

    public function testAppliesMigrationAndFlushesCache(): void
    {
        $this->migrator->expects($this->once())->method('migrate')->with(false, false)->willReturn($this->sampleRecords());
        $this->cacheManager->expects($this->once())->method('flush')->with(['config']);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('1 written, 0 overwritten, 1 skipped (already set), 1 native selectors disabled', $display);
        $this->assertStringContainsString('Config cache flushed', $display);
    }

    public function testDryRunDoesNotFlushCache(): void
    {
        $this->migrator->expects($this->once())->method('migrate')->with(true, false)->willReturn($this->sampleRecords());
        $this->cacheManager->expects($this->never())->method('flush');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('no changes will be written', $display);
        $this->assertStringContainsString('Re-run without --dry-run', $display);
    }

    public function testForceIsForwardedToMigrator(): void
    {
        $this->migrator->expects($this->once())->method('migrate')->with(false, true)->willReturn($this->sampleRecords());

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testNothingToMigrate(): void
    {
        $this->migrator->expects($this->once())->method('migrate')->willReturn([]);
        $this->cacheManager->expects($this->never())->method('flush');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('nothing to migrate', $tester->getDisplay());
    }

    public function testAllSkippedDoesNotFlushCache(): void
    {
        $records = [
            new ChangeRecord(
                null,
                'hryvinskyi_invisible_captcha/general/active_provider',
                'default',
                0,
                'recaptcha_v3',
                RecaptchaMigratorInterface::STATUS_SKIPPED_EXISTS
            ),
        ];
        $this->migrator->expects($this->once())->method('migrate')->willReturn($records);
        $this->cacheManager->expects($this->never())->method('flush');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('0 written, 0 overwritten, 1 skipped (already set), 0 native selectors disabled', $tester->getDisplay());
    }

    public function testDisabledOnlyRunStillFlushesCache(): void
    {
        // Clearing native selectors changes effective config → cache must flush.
        $records = [
            new ChangeRecord(
                'recaptcha_frontend/type_for/contact',
                'recaptcha_frontend/type_for/contact',
                'default',
                0,
                '',
                RecaptchaMigratorInterface::STATUS_SOURCE_DISABLED
            ),
        ];
        $this->migrator->expects($this->once())->method('migrate')->willReturn($records);
        $this->cacheManager->expects($this->once())->method('flush')->with(['config']);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('1 native selectors disabled', $tester->getDisplay());
    }
}
