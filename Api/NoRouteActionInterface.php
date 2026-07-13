<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Resolves the action Magento dispatches for unroutable URLs (the 404
 * "no-route" page), mirroring
 * {@see \Magento\Framework\App\Router\NoRouteHandler} semantics: the
 * `web/default/no_route` config path with core/index/index part fallbacks.
 */
interface NoRouteActionInterface
{
    /**
     * Route parts of the configured no-route action.
     *
     * @return array{route: string, controller: string, action: string}
     */
    public function getRouteParts(): array;

    /**
     * Full action name of the configured no-route action
     * (e.g. "cms_noroute_index").
     *
     * @return string
     */
    public function getFullActionName(): string;
}
