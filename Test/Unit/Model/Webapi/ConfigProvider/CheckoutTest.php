<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Webapi\ConfigProvider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\ConfigProvider\Checkout;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\Endpoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private Checkout $provider;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->provider = new Checkout($this->config);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function matchingEndpointProvider(): array
    {
        return [
            'rest place order' => [
                'Magento\\Checkout\\Api\\PaymentInformationManagementInterface',
                'savePaymentInformationAndPlaceOrder',
                'V1/carts/mine/payment-information',
            ],
            'graphql set payment and place order' => [
                'Magento\\QuoteGraphQl\\Model\\Resolver\\SetPaymentAndPlaceOrder',
                'resolve',
                'setPaymentMethodAndPlaceOrder',
            ],
            'graphql place order' => [
                'Magento\\QuoteGraphQl\\Model\\Resolver\\PlaceOrder',
                'resolve',
                'placeOrder',
            ],
        ];
    }

    #[DataProvider('matchingEndpointProvider')]
    public function testReturnsFormKeyWhenEndpointMatchesAndEnabled(
        string $class,
        string $method,
        string $name
    ): void {
        $this->configureEnabled();

        $result = $this->provider->getFormKeyFor(new Endpoint($class, $method, $name));

        $this->assertSame(ConfigInterface::FORM_PLACE_ORDER, $result);
    }

    #[DataProvider('matchingEndpointProvider')]
    public function testReturnsNullWhenEndpointMatchesButDisabled(
        string $class,
        string $method,
        string $name
    ): void {
        $this->config->method('isEnabled')->willReturn(false);

        $result = $this->provider->getFormKeyFor(new Endpoint($class, $method, $name));

        $this->assertNull($result);
    }

    public function testReturnsNullWhenEndpointDoesNotMatch(): void
    {
        // The config gate is never consulted for an unrelated endpoint.
        $this->config->expects($this->never())->method('isEnabled');

        $result = $this->provider->getFormKeyFor(
            new Endpoint('Magento\\Customer\\Api\\AccountManagementInterface', 'createAccount', 'V1/customers')
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
            ->with(ConfigInterface::FORM_PLACE_ORDER)
            ->willReturn(true);
    }
}
