<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Filter;

/**
 * Optional hint a {@see FieldInterface} can expose about what a literal
 * comparison value for it looks like, so the rules editor can offer a
 * field-specific placeholder and validate exact-match values dynamically.
 *
 * The pattern is applied by the editor only where the whole value (or each
 * list item) is compared verbatim — `equals` / `does not equal` and the list
 * operators. Substring and regex operators legitimately take fragments, so
 * the pattern is not enforced there; the placeholder is shown everywhere.
 */
interface FieldValueHintInterface
{
    /**
     * Value hint for the rules editor. All keys are optional:
     * - `pattern`: anchored, JS-compatible regex a full literal value must match
     * - `message`: validation message shown when the pattern fails
     * - `placeholder`: example value shown in the empty Value input
     *
     * @return array{pattern?: string, message?: string, placeholder?: string}
     */
    public function getValueHint(): array;
}
