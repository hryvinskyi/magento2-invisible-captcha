<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\RequestUri;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestUriTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private RequestUri $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new RequestUri($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('request_uri', $this->field->getCode());
        $this->assertSame('Request URI (path + query)', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testValueIncludesQueryString(): void
    {
        $this->request->method('getRequestUri')->willReturn('/catalog?p=2');
        $this->assertSame('/catalog?p=2', $this->field->getValue());
    }
}
