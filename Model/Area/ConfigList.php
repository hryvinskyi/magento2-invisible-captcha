<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Area;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class ConfigList
 */
class ConfigList implements ConfigListInterface
{
    /**
     * @var ConfigInterface[]
     */
    private $list = [];

    /**
     * ConfigList constructor.
     *
     * @param ConfigInterface[] $list
     */
    public function __construct(array $list = [])
    {
        $this->list = $list;
    }

    /**
     * @return ConfigInterface[]
     */
    public function getConfigList(): array
    {
        return $this->list;
    }

    /**
     * @param string $area
     *
     * @return ConfigInterface
     * @throws LocalizedException
     */
    public function getConfig(string $area): ConfigInterface
    {
        if (!isset($this->list[$area])) {
            throw new LocalizedException(__('Area not found'));
        }

        return $this->list[$area];
    }
}
