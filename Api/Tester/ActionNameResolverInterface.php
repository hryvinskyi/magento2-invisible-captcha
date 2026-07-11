<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Tester;

/**
 * Best-effort resolution of the frontend route parts (and therefore the full
 * action name) a storefront path would dispatch to, without running the
 * frontend router chain.
 */
interface ActionNameResolverInterface
{
    /**
     * Resolve a storefront path into its route parts.
     *
     * Resolution order: the store's URL rewrites (SEO product/category/CMS
     * URLs, including one redirect hop), then front-name → route-id mapping
     * for literal route paths; the bare root path maps to the CMS home
     * route. Returns null when the path cannot be resolved (e.g. a custom
     * router would handle it).
     *
     * @param string $path URI path without query string (leading slash optional)
     * @param int $storeId Store view the URL belongs to
     * @return array{
     *     route: string,
     *     controller: string,
     *     action: string,
     *     params: array<string, string>,
     *     source: string
     * }|null Route parts plus the path-embedded params and how they were
     *     resolved ('rewrite', 'route' or 'home')
     */
    public function resolve(string $path, int $storeId): ?array;
}
