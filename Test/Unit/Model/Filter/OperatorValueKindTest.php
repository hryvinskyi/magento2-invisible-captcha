<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorMetadataInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\Contains;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\EndsWith;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\Equals;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\GreaterThan;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\GreaterThanOrEqual;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\In;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\LessThan;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\LessThanOrEqual;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\NotContains;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\NotEquals;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\NotIn;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\NotRegex;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\Regex;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\StartsWith;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Every built-in operator declares the shape of value it consumes, so the
 * rules editor can validate input dynamically.
 */
class OperatorValueKindTest extends TestCase
{
    public function testEveryOperatorDeclaresItsValueKind(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expected = [
            OperatorMetadataInterface::VALUE_TEXT => [new Equals(), new NotEquals()],
            OperatorMetadataInterface::VALUE_TEXT_REQUIRED => [
                new Contains(),
                new NotContains(),
                new StartsWith(),
                new EndsWith(),
            ],
            OperatorMetadataInterface::VALUE_LIST => [new In(), new NotIn()],
            OperatorMetadataInterface::VALUE_PATTERN => [new Regex($logger), new NotRegex($logger)],
            OperatorMetadataInterface::VALUE_NUMBER => [
                new GreaterThan(),
                new GreaterThanOrEqual(),
                new LessThan(),
                new LessThanOrEqual(),
            ],
        ];

        foreach ($expected as $kind => $operators) {
            foreach ($operators as $operator) {
                $this->assertInstanceOf(OperatorMetadataInterface::class, $operator);
                $this->assertSame($kind, $operator->getValueKind(), $operator->getCode());
            }
        }
    }
}
