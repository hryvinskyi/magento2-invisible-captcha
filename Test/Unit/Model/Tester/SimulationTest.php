<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Model\Tester\Simulation;
use PHPUnit\Framework\TestCase;

class SimulationTest extends TestCase
{
    public function testDefaults(): void
    {
        $simulation = new Simulation('/checkout/cart');

        $this->assertSame('/checkout/cart', $simulation->getUrl());
        $this->assertSame('GET', $simulation->getMethod());
        $this->assertSame('', $simulation->getUserAgent());
        $this->assertSame('', $simulation->getClientIp());
        $this->assertSame('', $simulation->getReferer());
        $this->assertNull($simulation->getActionName());
        $this->assertNull($simulation->getStoreId());
        $this->assertNull($simulation->getDraftRules());
    }

    public function testAllValuesExposed(): void
    {
        $rules = [['combinator' => 'and', 'field' => 'uri_path', 'operator' => 'eq', 'value' => '/x']];
        $simulation = new Simulation(
            'https://shop.test/lamps?p=2',
            'POST',
            'TestBot/1.0',
            '1.2.3.4',
            'https://ref.test/',
            'catalog_category_view',
            3,
            $rules
        );

        $this->assertSame('https://shop.test/lamps?p=2', $simulation->getUrl());
        $this->assertSame('POST', $simulation->getMethod());
        $this->assertSame('TestBot/1.0', $simulation->getUserAgent());
        $this->assertSame('1.2.3.4', $simulation->getClientIp());
        $this->assertSame('https://ref.test/', $simulation->getReferer());
        $this->assertSame('catalog_category_view', $simulation->getActionName());
        $this->assertSame(3, $simulation->getStoreId());
        $this->assertSame($rules, $simulation->getDraftRules());
    }
}
