<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Filter;

use Magento\Framework\Phrase;

/**
 * Comparison operator between a {@see FieldInterface} value resolved from
 * the request and the literal value the admin entered, analogous to
 * Cloudflare's operator dropdown (equals, contains, matches regex, …).
 */
interface OperatorInterface
{
    /**
     * Stable machine code persisted in the admin config (e.g. "eq", "regex").
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Human-readable label shown in the admin Operator dropdown.
     *
     * @return Phrase
     */
    public function getLabel(): Phrase;

    /**
     * Decide whether the operator can be used against the given field type.
     *
     * @param string $fieldType One of {@see FieldInterface}::TYPE_*
     * @return bool
     */
    public function supports(string $fieldType): bool;

    /**
     * Evaluate the operator. Implementations must coerce values defensively
     * and never throw on bad input — return false so a single bad row never
     * blocks legitimate traffic.
     *
     * @param string|int|float|null $fieldValue Value resolved from the request
     * @param string $configValue Raw literal value entered by the admin
     * @return bool
     */
    public function evaluate(string|int|float|null $fieldValue, string $configValue): bool;
}
