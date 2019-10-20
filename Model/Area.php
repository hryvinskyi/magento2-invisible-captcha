<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Magento\Framework\App\Area as BaseArea;

/**
 * Class Area
 */
class Area
{
    const GLOBAL = BaseArea::AREA_GLOBAL;
    const FRONTEND = BaseArea::AREA_FRONTEND;
    const BACKEND = BaseArea::AREA_ADMINHTML;

    /**
     * @return array
     */
    public function getAllowedList(): array
    {
        return [
            self::GLOBAL,
            self::BACKEND,
            self::FRONTEND,
        ];
    }
}
