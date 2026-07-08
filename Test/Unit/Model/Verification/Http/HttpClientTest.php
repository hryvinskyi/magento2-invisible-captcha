<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification\Http;

use Hryvinskyi\InvisibleCaptcha\Exception\HttpClientException;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Http\BoundedHttpClient;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Http\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpClientTest extends TestCase
{
    /** @var BoundedHttpClient&MockObject */
    private BoundedHttpClient $transport;
    private HttpClient $client;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(BoundedHttpClient::class);
        $this->client = new HttpClient($this->transport);
    }

    public function testPostReturnsBodyOnSuccess(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('withBody')->willReturnSelf();
        $request->method('withHeader')->willReturnSelf();

        $stream = $this->createMock(StreamInterface::class);

        $this->transport->expects($this->once())
            ->method('createRequest')
            ->with('POST', 'https://verify.example/endpoint')
            ->willReturn($request);
        $this->transport->expects($this->once())
            ->method('createStream')
            ->with('payload')
            ->willReturn($stream);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn('response-body');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($bodyStream);

        $this->transport->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $result = $this->client->post(
            'https://verify.example/endpoint',
            'payload',
            ['Content-Type' => 'application/json']
        );

        $this->assertSame('response-body', $result);
    }

    public function testPostThrowsOnNonSuccessStatus(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('withBody')->willReturnSelf();
        $request->method('withHeader')->willReturnSelf();

        $this->transport->method('createRequest')->willReturn($request);
        $this->transport->method('createStream')->willReturn($this->createMock(StreamInterface::class));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(503);

        $this->transport->method('sendRequest')->willReturn($response);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Endpoint returned HTTP 503');

        $this->client->post('https://verify.example/endpoint', 'payload');
    }

    public function testPostWrapsClientException(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('withBody')->willReturnSelf();
        $request->method('withHeader')->willReturnSelf();

        $this->transport->method('createRequest')->willReturn($request);
        $this->transport->method('createStream')->willReturn($this->createMock(StreamInterface::class));

        $clientException = new class('transport down') extends \RuntimeException implements ClientExceptionInterface {
        };
        $this->transport->method('sendRequest')->willThrowException($clientException);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('transport down');

        $this->client->post('https://verify.example/endpoint', 'payload');
    }

    public function testPostWrapsGenericThrowable(): void
    {
        $this->transport->method('createRequest')
            ->willThrowException(new \RuntimeException('factory blew up'));

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('factory blew up');

        $this->client->post('https://verify.example/endpoint', 'payload');
    }
}
