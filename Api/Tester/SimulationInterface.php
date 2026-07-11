<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Tester;

/**
 * Input of one rule-tester run: the storefront request to simulate and,
 * optionally, the unsaved draft rules to evaluate instead of the stored ones.
 */
interface SimulationInterface
{
    /**
     * URL to simulate — absolute (https://host/path?query) or a bare
     * path (+ optional query) resolved against the store's base URL host.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * HTTP method of the simulated request (GET when not specified).
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * User-Agent header of the simulated request ('' = header absent).
     *
     * @return string
     */
    public function getUserAgent(): string;

    /**
     * Client IP of the simulated request ('' = unknown).
     *
     * @return string
     */
    public function getClientIp(): string;

    /**
     * Referer header of the simulated request ('' = header absent).
     *
     * @return string
     */
    public function getReferer(): string;

    /**
     * Manually supplied full action name (e.g. catalog_product_view) that
     * overrides automatic resolution, or null to auto-resolve from the URL.
     *
     * @return string|null
     */
    public function getActionName(): ?string;

    /**
     * Store view id whose scope (rules, exclusions, robots.txt, credentials)
     * the simulation runs under, or null for the default store view.
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Draft rule rows (combinator/field/operator/value maps) to evaluate
     * instead of the saved Protection Rules, or null to use the saved ones.
     *
     * @return array<int, array<string, string>>|null
     */
    public function getDraftRules(): ?array;
}
