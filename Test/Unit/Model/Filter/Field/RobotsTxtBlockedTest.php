<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\MatcherInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\RobotsTxtBlocked;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RobotsTxtBlockedTest extends TestCase
{
    /** @var MatcherInterface&MockObject */
    private MatcherInterface $matcher;
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private RobotsTxtBlocked $field;

    protected function setUp(): void
    {
        $this->matcher = $this->createMock(MatcherInterface::class);
        $this->request = $this->createMock(HttpRequest::class);
        $this->field = new RobotsTxtBlocked($this->matcher, $this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('robots_txt_blocked', $this->field->getCode());
        $this->assertSame('Blocked by robots.txt', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_BOOLEAN, $this->field->getType());
    }

    public function testValueIsOneWhenRobotsTxtDisallowsTheRequest(): void
    {
        $this->request->method('getRequestUri')->willReturn('/checkout/cart?step=1');
        $this->request->method('getHeader')->with('User-Agent')->willReturn('EvilBot/1.0');
        $this->matcher->expects($this->once())
            ->method('isDisallowed')
            ->with('/checkout/cart?step=1', 'EvilBot/1.0')
            ->willReturn(true);

        $this->assertSame(1, $this->field->getValue());
    }

    public function testValueIsZeroWhenRobotsTxtAllowsTheRequest(): void
    {
        $this->request->method('getRequestUri')->willReturn('/catalog/category');
        $this->request->method('getHeader')->with('User-Agent')->willReturn('Mozilla/5.0');
        $this->matcher->method('isDisallowed')->willReturn(false);

        $this->assertSame(0, $this->field->getValue());
    }

    public function testEmptyRequestUriShortCircuitsWithoutMatching(): void
    {
        $this->request->method('getRequestUri')->willReturn('');
        $this->matcher->expects($this->never())->method('isDisallowed');

        $this->assertSame(0, $this->field->getValue());
    }

    public function testMissingUserAgentHeaderIsPassedAsEmptyString(): void
    {
        $this->request->method('getRequestUri')->willReturn('/page');
        $this->request->method('getHeader')->with('User-Agent')->willReturn(false);
        $this->matcher->expects($this->once())
            ->method('isDisallowed')
            ->with('/page', '')
            ->willReturn(false);

        $this->assertSame(0, $this->field->getValue());
    }
}
