<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Operator implements OptionSourceInterface
{
    /**
     * @param OperatorProviderInterface $operatorProvider
     */
    public function __construct(
        private readonly OperatorProviderInterface $operatorProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->operatorProvider->getAll() as $operator) {
            $options[] = [
                'value' => $operator->getCode(),
                'label' => $operator->getLabel(),
            ];
        }

        return $options;
    }
}
