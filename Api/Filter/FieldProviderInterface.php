<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Filter;

/**
 * Registry of every {@see FieldInterface} the filter editor knows about.
 *
 * Other modules register additional fields by adding them to the `fields`
 * argument of this type's implementation in their own di.xml.
 */
interface FieldProviderInterface
{
    /**
     * All registered fields, keyed by their code, in display order.
     *
     * @return FieldInterface[]
     */
    public function getAll(): array;

    /**
     * Get a field by its code or return null when unknown.
     *
     * @param string $code
     * @return FieldInterface|null
     */
    public function get(string $code): ?FieldInterface;
}
