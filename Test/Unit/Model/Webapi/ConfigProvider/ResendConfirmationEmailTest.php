<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Webapi\ConfigProvider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\ConfigProvider\ResendConfirmationEmail;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\Endpoint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResendConfirmationEmailTest extends TestCase
{
    private const RESOLVER = 'Magento\\CustomerGraphQl\\Model\\Resolver\\ResendConfirmationEmail';

    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private ResendConfirmationEmail $provider;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->provider = new ResendConfirmationEmail($this->config);
    }

    public function testReturnsFormKeyWhenResolverMatchesAndEnabled(): void
    {
        $this->configureEnabled();

        $result = $this->provider->getFormKeyFor(new Endpoint(self::RESOLVER, 'resolve', 'resendConfirmationEmail'));

        $this->assertSame(ConfigInterface::FORM_RESEND_CONFIRMATION_EMAIL, $result);
    }

    public function testReturnsNullWhenResolverMatchesButDisabled(): void
    {
        $this->config->method('isEnabled')->willReturn(false);

        $result = $this->provider->getFormKeyFor(new Endpoint(self::RESOLVER, 'resolve', 'resendConfirmationEmail'));

        $this->assertNull($result);
    }

    public function testReturnsNullWhenResolverDoesNotMatch(): void
    {
        $this->config->expects($this->never())->method('isEnabled');

        $result = $this->provider->getFormKeyFor(
            new Endpoint('Magento\\CustomerGraphQl\\Model\\Resolver\\CreateCustomer', 'resolve', 'createCustomerV2')
        );

        $this->assertNull($result);
    }

    private function configureEnabled(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->method('isFormAreaEnabled')
            ->with(ConfigInterface::AREA_FRONTEND)
            ->willReturn(true);
        $this->config->method('isFormEnabled')
            ->with(ConfigInterface::FORM_RESEND_CONFIRMATION_EMAIL)
            ->willReturn(true);
    }
}
