<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Webapi;

/**
 * Maps a web API endpoint to the captcha form key that protects it.
 *
 * Implement (and register in the composite via DI) to add captcha protection to
 * additional REST/GraphQL endpoints. The module's extension seam for WebAPI.
 */
interface WebapiConfigProviderInterface
{
    /**
     * @return string|null The protecting form key (see ConfigInterface::FORM_*),
     *                      or null when the endpoint needs no validation / is disabled.
     */
    public function getFormKeyFor(EndpointInterface $endpoint): ?string;
}
