<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\FullActionName;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FullActionNameTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private FullActionName $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new FullActionName($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('action_name', $this->field->getCode());
        $this->assertSame('Full Action Name', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testGetValueReturnsFullActionName(): void
    {
        $this->request->method('getFullActionName')->willReturn('catalog_category_view');
        $this->assertSame('catalog_category_view', $this->field->getValue());
    }

    public function testGetValueCoercesNullToEmptyString(): void
    {
        $this->request->method('getFullActionName')->willReturn(null);
        $this->assertSame('', $this->field->getValue());
    }
}
