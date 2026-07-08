<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Webapi;

use Hryvinskyi\InvisibleCaptcha\Api\Webapi\EndpointInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\Endpoint;
use PHPUnit\Framework\TestCase;

class EndpointTest extends TestCase
{
    public function testImplementsEndpointInterface(): void
    {
        $endpoint = new Endpoint('Some\\Service\\Class', 'someMethod', 'some/route/path');

        $this->assertInstanceOf(EndpointInterface::class, $endpoint);
    }

    public function testGettersReturnConstructorValues(): void
    {
        $endpoint = new Endpoint(
            'Magento\\Quote\\Api\\CouponManagementInterface',
            'savePaymentInformationAndPlaceOrder',
            'V1/carts/mine/coupons/:couponCode'
        );

        $this->assertSame('Magento\\Quote\\Api\\CouponManagementInterface', $endpoint->getServiceClass());
        $this->assertSame('savePaymentInformationAndPlaceOrder', $endpoint->getServiceMethod());
        $this->assertSame('V1/carts/mine/coupons/:couponCode', $endpoint->getRoutePath());
    }

    public function testGraphQlStyleEndpointKeepsResolveMethod(): void
    {
        $endpoint = new Endpoint(
            'Magento\\QuoteGraphQl\\Model\\Resolver\\PlaceOrder',
            'resolve',
            'placeOrder'
        );

        $this->assertSame('Magento\\QuoteGraphQl\\Model\\Resolver\\PlaceOrder', $endpoint->getServiceClass());
        $this->assertSame('resolve', $endpoint->getServiceMethod());
        $this->assertSame('placeOrder', $endpoint->getRoutePath());
    }

    public function testEmptyValuesArePreservedVerbatim(): void
    {
        $endpoint = new Endpoint('', '', '');

        $this->assertSame('', $endpoint->getServiceClass());
        $this->assertSame('', $endpoint->getServiceMethod());
        $this->assertSame('', $endpoint->getRoutePath());
    }
}
