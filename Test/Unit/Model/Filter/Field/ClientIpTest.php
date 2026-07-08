<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\ClientIp;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientIpTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private ClientIp $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new ClientIp($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('client_ip', $this->field->getCode());
        $this->assertSame('Client IP', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testCloudflareHeaderTakesPriority(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, '1.2.3.4'],
            ['HTTP_X_REAL_IP', null, '5.6.7.8'],
            ['HTTP_X_FORWARDED_FOR', null, '9.10.11.12'],
            ['REMOTE_ADDR', null, '13.14.15.16'],
        ]);

        $this->assertSame('1.2.3.4', $this->field->getValue());
    }

    public function testFallsThroughHeaderPriority(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, ''],
            ['HTTP_X_REAL_IP', null, ''],
            ['HTTP_X_FORWARDED_FOR', null, '9.10.11.12'],
            ['REMOTE_ADDR', null, '13.14.15.16'],
        ]);

        $this->assertSame('9.10.11.12', $this->field->getValue());
    }

    public function testForwardedForChainPicksFirst(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, ''],
            ['HTTP_X_REAL_IP', null, ''],
            ['HTTP_X_FORWARDED_FOR', null, '203.0.113.1, 10.0.0.1, 172.16.0.1'],
        ]);

        $this->assertSame('203.0.113.1', $this->field->getValue());
    }

    public function testInvalidIpIsSkipped(): void
    {
        $this->request->method('getServer')->willReturnMap([
            ['HTTP_CF_CONNECTING_IP', null, 'not-an-ip'],
            ['HTTP_X_REAL_IP', null, '5.6.7.8'],
        ]);

        $this->assertSame('5.6.7.8', $this->field->getValue());
    }

    public function testEmptyWhenNothingValid(): void
    {
        $this->request->method('getServer')->willReturn('');
        $this->assertSame('', $this->field->getValue());
    }
}
