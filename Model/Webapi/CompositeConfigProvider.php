<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Webapi;

use Hryvinskyi\InvisibleCaptcha\Api\Webapi\EndpointInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Webapi\WebapiConfigProviderInterface;

/**
 * Aggregates per-domain endpoint config providers; the first non-null match wins.
 */
class CompositeConfigProvider implements WebapiConfigProviderInterface
{
    /**
     * @param WebapiConfigProviderInterface[] $providers
     */
    public function __construct(
        private readonly array $providers = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getFormKeyFor(EndpointInterface $endpoint): ?string
    {
        foreach ($this->providers as $provider) {
            $formKey = $provider->getFormKeyFor($endpoint);
            if ($formKey !== null) {
                return $formKey;
            }
        }

        return null;
    }
}
