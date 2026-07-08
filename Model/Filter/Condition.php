<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;

class Condition implements ConditionInterface
{
    private readonly string $combinator;
    private readonly string $fieldCode;
    private readonly string $operatorCode;
    private readonly string $value;

    /**
     * @param string $combinator
     * @param string $fieldCode
     * @param string $operatorCode
     * @param string $value
     */
    public function __construct(
        string $combinator,
        string $fieldCode,
        string $operatorCode,
        string $value
    ) {
        $this->combinator = strtolower($combinator) === self::COMBINATOR_OR
            ? self::COMBINATOR_OR
            : self::COMBINATOR_AND;
        $this->fieldCode = $fieldCode;
        $this->operatorCode = $operatorCode;
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function getCombinator(): string
    {
        return $this->combinator;
    }

    /**
     * @inheritDoc
     */
    public function getFieldCode(): string
    {
        return $this->fieldCode;
    }

    /**
     * @inheritDoc
     */
    public function getOperatorCode(): string
    {
        return $this->operatorCode;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
