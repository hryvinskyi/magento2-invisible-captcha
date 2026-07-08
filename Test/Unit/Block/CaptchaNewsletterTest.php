<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Block;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Block\CaptchaNewsletter;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaNewsletterTest extends TestCase
{
    private const COMPONENT_KEY = 'invisible-captcha-newsletter';

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
    private function createBlock(array $jsLayout = []): CaptchaNewsletter
    {
        return $this->objectManager->getObject(
            CaptchaNewsletter::class,
            [
                'config' => $this->config,
                'clientConfigProvider' => $this->clientConfigProvider,
                'json' => $this->json,
                'data' => ['jsLayout' => $jsLayout],
            ]
        );
    }

    public function testGetJsLayoutKeepsNewsletterKeyAndInjectsConfigWhenOn(): void
    {
        $formConfig = ['provider' => 'recaptcha_v3'];
        $this->config->method('isEnabled')->willReturn(true);
        $this->clientConfigProvider->method('getFormConfig')->willReturn($formConfig);

        $block = $this->createBlock(
            ['components' => [self::COMPONENT_KEY => ['component' => 'newsletter-js']]]
        );

        $decoded = json_decode($block->getJsLayout(), true);

        // The fixed key is preserved (no per-instance scope relocation here).
        $this->assertArrayHasKey(self::COMPONENT_KEY, $decoded['components']);
        $this->assertSame($formConfig, $decoded['components'][self::COMPONENT_KEY]['config']);
        $this->assertSame('newsletter-js', $decoded['components'][self::COMPONENT_KEY]['component']);
    }

    public function testGetJsLayoutRemovesNewsletterComponentWhenOff(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->clientConfigProvider->expects($this->never())->method('getFormConfig');

        $block = $this->createBlock(
            ['components' => [self::COMPONENT_KEY => ['component' => 'newsletter-js']]]
        );

        $decoded = json_decode($block->getJsLayout(), true);

        $this->assertArrayNotHasKey(self::COMPONENT_KEY, $decoded['components']);
    }
}
