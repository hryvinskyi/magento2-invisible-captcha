<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Source;

use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourcePoolInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Country-source dropdown, driven by the registered country-source pool.
 */
class GeoSource implements OptionSourceInterface
{
    /**
     * @param CountrySourcePoolInterface $sourcePool
     */
    public function __construct(
        private readonly CountrySourcePoolInterface $sourcePool
    ) {
    }

    /**
     * @inheritDoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->sourcePool->getAll() as $source) {
            $options[] = ['value' => $source->getCode(), 'label' => $source->getLabel()];
        }

        return $options;
    }
}
