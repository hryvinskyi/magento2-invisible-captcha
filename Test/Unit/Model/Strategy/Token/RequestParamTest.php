<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Token;

use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token\RequestParam;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestParamTest extends TestCase
{
    /** @var RequestInterface&MockObject */
    private RequestInterface $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
    }

    public function testReturnsTokenFromDefaultField(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with(RequestParam::DEFAULT_FIELD)
            ->willReturn('token-value');

        $strategy = new RequestParam($this->request);

        $this->assertSame('token-value', $strategy->getToken());
    }

    public function testReturnsNullWhenParamAbsent(): void
    {
        $this->request->method('getParam')
            ->with(RequestParam::DEFAULT_FIELD)
            ->willReturn(null);

        $strategy = new RequestParam($this->request);

        $this->assertNull($strategy->getToken());
    }

    public function testCastsNonStringValueToString(): void
    {
        $this->request->method('getParam')->willReturn(12345);

        $strategy = new RequestParam($this->request);

        $this->assertSame('12345', $strategy->getToken());
    }

    public function testUsesCustomFieldName(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('custom_field')
            ->willReturn('custom-token');

        $strategy = new RequestParam($this->request, 'custom_field');

        $this->assertSame('custom-token', $strategy->getToken());
    }
}
