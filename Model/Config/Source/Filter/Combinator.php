<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Combinator implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => ConditionInterface::COMBINATOR_AND, 'label' => __('AND')],
            ['value' => ConditionInterface::COMBINATOR_OR, 'label' => __('OR')],
        ];
    }
}
