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

class NotEquals implements OperatorInterface, OperatorMetadataInterface
{
    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'neq';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('does not equal');
    }

    /**
     * @inheritDoc
     */
    public function supports(string $fieldType): bool
    {
        return in_array(
            $fieldType,
            [FieldInterface::TYPE_STRING, FieldInterface::TYPE_NUMERIC, FieldInterface::TYPE_BOOLEAN],
            true
        );
    }

    /**
     * @inheritDoc
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        return (string)($fieldValue ?? '') !== $configValue;
    }

    /**
     * @inheritDoc
     */
    public function getValueKind(): string
    {
        return self::VALUE_TEXT;
    }
}
