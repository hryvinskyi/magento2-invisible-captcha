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

class In implements OperatorInterface, OperatorMetadataInterface
{
    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'in';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('is in list');
    }

    /**
     * @inheritDoc
     */
    public function supports(string $fieldType): bool
    {
        return in_array($fieldType, [FieldInterface::TYPE_STRING, FieldInterface::TYPE_NUMERIC], true);
    }

    /**
     * @inheritDoc
     *
     * Comma- or newline-separated list. Empty list never matches.
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        $items = preg_split('/[\s,]+/', $configValue) ?: [];
        $items = array_values(array_filter(array_map('trim', $items)));
        if ($items === []) {
            return false;
        }

        return in_array((string)($fieldValue ?? ''), $items, true);
    }

    /**
     * @inheritDoc
     */
    public function getValueKind(): string
    {
        return self::VALUE_LIST;
    }
}
