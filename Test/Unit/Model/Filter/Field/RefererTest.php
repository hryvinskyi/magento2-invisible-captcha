<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\Referer;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RefererTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private Referer $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new Referer($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('referer', $this->field->getCode());
        $this->assertSame('Referer', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testReturnsHeaderValue(): void
    {
        $this->request->method('getHeader')
            ->with('Referer')
            ->willReturn('https://example.com/cart');
        $this->assertSame('https://example.com/cart', $this->field->getValue());
    }

    public function testEmptyWhenHeaderMissing(): void
    {
        $this->request->method('getHeader')->willReturn(false);
        $this->assertSame('', $this->field->getValue());
    }
}
