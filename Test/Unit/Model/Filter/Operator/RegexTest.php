<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\Regex;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RegexTest extends TestCase
{
    private Regex $operator;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->operator = new Regex($this->logger);
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('regex', $this->operator->getCode());
        $this->assertSame('matches regex', (string)$this->operator->getLabel());
    }

    public function testSupportsOnlyString(): void
    {
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_STRING));
        $this->assertFalse($this->operator->supports(FieldInterface::TYPE_NUMERIC));
    }

    public function testMatchingPattern(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $this->assertTrue($this->operator->evaluate('/checkout/onepage', '~^/checkout~'));
    }

    public function testNonMatchingPattern(): void
    {
        $this->assertFalse($this->operator->evaluate('/customer/account', '~^/checkout~'));
    }

    public function testEmptyPatternNeverMatches(): void
    {
        $this->assertFalse($this->operator->evaluate('anything', ''));
    }

    public function testCaseInsensitiveFlag(): void
    {
        $this->assertTrue($this->operator->evaluate('/CheckOut', '~^/checkout~i'));
    }

    public function testBareCloudflareStylePatternIsAccepted(): void
    {
        // Admins write Cloudflare-style patterns without PCRE delimiters —
        // `.*` must match every action name instead of erroring out.
        $this->logger->expects($this->never())->method('warning');
        $this->assertTrue($this->operator->evaluate('cms_index_index', '.*'));
        $this->assertTrue($this->operator->evaluate('catalog_product_view', '^catalog_.*'));
        $this->assertFalse($this->operator->evaluate('cms_index_index', '^catalog_.*'));
    }

    public function testBarePatternWithLiteralTextNeverErrors(): void
    {
        // Wrapped as ~not-a-valid-regex~ this is a valid literal pattern.
        $this->logger->expects($this->never())->method('warning');
        $this->assertTrue($this->operator->evaluate('xx not-a-valid-regex yy', 'not-a-valid-regex'));
        $this->assertFalse($this->operator->evaluate('whatever', 'not-a-valid-regex'));
    }

    public function testInvalidPatternLogsAndReturnsFalse(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('invalid regex skipped'));

        // Unclosed group is invalid both as-is and wrapped.
        $this->assertFalse($this->operator->evaluate('whatever', '(unclosed'));
    }

    public function testNullFieldValueTreatedAsEmptyString(): void
    {
        $this->assertTrue($this->operator->evaluate(null, '~^$~'));
        $this->assertFalse($this->operator->evaluate(null, '~.+~'));
    }
}
