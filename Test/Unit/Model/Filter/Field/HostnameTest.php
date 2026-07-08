<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\Hostname;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HostnameTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private Hostname $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new Hostname($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('hostname', $this->field->getCode());
        $this->assertSame('Hostname', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testPrefersHostHeader(): void
    {
        $this->request->method('getHeader')->with('Host')->willReturn('Shop.Example.COM');
        $this->assertSame('shop.example.com', $this->field->getValue());
    }

    public function testFallsBackToHttpHostServerVar(): void
    {
        $this->request->method('getHeader')->with('Host')->willReturn(false);
        $this->request->method('getServer')->with('HTTP_HOST')->willReturn('Other.HOST');
        $this->assertSame('other.host', $this->field->getValue());
    }

    public function testEmptyWhenNoSources(): void
    {
        $this->request->method('getHeader')->with('Host')->willReturn(false);
        $this->request->method('getServer')->with('HTTP_HOST')->willReturn(null);
        $this->assertSame('', $this->field->getValue());
    }
}
