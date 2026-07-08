<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\RequestMethod;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestMethodTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private RequestMethod $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new RequestMethod($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('request_method', $this->field->getCode());
        $this->assertSame('HTTP Method', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testValueNormalizedToUpper(): void
    {
        $this->request->method('getMethod')->willReturn('post');
        $this->assertSame('POST', $this->field->getValue());
    }

    public function testAlreadyUppercaseMethod(): void
    {
        $this->request->method('getMethod')->willReturn('GET');
        $this->assertSame('GET', $this->field->getValue());
    }
}
