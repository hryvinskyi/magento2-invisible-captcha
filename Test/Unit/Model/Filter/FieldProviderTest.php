<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\FieldProvider;
use PHPUnit\Framework\TestCase;

class FieldProviderTest extends TestCase
{
    public function testEmptyProviderReturnsNothing(): void
    {
        $provider = new FieldProvider();
        $this->assertSame([], $provider->getAll());
        $this->assertNull($provider->get('action_name'));
    }

    public function testRegistersFieldsByCode(): void
    {
        $field = $this->createMock(FieldInterface::class);
        $field->method('getCode')->willReturn('action_name');

        $provider = new FieldProvider(['ignored_key' => $field]);
        $all = $provider->getAll();

        $this->assertArrayHasKey('action_name', $all);
        $this->assertSame($field, $all['action_name']);
        $this->assertSame($field, $provider->get('action_name'));
    }

    public function testSkipsNonFieldInterfaceEntries(): void
    {
        $field = $this->createMock(FieldInterface::class);
        $field->method('getCode')->willReturn('client_ip');

        $provider = new FieldProvider([$field, 'string', null, new \stdClass()]);

        $this->assertCount(1, $provider->getAll());
        $this->assertNotNull($provider->get('client_ip'));
    }

    public function testGetReturnsNullForUnknown(): void
    {
        $provider = new FieldProvider();
        $this->assertNull($provider->get('nope'));
    }
}
