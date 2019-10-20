<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Area;

interface ConfigListInterface
{
    /**
     * @return array
     */
    public function getConfigList(): array;

    /**
     * @param string $area
     *
     * @return ConfigInterface
     */
    public function getConfig(string $area): ConfigInterface;
}