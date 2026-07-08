<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionInterface;

class Expression implements ExpressionInterface
{
    /** @var ConditionInterface[] */
    private readonly array $conditions;

    /**
     * @param ConditionInterface[] $conditions
     */
    public function __construct(array $conditions = [])
    {
        $this->conditions = array_values(array_filter(
            $conditions,
            static fn ($condition): bool => $condition instanceof ConditionInterface
        ));
    }

    /**
     * @inheritDoc
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return $this->conditions === [];
    }
}
