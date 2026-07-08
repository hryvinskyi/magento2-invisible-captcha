<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Enablement;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Enablement\FormEnablement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormEnablementTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
    }

    public function testIsEnabledTrueWhenAllFlagsEnabled(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->method('isFormAreaEnabled')->willReturn(true);
        $this->config->method('isFormEnabled')->willReturn(true);

        $enablement = new FormEnablement(
            $this->config,
            ConfigInterface::AREA_FRONTEND,
            ConfigInterface::FORM_CUSTOMER_LOGIN
        );

        $this->assertTrue($enablement->isEnabled());
    }

    public function testIsEnabledPassesAreaFormAndScopeToConfig(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->expects($this->once())
            ->method('isFormAreaEnabled')
            ->with(ConfigInterface::AREA_ADMINHTML, 'store_code')
            ->willReturn(true);
        $this->config->expects($this->once())
            ->method('isFormEnabled')
            ->with(ConfigInterface::FORM_ADMIN_LOGIN, 'store_code')
            ->willReturn(true);

        $enablement = new FormEnablement(
            $this->config,
            ConfigInterface::AREA_ADMINHTML,
            ConfigInterface::FORM_ADMIN_LOGIN
        );

        $this->assertTrue($enablement->isEnabled('store_code'));
    }

    /**
     * @param array{0:bool,1:bool,2:bool,3:bool} $flags
     */
    #[DataProvider('disabledFlagsProvider')]
    public function testIsEnabledFalseWhenAnyFlagDisabled(array $flags): void
    {
        [$enabled, $formProtection, $area, $form] = $flags;
        $this->config->method('isEnabled')->willReturn($enabled);
        $this->config->method('isFormProtectionEnabled')->willReturn($formProtection);
        $this->config->method('isFormAreaEnabled')->willReturn($area);
        $this->config->method('isFormEnabled')->willReturn($form);

        $enablement = new FormEnablement(
            $this->config,
            ConfigInterface::AREA_FRONTEND,
            ConfigInterface::FORM_CONTACT
        );

        $this->assertFalse($enablement->isEnabled());
    }

    /**
     * @return array<string, array{0: array{0:bool,1:bool,2:bool,3:bool}}>
     */
    public static function disabledFlagsProvider(): array
    {
        return [
            'module disabled' => [[false, true, true, true]],
            'form protection disabled' => [[true, false, true, true]],
            'area disabled' => [[true, true, false, true]],
            'form disabled' => [[true, true, true, false]],
            'all disabled' => [[false, false, false, false]],
        ];
    }

    #[DataProvider('validAreaProvider')]
    public function testConstructorAcceptsValidAreas(string $area): void
    {
        $enablement = new FormEnablement($this->config, $area, ConfigInterface::FORM_NEWSLETTER);

        $this->assertInstanceOf(FormEnablement::class, $enablement);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validAreaProvider(): array
    {
        return [
            'frontend' => [ConfigInterface::AREA_FRONTEND],
            'adminhtml' => [ConfigInterface::AREA_ADMINHTML],
        ];
    }

    public function testConstructorRejectsInvalidArea(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FormEnablement($this->config, 'webapi', ConfigInterface::FORM_CONTACT);
    }
}
