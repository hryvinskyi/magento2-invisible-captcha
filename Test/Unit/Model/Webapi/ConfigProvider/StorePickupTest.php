<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Webapi\ConfigProvider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\ConfigProvider\StorePickup;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\Endpoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StorePickupTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private StorePickup $provider;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->provider = new StorePickup($this->config);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function matchingEndpointProvider(): array
    {
        return [
            'rest in-store-pickup place order' => [
                'Magento\\InStorePickupQuote\\Api\\PaymentInformationManagementInterface',
                'savePaymentInformationAndPlaceOrder',
                'V1/in-store-pickup/carts/mine/payment-information',
            ],
            'graphql set payment and place order' => [
                'Magento\\InStorePickupQuoteGraphQl\\Model\\Resolver\\SetPaymentMethodAndPlaceOrder',
                'resolve',
                'setPaymentMethodAndPlaceOrder',
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function nonMatchingEndpointProvider(): array
    {
        return [
            'right class wrong method' => [
                'Magento\\InStorePickupQuote\\Api\\PaymentInformationManagementInterface',
                'getPaymentInformation',
                'V1/in-store-pickup/carts/mine/payment-information',
            ],
            'unrelated endpoint' => [
                'Magento\\Checkout\\Api\\PaymentInformationManagementInterface',
                'savePaymentInformationAndPlaceOrder',
                'V1/carts/mine/payment-information',
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

        $this->assertSame(ConfigInterface::FORM_STORE_PICKUP, $result);
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

    #[DataProvider('nonMatchingEndpointProvider')]
    public function testReturnsNullWhenEndpointDoesNotMatch(
        string $class,
        string $method,
        string $name
    ): void {
        $this->config->expects($this->never())->method('isEnabled');

        $result = $this->provider->getFormKeyFor(new Endpoint($class, $method, $name));

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
            ->with(ConfigInterface::FORM_STORE_PICKUP)
            ->willReturn(true);
    }
}
