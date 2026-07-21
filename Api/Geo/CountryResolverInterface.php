<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Geo;

/**
 * Facade the rest of the module talks to: answers "which country is the
 * current visitor from?" via the admin-selected source.
 */
interface CountryResolverInterface
{
    /**
     * Uppercase ISO 3166-1 alpha-2 code for the current request, resolved via
     * the admin-selected source and memoized per request. Null when the source
     * is missing, unconfigured, or cannot determine the country.
     *
     * @return string|null
     */
    public function getCountryCode(): ?string;
}
