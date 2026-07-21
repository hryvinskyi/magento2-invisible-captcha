<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Geo;

use Magento\Framework\Phrase;

/**
 * A strategy that determines the visitor's country for the current request —
 * e.g. the Cloudflare edge header or a lookup in an uploaded MaxMind database.
 *
 * Implementations are registered in the {@see CountrySourcePoolInterface}
 * registry via di.xml so other modules can contribute their own.
 */
interface CountrySourceInterface
{
    /**
     * Stable machine code used in the admin config to identify the source.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Human-readable label shown in the admin Source dropdown.
     *
     * @return Phrase
     */
    public function getLabel(): Phrase;

    /**
     * Whether the source is ready to resolve (e.g. its database is uploaded).
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Uppercase ISO 3166-1 alpha-2 code for the given client IP, or null when
     * unknown. Never throws — failures degrade to null.
     *
     * @param string $clientIp
     * @return string|null
     */
    public function resolve(string $clientIp): ?string;
}
