<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl\BeforeAuthUrl;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BeforeAuthUrlTest extends TestCase
{
    /** @var CustomerSession&MockObject */
    private CustomerSession $customerSession;
    /** @var Url&MockObject */
    private Url $url;
    private BeforeAuthUrl $provider;

    protected function setUp(): void
    {
        // getBeforeAuthUrl() is a magic data accessor on the customer session
        // (SessionManager::__call); declare it on the concrete-class mock.
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBeforeAuthUrl'])
            ->getMock();
        $this->url = $this->createMock(Url::class);
        $this->provider = new BeforeAuthUrl($this->customerSession, $this->url);
    }

    public function testReturnsBeforeAuthUrlWhenSet(): void
    {
        $this->customerSession->method('getBeforeAuthUrl')->willReturn('https://example.com/before-auth');
        $this->url->expects($this->never())->method('getLoginUrl');

        $this->assertSame('https://example.com/before-auth', $this->provider->getRedirectUrl());
    }

    public function testFallsBackToLoginUrlWhenBeforeAuthEmpty(): void
    {
        $this->customerSession->method('getBeforeAuthUrl')->willReturn(null);
        $this->url->expects($this->once())
            ->method('getLoginUrl')
            ->willReturn('https://example.com/customer/account/login');

        $this->assertSame('https://example.com/customer/account/login', $this->provider->getRedirectUrl());
    }

    public function testFallsBackToLoginUrlWhenBeforeAuthEmptyString(): void
    {
        $this->customerSession->method('getBeforeAuthUrl')->willReturn('');
        $this->url->method('getLoginUrl')->willReturn('https://example.com/login');

        $this->assertSame('https://example.com/login', $this->provider->getRedirectUrl());
    }
}
