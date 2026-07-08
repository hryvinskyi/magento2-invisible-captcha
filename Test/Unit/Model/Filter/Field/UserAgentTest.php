<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\UserAgent;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserAgentTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private UserAgent $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new UserAgent($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('user_agent', $this->field->getCode());
        $this->assertSame('User Agent', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testReturnsHeaderValue(): void
    {
        $this->request->method('getHeader')->with('User-Agent')->willReturn('Mozilla/5.0');
        $this->assertSame('Mozilla/5.0', $this->field->getValue());
    }

    public function testEmptyWhenHeaderMissing(): void
    {
        $this->request->method('getHeader')->with('User-Agent')->willReturn(false);
        $this->assertSame('', $this->field->getValue());
    }
}
