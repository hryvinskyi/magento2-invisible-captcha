<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Tester;

/**
 * The rule tester's counterpart of {@see \Hryvinskyi\InvisibleCaptcha\Api\CrawlerExemptionInterface}:
 * decides whether a simulated User-Agent and client IP would skip the challenge
 * as a trusted crawler, and explains the decision to the administrator.
 *
 * The module's own implementation applies the "Excluded User Agents" list. A
 * module that replaces the live crawler exemption replaces or decorates this
 * contract too, so the tester shows what the storefront would do.
 *
 * @api
 */
interface CrawlerExemptionSimulatorInterface
{
    /**
     * @param string $userAgent Simulated User-Agent header
     * @param string $clientIp Simulated client IP; empty when none was given
     * @return array{exempt: bool, notes: list<string>} `notes` are translated
     *     sentences shown with the test result
     */
    public function simulate(string $userAgent, string $clientIp): array;
}
