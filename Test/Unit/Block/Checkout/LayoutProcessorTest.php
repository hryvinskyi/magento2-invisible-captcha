<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Block\Checkout;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Block\Checkout\LayoutProcessor;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LayoutProcessorTest extends TestCase
{
    /** @var ClientConfigProvider&MockObject */
    private ClientConfigProvider $clientConfigProvider;
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private LayoutProcessor $processor;

    protected function setUp(): void
    {
        $this->clientConfigProvider = $this->createMock(ClientConfigProvider::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->processor = new LayoutProcessor($this->clientConfigProvider, $this->config);
    }

    /**
     * Build the minimal checkout jsLayout skeleton with both login captcha nodes present.
     *
     * @return array<string, mixed>
     */
    private static function baseLayout(): array
    {
        return [
            'components' => [
                'checkout' => [
                    'children' => [
                        'steps' => [
                            'children' => [
                                'shipping-step' => [
                                    'children' => [
                                        'shippingAddress' => [
                                            'children' => [
                                                'customer-email' => [
                                                    'children' => [
                                                        'invisible-captcha' => ['component' => 'x'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'authentication' => [
                            'children' => [
                                'invisible-captcha' => ['component' => 'y'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testInjectsConfigIntoLoginNodesWhenEnabled(): void
    {
        $settings = ['provider' => 'turnstile', 'siteKey' => 'k'];
        $this->clientConfigProvider->method('getFormConfig')->willReturn($settings);

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->method('isFormAreaEnabled')
            ->with(ConfigInterface::AREA_FRONTEND)->willReturn(true);
        $this->config->method('isFormEnabled')
            ->with(ConfigInterface::FORM_CUSTOMER_LOGIN)->willReturn(true);

        $result = $this->processor->process(self::baseLayout());

        $email = $result['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['customer-email']['children']['invisible-captcha'];
        $auth = $result['components']['checkout']['children']['authentication']['children']['invisible-captcha'];

        $this->assertSame($settings, $email['config']);
        $this->assertSame($settings, $auth['config']);
    }

    public function testRemovesLoginNodesWhenLoginCaptchaDisabled(): void
    {
        $this->clientConfigProvider->method('getFormConfig')->willReturn(['provider' => 'turnstile']);
        // Master switch off short-circuits isLoginCaptchaEnabled().
        $this->config->method('isEnabled')->willReturn(false);

        $result = $this->processor->process(self::baseLayout());

        $emailChildren = $result['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['customer-email']['children'];
        $authChildren = $result['components']['checkout']['children']['authentication']['children'];

        $this->assertArrayNotHasKey('invisible-captcha', $emailChildren);
        $this->assertArrayNotHasKey('invisible-captcha', $authChildren);
    }
}
