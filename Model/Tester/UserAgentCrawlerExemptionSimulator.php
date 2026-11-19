<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\ExclusionPolicyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Tester\CrawlerExemptionSimulatorInterface;

/**
 * A simulated request is an exempt crawler when its User-Agent matches the
 * "Excluded User Agents" list, as {@see \Hryvinskyi\InvisibleCaptcha\Model\UserAgentCrawlerExemption}
 * decides for a live one. The client IP plays no part.
 */
class UserAgentCrawlerExemptionSimulator implements CrawlerExemptionSimulatorInterface
{
    /**
     * @param ExclusionPolicyInterface $exclusionPolicy
     */
    public function __construct(
        private readonly ExclusionPolicyInterface $exclusionPolicy
    ) {
    }

    /**
     * @inheritDoc
     */
    public function simulate(string $userAgent, string $clientIp): array
    {
        return ['exempt' => $this->exclusionPolicy->isUserAgentExcluded($userAgent), 'notes' => []];
    }
}
