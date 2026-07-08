<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Strategy\Token;

use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token\JsonBody;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token\RequestParam;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonBodyTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    /** @var Json&MockObject */
    private Json $json;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->json = $this->createMock(Json::class);
    }

    public function testReturnsNullWhenRequestNotHttp(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $this->json->expects($this->never())->method('unserialize');

        $strategy = new JsonBody($request, $this->json);

        $this->assertNull($strategy->getToken());
    }

    public function testReturnsTokenFromJsonBody(): void
    {
        $this->request->method('getContent')
            ->willReturn('{"' . RequestParam::DEFAULT_FIELD . '":"json-token"}');
        $this->json->method('unserialize')
            ->willReturn([RequestParam::DEFAULT_FIELD => 'json-token']);

        $strategy = new JsonBody($this->request, $this->json);

        $this->assertSame('json-token', $strategy->getToken());
    }

    public function testReturnsNullWhenContentEmpty(): void
    {
        $this->request->method('getContent')->willReturn('');
        $this->json->expects($this->never())->method('unserialize');

        $strategy = new JsonBody($this->request, $this->json);

        $this->assertNull($strategy->getToken());
    }

    public function testReturnsNullWhenContentNull(): void
    {
        $this->request->method('getContent')->willReturn(null);
        $this->json->expects($this->never())->method('unserialize');

        $strategy = new JsonBody($this->request, $this->json);

        $this->assertNull($strategy->getToken());
    }

    public function testReturnsNullOnInvalidJson(): void
    {
        $this->request->method('getContent')->willReturn('not-json');
        $this->json->method('unserialize')
            ->willThrowException(new \InvalidArgumentException('Unable to unserialize value.'));

        $strategy = new JsonBody($this->request, $this->json);

        $this->assertNull($strategy->getToken());
    }

    public function testReturnsNullWhenFieldMissing(): void
    {
        $this->request->method('getContent')->willReturn('{"other":"x"}');
        $this->json->method('unserialize')->willReturn(['other' => 'x']);

        $strategy = new JsonBody($this->request, $this->json);

        $this->assertNull($strategy->getToken());
    }

    public function testReturnsNullWhenUnserializeReturnsNonArray(): void
    {
        $this->request->method('getContent')->willReturn('"scalar"');
        $this->json->method('unserialize')->willReturn('scalar');

        $strategy = new JsonBody($this->request, $this->json);

        $this->assertNull($strategy->getToken());
    }

    public function testCastsNonStringFieldToString(): void
    {
        $this->request->method('getContent')->willReturn('{"' . RequestParam::DEFAULT_FIELD . '":123}');
        $this->json->method('unserialize')->willReturn([RequestParam::DEFAULT_FIELD => 123]);

        $strategy = new JsonBody($this->request, $this->json);

        $this->assertSame('123', $strategy->getToken());
    }

    public function testUsesCustomFieldName(): void
    {
        $this->request->method('getContent')->willReturn('{"custom":"tok"}');
        $this->json->method('unserialize')->willReturn(['custom' => 'tok']);

        $strategy = new JsonBody($this->request, $this->json, 'custom');

        $this->assertSame('tok', $strategy->getToken());
    }
}
