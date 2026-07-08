<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\QueryString;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryStringTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private QueryString $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new QueryString($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('query_string', $this->field->getCode());
        $this->assertSame('Query String', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testPrefersServerQueryStringWhenAvailable(): void
    {
        $this->request->method('getServer')->with('QUERY_STRING')->willReturn('p=2&q=hi');
        $this->assertSame('p=2&q=hi', $this->field->getValue());
    }

    public function testFallsBackToRequestUriWhenServerEmpty(): void
    {
        $this->request->method('getServer')->with('QUERY_STRING')->willReturn('');
        $this->request->method('getRequestUri')->willReturn('/catalog?p=3');
        $this->assertSame('p=3', $this->field->getValue());
    }

    public function testEmptyWhenNoQuery(): void
    {
        $this->request->method('getServer')->with('QUERY_STRING')->willReturn('');
        $this->request->method('getRequestUri')->willReturn('/catalog');
        $this->assertSame('', $this->field->getValue());
    }
}
