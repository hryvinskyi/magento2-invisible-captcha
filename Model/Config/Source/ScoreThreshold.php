<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Score threshold options (0.1–0.9) for score-based providers.
 */
class ScoreThreshold implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach (range(1, 9) as $tenth) {
            $value = $tenth / 10;
            $options[] = ['value' => (string)$value, 'label' => (string)$value];
        }

        return $options;
    }
}
