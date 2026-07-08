<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Parses the raw admin field-array rows persisted by Magento's serialized
 * array backend into an {@see ExpressionInterface}, skipping malformed
 * rows so a single bad entry never breaks the entire expression.
 */
interface ExpressionParserInterface
{
    /**
     * @param array<int|string, array<string, mixed>> $rows
     * @return ExpressionInterface
     */
    public function parse(array $rows): ExpressionInterface;
}
