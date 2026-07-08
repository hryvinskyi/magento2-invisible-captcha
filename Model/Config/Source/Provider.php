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
 * Active-provider dropdown, driven by the registered provider pool.
 */
class Provider implements OptionSourceInterface
{
    /**
     * @param ProviderPoolInterface $providerPool
     */
    public function __construct(
        private readonly ProviderPoolInterface $providerPool
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->providerPool->getAll() as $provider) {
            $options[] = ['value' => $provider->getCode(), 'label' => $provider->getLabel()];
        }

        return $options;
    }
}
