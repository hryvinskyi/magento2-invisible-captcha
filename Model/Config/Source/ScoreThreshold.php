<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ScoreThreshold
 */
class ScoreThreshold implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0.1,
                'label' => 0.1
            ],
            [
                'value' => 0.2,
                'label' => 0.2
            ],
            [
                'value' => 0.3,
                'label' => 0.3
            ],
            [
                'value' => 0.4,
                'label' => 0.4
            ],
            [
                'value' => 0.5,
                'label' => 0.5
            ],
            [
                'value' => 0.6,
                'label' => 0.6
            ],
            [
                'value' => 0.7,
                'label' => 0.7
            ],
            [
                'value' => 0.8,
                'label' => 0.8
            ],
            [
                'value' => 0.9,
                'label' => 0.9
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            '0.1' => 0.1,
            '0.2' => 0.2,
            '0.3' => 0.3,
            '0.4' => 0.4,
            '0.5' => 0.5,
            '0.6' => 0.6,
            '0.7' => 0.7,
            '0.8' => 0.8,
            '0.9' => 0.9,
        ];
    }
}
