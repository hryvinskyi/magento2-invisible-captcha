<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorMetadataInterface;
use Magento\Framework\Phrase;

class LessThanOrEqual implements OperatorInterface, OperatorMetadataInterface
{
    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'lte';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('less than or equal');
    }

    /**
     * @inheritDoc
     */
    public function supports(string $fieldType): bool
    {
        return $fieldType === FieldInterface::TYPE_NUMERIC;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        if (!is_numeric($configValue)) {
            return false;
        }

        return (float)($fieldValue ?? 0) <= (float)$configValue;
    }

    /**
     * @inheritDoc
     */
    public function getValueKind(): string
    {
        return self::VALUE_NUMBER;
    }
}
