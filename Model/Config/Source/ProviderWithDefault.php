<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provider dropdown prefixed with an empty "Use active provider" option, used
 * for the route-gate override and fallback selectors.
 */
class ProviderWithDefault implements OptionSourceInterface
{
    /**
     * @param ProviderPoolInterface $providerPool
     * @param string $emptyLabel
     */
    public function __construct(
        private readonly ProviderPoolInterface $providerPool,
        private readonly string $emptyLabel = 'Use active provider'
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __($this->emptyLabel)]];
        foreach ($this->providerPool->getAll() as $provider) {
            $options[] = ['value' => $provider->getCode(), 'label' => $provider->getLabel()];
        }

        return $options;
    }
}
