<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Observer;

use Hryvinskyi\InvisibleCaptcha\Model\Http\CaptchaContext;
use Hryvinskyi\InvisibleCaptcha\Model\RequestChecker;
use Hryvinskyi\InvisibleCaptcha\Observer\SetCaptchaHttpContext;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetCaptchaHttpContextTest extends TestCase
{
    /** @var RequestChecker&MockObject */
    private RequestChecker $requestChecker;
    /** @var CaptchaContext&MockObject */
    private CaptchaContext $captchaContext;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private SetCaptchaHttpContext $observer;

    protected function setUp(): void
    {
        $this->requestChecker = $this->createMock(RequestChecker::class);
        $this->captchaContext = $this->createMock(CaptchaContext::class);
        $this->request = $this->createMock(HttpRequest::class);

        $this->observer = new SetCaptchaHttpContext(
            $this->requestChecker,
            $this->captchaContext,
            $this->request
        );
    }

    public function testNoOpWhenNotConfigured(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(false);

        $this->request->expects($this->never())->method('getPathInfo');
        $this->captchaContext->expects($this->never())->method('setFromCookie');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testSkipsVerifyEndpoint(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(true);
        // Verify endpoint manages its own context inline; surrounding slashes are trimmed.
        $this->request->method('getPathInfo')->willReturn('/invisiblecaptcha/verify/');

        $this->captchaContext->expects($this->never())->method('setFromCookie');

        $this->observer->execute($this->createMock(Observer::class));
    }

    public function testSetsContextFromCookieForOtherPaths(): void
    {
        $this->requestChecker->method('isConfigured')->willReturn(true);
        $this->request->method('getPathInfo')->willReturn('/customer/account/login');

        $this->captchaContext->expects($this->once())->method('setFromCookie');

        $this->observer->execute($this->createMock(Observer::class));
    }
}
