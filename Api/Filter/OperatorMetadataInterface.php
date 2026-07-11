<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Filter;

/**
 * Optional metadata an {@see OperatorInterface} can expose about the shape of
 * the value it consumes, so the rules editor can validate input dynamically.
 *
 * Operators that do not implement this interface are treated as
 * {@see self::VALUE_TEXT}.
 */
interface OperatorMetadataInterface
{
    /**
     * Free text; an empty value is legal (e.g. `equals ""` matches an empty
     * field value).
     */
    public const VALUE_TEXT = 'text';

    /**
     * Free text that never matches when empty (contains / starts with / …) —
     * the editor flags an empty value.
     */
    public const VALUE_TEXT_REQUIRED = 'text_required';

    /**
     * One or more items separated by commas and/or whitespace.
     */
    public const VALUE_LIST = 'list';

    /**
     * A regular expression — bare pattern or delimited PCRE.
     */
    public const VALUE_PATTERN = 'pattern';

    /**
     * A numeric literal.
     */
    public const VALUE_NUMBER = 'number';

    /**
     * How the operator consumes the admin-entered value — one of the
     * VALUE_* constants.
     *
     * @return string
     */
    public function getValueKind(): string;
}
