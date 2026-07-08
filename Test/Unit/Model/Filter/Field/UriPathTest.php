<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\UriPath;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UriPathTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private UriPath $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new UriPath($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('uri_path', $this->field->getCode());
        $this->assertSame('URI Path', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    /**
     * @dataProvider valueProvider
     */
    public function testValueStripsQueryString(string $uri, string $expected): void
    {
        $this->request->method('getRequestUri')->willReturn($uri);
        $this->assertSame($expected, $this->field->getValue());
    }

    public static function valueProvider(): array
    {
        return [
            'path only' => ['/catalog/category', '/catalog/category'],
            'path with query' => ['/catalog/category?p=2&q=hi', '/catalog/category'],
            'just question mark' => ['/path?', '/path'],
            'empty uri' => ['', ''],
        ];
    }
}
