<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Webapi;

use Hryvinskyi\InvisibleCaptcha\Api\Webapi\EndpointInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Webapi\WebapiConfigProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\CompositeConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositeConfigProviderTest extends TestCase
{
    /** @var EndpointInterface&MockObject */
    private EndpointInterface $endpoint;

    protected function setUp(): void
    {
        $this->endpoint = $this->createMock(EndpointInterface::class);
    }

    public function testReturnsNullWhenNoProvidersRegistered(): void
    {
        $composite = new CompositeConfigProvider([]);

        $this->assertNull($composite->getFormKeyFor($this->endpoint));
    }

    public function testReturnsNullWhenAllProvidersReturnNull(): void
    {
        $first = $this->provider(null);
        $second = $this->provider(null);

        $composite = new CompositeConfigProvider([$first, $second]);

        $this->assertNull($composite->getFormKeyFor($this->endpoint));
    }

    public function testReturnsFirstNonNullFormKey(): void
    {
        $first = $this->provider(null);
        $second = $this->provider('place_order');
        // A third provider must never be consulted once a match is found.
        $third = $this->createMock(WebapiConfigProviderInterface::class);
        $third->expects($this->never())->method('getFormKeyFor');

        $composite = new CompositeConfigProvider([$first, $second, $third]);

        $this->assertSame('place_order', $composite->getFormKeyFor($this->endpoint));
    }

    public function testStopsAtFirstMatchingProvider(): void
    {
        $first = $this->provider('coupon_code');
        // Once the first provider matches, the rest are skipped.
        $second = $this->createMock(WebapiConfigProviderInterface::class);
        $second->expects($this->never())->method('getFormKeyFor');

        $composite = new CompositeConfigProvider([$first, $second]);

        $this->assertSame('coupon_code', $composite->getFormKeyFor($this->endpoint));
    }

    /**
     * @return WebapiConfigProviderInterface&MockObject
     */
    private function provider(?string $formKey): WebapiConfigProviderInterface
    {
        $provider = $this->createMock(WebapiConfigProviderInterface::class);
        $provider->method('getFormKeyFor')->willReturn($formKey);

        return $provider;
    }
}
