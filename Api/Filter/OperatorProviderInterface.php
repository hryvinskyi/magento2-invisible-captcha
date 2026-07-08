<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Filter;

/**
 * Registry of every {@see OperatorInterface} the filter editor knows about.
 */
interface OperatorProviderInterface
{
    /**
     * All registered operators, keyed by their code, in display order.
     *
     * @return OperatorInterface[]
     */
    public function getAll(): array;

    /**
     * Get an operator by its code or return null when unknown.
     *
     * @param string $code
     * @return OperatorInterface|null
     */
    public function get(string $code): ?OperatorInterface;
}
