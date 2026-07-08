<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Model\Area;
use Magento\Framework\App\Area as BaseArea;
use PHPUnit\Framework\TestCase;

class AreaTest extends TestCase
{
    public function testGetAllowedListReturnsGlobalBackendFrontend(): void
    {
        $area = new Area();

        $this->assertSame(
            [
                BaseArea::AREA_GLOBAL,
                BaseArea::AREA_ADMINHTML,
                BaseArea::AREA_FRONTEND,
            ],
            $area->getAllowedList()
        );
    }

    public function testConstantsMapToFrameworkAreas(): void
    {
        $this->assertSame(BaseArea::AREA_GLOBAL, Area::GLOBAL);
        $this->assertSame(BaseArea::AREA_ADMINHTML, Area::BACKEND);
        $this->assertSame(BaseArea::AREA_FRONTEND, Area::FRONTEND);
    }
}
