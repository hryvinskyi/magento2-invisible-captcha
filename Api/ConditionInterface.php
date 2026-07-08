<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * One node of a filter expression — a Cloudflare-style triplet of
 * `field operator value`, combined with the previous node via a logical
 * combinator (AND/OR). The combinator on the first condition is ignored.
 */
interface ConditionInterface
{
    public const COMBINATOR_AND = 'and';
    public const COMBINATOR_OR = 'or';

    /**
     * Logical combinator that joins this condition to the previous one.
     *
     * @return string One of COMBINATOR_AND / COMBINATOR_OR
     */
    public function getCombinator(): string;

    /**
     * Field code referencing a {@see \Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface}.
     *
     * @return string
     */
    public function getFieldCode(): string;

    /**
     * Operator code referencing a {@see \Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface}.
     *
     * @return string
     */
    public function getOperatorCode(): string;

    /**
     * Raw literal value entered by the admin.
     *
     * @return string
     */
    public function getValue(): string;
}
