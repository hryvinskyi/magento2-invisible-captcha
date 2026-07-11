<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Tester;

/**
 * Runs a route-protection simulation: evaluates the Protection Rules (saved
 * or draft) against a synthetic storefront request under the target store's
 * scope and reports the verdict with a per-condition trace.
 */
interface RouteRuleTesterInterface
{
    /**
     * Simulate the request and report whether it would be challenged.
     *
     * @param SimulationInterface $simulation
     * @return array{
     *     matched: bool,
     *     wouldChallenge: bool,
     *     bypass: array{excludedIp: bool, excludedUserAgent: bool, verifyEndpoint: bool},
     *     context: array<string, mixed>,
     *     fields: array<string, string|int|float|null>,
     *     groups: array<int, array<string, mixed>>,
     *     warnings: array<int, string>
     * } `matched` = the rule expression is true for the simulated request;
     *     `wouldChallenge` also requires route protection enabled, a
     *     configured provider and no bypass; `groups` is the trace from
     *     {@see \Hryvinskyi\InvisibleCaptcha\Api\ExpressionTracerInterface}
     */
    public function test(SimulationInterface $simulation): array;
}
