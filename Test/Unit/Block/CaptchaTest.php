<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Block;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Block\Captcha;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var ClientConfigProvider&MockObject */
    private ClientConfigProvider $clientConfigProvider;
    /** @var Json&MockObject */
    private Json $json;
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->clientConfigProvider = $this->createMock(ClientConfigProvider::class);
        $this->json = $this->createMock(Json::class);
        $this->objectManager = new ObjectManager($this);

        // Json mock behaves like the real serializer for the round-trip used by the block.
        $this->json->method('serialize')->willReturnCallback(
            static fn ($value): string => (string)json_encode($value)
        );
        $this->json->method('unserialize')->willReturnCallback(
            static fn (string $value): array => (array)json_decode($value, true)
        );
    }

    /**
     * @param array<string, mixed> $jsLayout
     */
    private function createBlock(array $jsLayout = []): Captcha
    {
        return $this->objectManager->getObject(
            Captcha::class,
            [
                'config' => $this->config,
                'clientConfigProvider' => $this->clientConfigProvider,
                'json' => $this->json,
                'data' => ['jsLayout' => $jsLayout],
            ]
        );
    }

    public function testToHtmlReturnsEmptyStringWhenModuleOff(): void
    {
        $this->config->method('isEnabled')->willReturn(false);

        $block = $this->createBlock();

        $this->assertSame('', $block->toHtml());
    }

    public function testGetJsLayoutRenamesInvisibleCaptchaToScopeAndInjectsConfigWhenOn(): void
    {
        $formConfig = ['provider' => 'turnstile', 'siteKey' => 'abc'];
        $this->config->method('isEnabled')->willReturn(true);
        $this->clientConfigProvider->method('getFormConfig')->willReturn($formConfig);

        $component = [
            'component' => 'Hryvinskyi_InvisibleCaptcha/js/invisible-captcha',
            'action' => 'contact',
        ];
        $block = $this->createBlock(['components' => ['invisible-captcha' => $component]]);
        $scope = $block->getScope();

        $decoded = json_decode($block->getJsLayout(), true);

        // Original generic key is gone, relocated under the per-instance scope.
        $this->assertArrayNotHasKey('invisible-captcha', $decoded['components']);
        $this->assertArrayHasKey($scope, $decoded['components']);
        $this->assertSame($formConfig, $decoded['components'][$scope]['config']);
        $this->assertSame($component['component'], $decoded['components'][$scope]['component']);
    }

    public function testGetJsLayoutRemovesScopeComponentWhenModuleOff(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        // getFormConfig must never be consulted when the module is off.
        $this->clientConfigProvider->expects($this->never())->method('getFormConfig');

        $block = $this->createBlock(
            ['components' => ['invisible-captcha' => ['component' => 'x']]]
        );
        $scope = $block->getScope();

        $decoded = json_decode($block->getJsLayout(), true);

        $this->assertArrayNotHasKey('invisible-captcha', $decoded['components']);
        $this->assertArrayNotHasKey($scope, $decoded['components']);
    }

    public function testIsHideBadgeAndHideBadgeTextFromFormConfig(): void
    {
        $this->clientConfigProvider->method('getFormConfig')->willReturn(
            ['hideBadge' => true, 'hideBadgeText' => 'Protected by captcha']
        );

        $block = $this->createBlock();

        $this->assertTrue($block->isHideBadge());
        $this->assertSame('Protected by captcha', $block->getHideBadgeText());
    }

    public function testIsHideBadgeDefaultsWhenFormConfigEmpty(): void
    {
        $this->clientConfigProvider->method('getFormConfig')->willReturn([]);

        $block = $this->createBlock();

        $this->assertFalse($block->isHideBadge());
        $this->assertSame('', $block->getHideBadgeText());
    }

    /**
     * The captcha template emits the parse-time "disable submit" script when this
     * flag is on; it mirrors the config value 1:1.
     *
     * @param bool $configValue
     * @return void
     */
    #[DataProvider('disabledSubmitProvider')]
    public function testIsDisabledSubmitFormReflectsConfig(bool $configValue): void
    {
        $this->config->method('isDisableSubmitForm')->willReturn($configValue);

        $this->assertSame($configValue, $this->createBlock()->isDisabledSubmitForm());
    }

    /**
     * @return array<string, array{0: bool}>
     */
    public static function disabledSubmitProvider(): array
    {
        return [
            'enabled' => [true],
            'disabled' => [false],
        ];
    }
}
