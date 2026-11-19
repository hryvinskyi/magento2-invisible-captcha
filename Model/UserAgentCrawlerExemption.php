<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\CrawlerExemptionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExclusionPolicyInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;

/**
 * A request is an exempt crawler when its User-Agent matches the "Excluded
 * User Agents" list ({@see ExclusionPolicyInterface::isUserAgentExcluded()}).
 *
 * The User-Agent is whatever the client sent, so this exemption takes every
 * claim at its word. A request that is not an HTTP request has no User-Agent
 * and is never exempt.
 */
class UserAgentCrawlerExemption implements CrawlerExemptionInterface
{
    /**
     * @param ExclusionPolicyInterface $exclusionPolicy Holds the excluded User-Agent substrings
     * @param RequestInterface $request The current request
     */
    public function __construct(
        private readonly ExclusionPolicyInterface $exclusionPolicy,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isExempt(): bool
    {
        if (!$this->request instanceof HttpRequest) {
            return false;
        }

        $userAgent = $this->request->getHeader('User-Agent');
        if (!is_string($userAgent)) {
            return false;
        }

        return $this->exclusionPolicy->isUserAgentExcluded($userAgent);
    }
}
