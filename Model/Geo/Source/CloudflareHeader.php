<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Geo\Source;

use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourceInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

/**
 * Reads the visitor's country from Cloudflare's `CF-IPCountry` edge header.
 *
 * Requires the zone's "IP Geolocation" toggle; always considered configured
 * because it needs no uploaded data.
 */
class CloudflareHeader implements CountrySourceInterface
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'cloudflare';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Cloudflare (CF-IPCountry header)');
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     *
     * Cloudflare injects the country in the `CF-IPCountry` header, so the
     * client IP is irrelevant here. `XX` (unknown) maps to null; `T1` (Tor)
     * is a valid two-char value and passes through.
     */
    public function resolve(string $clientIp): ?string
    {
        $country = strtoupper(trim((string)$this->request->getServer('HTTP_CF_IPCOUNTRY')));

        if ($country === 'XX' || preg_match('/^[A-Z0-9]{2}$/', $country) !== 1) {
            return null;
        }

        return $country;
    }
}
