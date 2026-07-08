<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Failure\RedirectUrl;

use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure\RedirectUrl\BeforeAuthUrl;
use Magento\Customer\Model\Url;
use Magento\Framework\Session\SessionManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BeforeAuthUrlTest extends TestCase
{
    /** @var SessionManagerInterface&MockObject */
    private SessionManagerInterface $sessionManager;
    /** @var Url&MockObject */
    private Url $url;
    private BeforeAuthUrl $provider;

    protected function setUp(): void
    {
        // getBeforeAuthUrl() is a magic accessor on SessionManager; declare it on the mock.
        $this->sessionManager = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getBeforeAuthUrl'])
            ->getMock();
        $this->url = $this->createMock(Url::class);
        $this->provider = new BeforeAuthUrl($this->sessionManager, $this->url);
    }

    public function testReturnsBeforeAuthUrlWhenSet(): void
    {
        $this->sessionManager->method('getBeforeAuthUrl')->willReturn('https://example.com/before-auth');
        $this->url->expects($this->never())->method('getLoginUrl');

        $this->assertSame('https://example.com/before-auth', $this->provider->getRedirectUrl());
    }

    public function testFallsBackToLoginUrlWhenBeforeAuthEmpty(): void
    {
        $this->sessionManager->method('getBeforeAuthUrl')->willReturn(null);
        $this->url->expects($this->once())
            ->method('getLoginUrl')
            ->willReturn('https://example.com/customer/account/login');

        $this->assertSame('https://example.com/customer/account/login', $this->provider->getRedirectUrl());
    }

    public function testFallsBackToLoginUrlWhenBeforeAuthEmptyString(): void
    {
        $this->sessionManager->method('getBeforeAuthUrl')->willReturn('');
        $this->url->method('getLoginUrl')->willReturn('https://example.com/login');

        $this->assertSame('https://example.com/login', $this->provider->getRedirectUrl());
    }
}
