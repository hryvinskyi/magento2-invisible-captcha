<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Geo;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountryResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourcePoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Resolves the visitor's country via the admin-selected source, memoizing the
 * result (including a null result) for the lifetime of the request.
 *
 * A singleton holding per-request state — {@see self::_resetState()} clears the
 * memo between requests when running under a stateful application server.
 */
class CountryResolver implements CountryResolverInterface, ResetAfterRequestInterface
{
    private bool $resolved = false;
    private ?string $countryCode = null;

    /**
     * @param ConfigInterface $config
     * @param CountrySourcePoolInterface $sourcePool
     * @param ClientIpResolverInterface $clientIpResolver
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly CountrySourcePoolInterface $sourcePool,
        private readonly ClientIpResolverInterface $clientIpResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode(): ?string
    {
        if ($this->resolved) {
            return $this->countryCode;
        }

        $this->resolved = true;

        $source = $this->sourcePool->get($this->config->getGeoSource());
        if ($source === null || !$source->isConfigured()) {
            return $this->countryCode = null;
        }

        return $this->countryCode = $source->resolve($this->clientIpResolver->resolve());
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->resolved = false;
        $this->countryCode = null;
    }
}
