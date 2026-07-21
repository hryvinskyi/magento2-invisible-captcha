<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Http;

use Hryvinskyi\InvisibleCaptcha\Model\Http\ClientIpResolver;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientIpResolverTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private ClientIpResolver $resolver;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->resolver = new ClientIpResolver($this->request);
    }

    public function testCloudflareHeaderTakesPriority(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, '1.2.3.4'],
            ['HTTP_X_REAL_IP', null, '5.6.7.8'],
            ['HTTP_X_FORWARDED_FOR', null, '9.10.11.12'],
            ['REMOTE_ADDR', null, '13.14.15.16'],
        ]);

        $this->assertSame('1.2.3.4', $this->resolver->resolve());
    }

    public function testFallsThroughHeaderPriority(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, ''],
            ['HTTP_X_REAL_IP', null, ''],
            ['HTTP_X_FORWARDED_FOR', null, '9.10.11.12'],
            ['REMOTE_ADDR', null, '13.14.15.16'],
        ]);

        $this->assertSame('9.10.11.12', $this->resolver->resolve());
    }

    public function testForwardedForChainPicksFirst(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, ''],
            ['HTTP_X_REAL_IP', null, ''],
            ['HTTP_X_FORWARDED_FOR', null, '203.0.113.1, 10.0.0.1, 172.16.0.1'],
        ]);

        $this->assertSame('203.0.113.1', $this->resolver->resolve());
    }

    public function testInvalidIpIsSkipped(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, 'not-an-ip'],
            ['HTTP_X_REAL_IP', null, '5.6.7.8'],
        ]);

        $this->assertSame('5.6.7.8', $this->resolver->resolve());
    }

    public function testEmptyWhenNothingValid(): void
    {
        $this->request->method('getServer')->willReturn('');
        $this->assertSame('', $this->resolver->resolve());
    }

    public function testResolveFromUsesTheProvidedRequest(): void
    {
        $otherRequest = $this->createMock(HttpRequest::class);
        $otherRequest->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, ''],
            ['HTTP_X_REAL_IP', null, '198.51.100.7'],
        ]);

        $this->assertSame('198.51.100.7', $this->resolver->resolveFrom($otherRequest));
    }

    public function testResolveDelegatesToTheInjectedRequest(): void
    {
        // The injected request yields one IP; a different request yields another —
        // resolve() must read the injected one.
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, '203.0.113.9'],
        ]);
        $otherRequest = $this->createMock(HttpRequest::class);
        $otherRequest->method('getServer')->willReturn('192.0.2.1');

        $this->assertSame('203.0.113.9', $this->resolver->resolve());
        $this->assertSame('192.0.2.1', $this->resolver->resolveFrom($otherRequest));
    }
}
