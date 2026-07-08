<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\NotRegex;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotRegexTest extends TestCase
{
    private NotRegex $operator;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->operator = new NotRegex($this->logger);
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('not_regex', $this->operator->getCode());
        $this->assertSame('does not match regex', (string)$this->operator->getLabel());
    }

    public function testSupportsOnlyString(): void
    {
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_STRING));
        $this->assertFalse($this->operator->supports(FieldInterface::TYPE_NUMERIC));
    }

    public function testEmptyPatternIsTrue(): void
    {
        $this->assertTrue($this->operator->evaluate('anything', ''));
    }

    public function testMatchingPatternReturnsFalse(): void
    {
        $this->assertFalse($this->operator->evaluate('/checkout', '~^/checkout~'));
    }

    public function testNonMatchingPatternReturnsTrue(): void
    {
        $this->assertTrue($this->operator->evaluate('/customer', '~^/checkout~'));
    }

    public function testInvalidPatternLogsAndReturnsFalse(): void
    {
        $this->logger->expects($this->once())->method('warning');
        $this->assertFalse($this->operator->evaluate('whatever', 'not-a-valid-regex'));
    }
}
