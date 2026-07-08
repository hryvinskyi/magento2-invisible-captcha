<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Command;

use Hryvinskyi\InvisibleCaptcha\Command\Captcha;
use Hryvinskyi\InvisibleCaptcha\Model\Area;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CaptchaTest extends TestCase
{
    /** @var Manager&MockObject */
    private Manager $cacheManager;
    /** @var StoreManagerInterface&MockObject */
    private StoreManagerInterface $storeManager;
    /** @var Area&MockObject */
    private Area $area;
    /** @var ConfigResource&MockObject */
    private ConfigResource $configResource;
    private Captcha $command;

    protected function setUp(): void
    {
        $this->cacheManager = $this->createMock(Manager::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->area = $this->createMock(Area::class);
        $this->area->method('getAllowedList')->willReturn([Area::GLOBAL, Area::BACKEND, Area::FRONTEND]);
        $this->configResource = $this->createMock(ConfigResource::class);

        $this->command = new Captcha(
            $this->cacheManager,
            $this->storeManager,
            $this->area,
            $this->configResource
        );
    }

    public function testDisablesGlobalAtDefaultScopeWhenNoArgsGiven(): void
    {
        $this->configResource->expects($this->once())
            ->method('saveConfig')
            ->with(
                'hryvinskyi_invisible_captcha/general/enabled',
                '0',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        $this->cacheManager->expects($this->once())->method('flush')->with(['config']);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('disabled for area "global"', $tester->getDisplay());
    }

    public function testDisablesFrontendForWebsiteScope(): void
    {
        $this->storeManager->method('getWebsites')->willReturn([1 => $this->createMock(\Magento\Store\Api\Data\WebsiteInterface::class)]);

        $this->configResource->expects($this->once())
            ->method('saveConfig')
            ->with(
                'hryvinskyi_invisible_captcha/form_protection/frontend/enabled',
                '0',
                ScopeInterface::SCOPE_WEBSITES,
                1
            );
        $this->cacheManager->expects($this->once())->method('flush')->with(['config']);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['area' => Area::FRONTEND, '--website_id' => '1']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('disabled for area "frontend"', $tester->getDisplay());
    }

    public function testBackendAreaIgnoresWebsiteAndSavesToDefaultScope(): void
    {
        // Backend area is not website-scoped; even with a valid website it saves to default.
        $this->storeManager->method('getWebsites')->willReturn([1 => $this->createMock(\Magento\Store\Api\Data\WebsiteInterface::class)]);

        $this->configResource->expects($this->once())
            ->method('saveConfig')
            ->with(
                'hryvinskyi_invisible_captcha/form_protection/backend/enabled',
                '0',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        $this->cacheManager->expects($this->once())->method('flush')->with(['config']);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute(['area' => Area::BACKEND, '--website_id' => '1']);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testThrowsForInvalidArea(): void
    {
        $this->configResource->expects($this->never())->method('saveConfig');
        $this->cacheManager->expects($this->never())->method('flush');

        $tester = new CommandTester($this->command);

        $this->expectException(LocalizedException::class);
        $tester->execute(['area' => 'webapi']);
    }

    public function testThrowsWhenWebsiteNotFound(): void
    {
        $this->storeManager->method('getWebsites')->willReturn([]);
        $this->configResource->expects($this->never())->method('saveConfig');
        $this->cacheManager->expects($this->never())->method('flush');

        $tester = new CommandTester($this->command);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Website not found');
        $tester->execute(['area' => Area::FRONTEND, '--website_id' => '99']);
    }
}
