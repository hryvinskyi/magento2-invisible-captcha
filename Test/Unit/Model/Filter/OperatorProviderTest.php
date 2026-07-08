<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\OperatorProvider;
use PHPUnit\Framework\TestCase;

class OperatorProviderTest extends TestCase
{
    public function testEmptyProviderReturnsNothing(): void
    {
        $provider = new OperatorProvider();
        $this->assertSame([], $provider->getAll());
        $this->assertNull($provider->get('eq'));
    }

    public function testRegistersOperatorsByCode(): void
    {
        $eq = $this->createMock(OperatorInterface::class);
        $eq->method('getCode')->willReturn('eq');
        $contains = $this->createMock(OperatorInterface::class);
        $contains->method('getCode')->willReturn('contains');

        $provider = new OperatorProvider(['a' => $eq, 'b' => $contains]);
        $all = $provider->getAll();

        $this->assertArrayHasKey('eq', $all);
        $this->assertArrayHasKey('contains', $all);
        $this->assertSame($eq, $provider->get('eq'));
        $this->assertSame($contains, $provider->get('contains'));
    }

    public function testIgnoresNonOperatorEntries(): void
    {
        $eq = $this->createMock(OperatorInterface::class);
        $eq->method('getCode')->willReturn('eq');

        $provider = new OperatorProvider([$eq, 'not-an-operator', null]);

        $this->assertCount(1, $provider->getAll());
    }
}
