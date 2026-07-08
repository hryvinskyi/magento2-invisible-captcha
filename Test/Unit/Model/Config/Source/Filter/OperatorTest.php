<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Filter\Operator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OperatorTest extends TestCase
{
    /** @var OperatorProviderInterface&MockObject */
    private OperatorProviderInterface $operatorProvider;
    private Operator $source;

    protected function setUp(): void
    {
        $this->operatorProvider = $this->createMock(OperatorProviderInterface::class);
        $this->source = new Operator($this->operatorProvider);
    }

    public function testToOptionArrayMapsRegisteredOperators(): void
    {
        $this->operatorProvider->method('getAll')->willReturn([
            'eq' => $this->makeOperator('eq', 'equals'),
            'contains' => $this->makeOperator('contains', 'contains'),
        ]);

        $options = $this->source->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame('eq', $options[0]['value']);
        $this->assertSame('equals', (string)$options[0]['label']);
        $this->assertSame('contains', $options[1]['value']);
        $this->assertSame('contains', (string)$options[1]['label']);
    }

    public function testToOptionArrayWithNoOperators(): void
    {
        $this->operatorProvider->method('getAll')->willReturn([]);

        $this->assertSame([], $this->source->toOptionArray());
    }

    /**
     * @return OperatorInterface&MockObject
     */
    private function makeOperator(string $code, string $label): OperatorInterface
    {
        $operator = $this->createMock(OperatorInterface::class);
        $operator->method('getCode')->willReturn($code);
        $operator->method('getLabel')->willReturn(__($label));

        return $operator;
    }
}
