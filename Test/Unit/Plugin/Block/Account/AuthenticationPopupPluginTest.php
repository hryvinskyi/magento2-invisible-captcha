<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Plugin\Block\Account;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use Hryvinskyi\InvisibleCaptcha\Plugin\Block\Account\AuthenticationPopupPlugin;
use Magento\Customer\Block\Account\AuthenticationPopup;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthenticationPopupPluginTest extends TestCase
{
    /** @var ClientConfigProvider&MockObject */
    private ClientConfigProvider $clientConfigProvider;
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var Json&MockObject */
    private Json $json;
    /** @var AuthenticationPopup&MockObject */
    private AuthenticationPopup $subject;
    private AuthenticationPopupPlugin $plugin;

    protected function setUp(): void
    {
        $this->clientConfigProvider = $this->createMock(ClientConfigProvider::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->json = $this->createMock(Json::class);
        $this->subject = $this->createMock(AuthenticationPopup::class);
        $this->plugin = new AuthenticationPopupPlugin(
            $this->clientConfigProvider,
            $this->config,
            $this->json
        );

        $this->json->method('serialize')->willReturnCallback(
            static fn ($value): string => (string)json_encode($value)
        );
        $this->json->method('unserialize')->willReturnCallback(
            static fn (string $value): array => (array)json_decode($value, true)
        );
    }

    /**
     * @param array<string, mixed> $layout
     */
    private function encode(array $layout): string
    {
        return (string)json_encode($layout);
    }

    public function testAfterGetJsLayoutInjectsConfigWhenLoginEnabled(): void
    {
        $settings = ['provider' => 'recaptcha_v3'];
        $this->clientConfigProvider->method('getFormConfig')->willReturn($settings);

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->method('isFormAreaEnabled')
            ->with(ConfigInterface::AREA_FRONTEND)->willReturn(true);
        $this->config->method('isFormEnabled')
            ->with(ConfigInterface::FORM_CUSTOMER_LOGIN)->willReturn(true);

        $input = $this->encode([
            'components' => [
                'authenticationPopup' => [
                    'children' => ['invisible-captcha' => ['component' => 'x']],
                ],
            ],
        ]);

        $decoded = json_decode($this->plugin->afterGetJsLayout($this->subject, $input), true);

        $node = $decoded['components']['authenticationPopup']['children']['invisible-captcha'];
        $this->assertSame($settings, $node['config']);
    }

    public function testAfterGetJsLayoutRemovesNodeWhenLoginDisabled(): void
    {
        $this->clientConfigProvider->method('getFormConfig')->willReturn(['provider' => 'x']);
        // Master switch off short-circuits isLoginCaptchaEnabled().
        $this->config->method('isEnabled')->willReturn(false);

        $input = $this->encode([
            'components' => [
                'authenticationPopup' => [
                    'children' => ['invisible-captcha' => ['component' => 'x']],
                ],
            ],
        ]);

        $decoded = json_decode($this->plugin->afterGetJsLayout($this->subject, $input), true);

        $children = $decoded['components']['authenticationPopup']['children'];
        $this->assertArrayNotHasKey('invisible-captcha', $children);
    }
}
