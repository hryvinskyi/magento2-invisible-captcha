<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Geo;

/**
 * Registry of every {@see CountrySourceInterface} the geo domain knows about.
 *
 * Other modules register additional sources by adding them to the `sources`
 * argument of this type's implementation in their own di.xml.
 */
interface CountrySourcePoolInterface
{
    /**
     * All registered sources, keyed by their code, in display order.
     *
     * @return CountrySourceInterface[]
     */
    public function getAll(): array;

    /**
     * Get a source by its code or return null when unknown.
     *
     * @param string $code
     * @return CountrySourceInterface|null
     */
    public function get(string $code): ?CountrySourceInterface;
}
