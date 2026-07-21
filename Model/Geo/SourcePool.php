<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Geo;

use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourceInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourcePoolInterface;

class SourcePool implements CountrySourcePoolInterface
{
    /** @var array<string, CountrySourceInterface> */
    private readonly array $sources;

    /**
     * @param array<string, CountrySourceInterface> $sources Map of code => source, injected via di.xml.
     */
    public function __construct(array $sources = [])
    {
        $normalized = [];
        foreach ($sources as $source) {
            if ($source instanceof CountrySourceInterface) {
                $normalized[$source->getCode()] = $source;
            }
        }
        $this->sources = $normalized;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return $this->sources;
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): ?CountrySourceInterface
    {
        return $this->sources[$code] ?? null;
    }
}
