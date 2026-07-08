<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Webapi;

/**
 * Identifies a REST/SOAP/GraphQL endpoint (by service class + method + route)
 * so a config provider can decide whether it needs captcha validation.
 */
interface EndpointInterface
{
    public function getServiceClass(): string;

    public function getServiceMethod(): string;

    public function getRoutePath(): string;
}
