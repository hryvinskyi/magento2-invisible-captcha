<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Field implements OptionSourceInterface
{
    /**
     * @param FieldProviderInterface $fieldProvider
     */
    public function __construct(
        private readonly FieldProviderInterface $fieldProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->fieldProvider->getAll() as $field) {
            $options[] = [
                'value' => $field->getCode(),
                'label' => $field->getLabel(),
            ];
        }

        return $options;
    }
}
