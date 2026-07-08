<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl\Simple;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    /** @var UrlInterface&MockObject */
    private UrlInterface $url;

    protected function setUp(): void
    {
        $this->url = $this->createMock(UrlInterface::class);
    }

    public function testBuildsUrlFromPathAndParams(): void
    {
        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('checkout/cart/index', ['_secure' => true])
            ->willReturn('https://example.com/checkout/cart');

        $provider = new Simple($this->url, 'checkout/cart/index', ['_secure' => true]);

        $this->assertSame('https://example.com/checkout/cart', $provider->getRedirectUrl());
    }

    public function testBuildsUrlWithNullParams(): void
    {
        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('customer/account/login', null)
            ->willReturn('https://example.com/customer/account/login');

        $provider = new Simple($this->url, 'customer/account/login');

        $this->assertSame('https://example.com/customer/account/login', $provider->getRedirectUrl());
    }
}
