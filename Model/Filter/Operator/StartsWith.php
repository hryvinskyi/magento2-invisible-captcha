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

class StartsWith implements OperatorInterface, OperatorMetadataInterface
{
    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'starts_with';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('starts with');
    }

    /**
     * @inheritDoc
     */
    public function supports(string $fieldType): bool
    {
        return $fieldType === FieldInterface::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool
    {
        if ($configValue === '') {
            return false;
        }

        return str_starts_with((string)($fieldValue ?? ''), $configValue);
    }

    /**
     * @inheritDoc
     */
    public function getValueKind(): string
    {
        return self::VALUE_TEXT_REQUIRED;
    }
}
