<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExclusionPolicyInterface;

class ExclusionPolicy implements ExclusionPolicyInterface
{
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isIpExcluded(string $ip, ?string $scopeCode = null): bool
    {
        if ($ip === '') {
            return false;
        }

        $excludedIps = $this->config->getExcludedIps($scopeCode);

        return $excludedIps !== [] && in_array($ip, $excludedIps, true);
    }

    /**
     * @inheritDoc
     */
    public function isUserAgentExcluded(string $userAgent, ?string $scopeCode = null): bool
    {
        if ($userAgent === '') {
            return false;
        }

        foreach ($this->config->getExcludedUserAgents($scopeCode) as $excluded) {
            if ($excluded !== '' && stripos($userAgent, $excluded) !== false) {
                return true;
            }
        }

        return false;
    }
}
