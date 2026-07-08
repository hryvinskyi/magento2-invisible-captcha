<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\Validator\ValidatorInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\ValidatorList;
use PHPUnit\Framework\TestCase;

class ValidatorListTest extends TestCase
{
    public function testGetListReturnsInjectedArray(): void
    {
        $first = $this->createMock(ValidatorInterface::class);
        $second = $this->createMock(ValidatorInterface::class);
        $validators = [$first, $second];

        $list = new ValidatorList($validators);

        $this->assertSame($validators, $list->getList());
    }

    public function testGetListDefaultsToEmptyArray(): void
    {
        $list = new ValidatorList();

        $this->assertSame([], $list->getList());
    }
}
