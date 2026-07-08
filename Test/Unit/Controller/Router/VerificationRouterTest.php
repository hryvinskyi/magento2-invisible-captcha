<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Controller\Router;

use Hryvinskyi\InvisibleCaptcha\Controller\Router\VerificationRouter;
use Hryvinskyi\InvisibleCaptcha\Controller\Router\Verify;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VerificationRouterTest extends TestCase
{
    /** @var Verify&MockObject */
    private Verify $verifyAction;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private VerificationRouter $router;

    protected function setUp(): void
    {
        $this->verifyAction = $this->createMock(Verify::class);
        $this->request = $this->createMock(HttpRequest::class);

        $this->router = new VerificationRouter($this->verifyAction);
    }

    #[DataProvider('offPathProvider')]
    public function testReturnsNullForNonVerifyPath(string $pathInfo): void
    {
        $this->request->method('getPathInfo')->willReturn($pathInfo);

        // Routing details must never be mutated when the path does not match.
        $this->request->expects($this->never())->method('setModuleName');

        $this->assertNull($this->router->match($this->request));
    }

    public static function offPathProvider(): array
    {
        return [
            'unrelated path' => ['/customer/account/login'],
            'similar but different' => ['/invisiblecaptcha/other'],
            'root' => ['/'],
        ];
    }

    public function testReturnsNullForNonPostMethod(): void
    {
        $this->request->method('getPathInfo')->willReturn('/invisiblecaptcha/verify');
        $this->request->method('getMethod')->willReturn('GET');

        $this->request->expects($this->never())->method('setModuleName');

        $this->assertNull($this->router->match($this->request));
    }

    public function testMatchesPostRequestAndConfiguresRouting(): void
    {
        // Trailing slash is tolerated by the trim().
        $this->request->method('getPathInfo')->willReturn('/invisiblecaptcha/verify/');
        $this->request->method('getMethod')->willReturn('POST');

        $this->request->expects($this->once())->method('setModuleName')
            ->with('invisiblecaptcha')->willReturnSelf();
        $this->request->expects($this->once())->method('setControllerName')
            ->with('verification')->willReturnSelf();
        $this->request->expects($this->once())->method('setActionName')
            ->with('verify')->willReturnSelf();
        $this->request->expects($this->once())->method('setDispatched')
            ->with(true)->willReturnSelf();

        $this->assertSame($this->verifyAction, $this->router->match($this->request));
    }

    public function testMatchesPostCaseInsensitively(): void
    {
        $this->request->method('getPathInfo')->willReturn('/invisiblecaptcha/verify');
        $this->request->method('getMethod')->willReturn('post');
        $this->request->method('setModuleName')->willReturnSelf();
        $this->request->method('setControllerName')->willReturnSelf();
        $this->request->method('setActionName')->willReturnSelf();
        $this->request->method('setDispatched')->willReturnSelf();

        $this->assertSame($this->verifyAction, $this->router->match($this->request));
    }
}
