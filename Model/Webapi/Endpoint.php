<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Webapi;

use Hryvinskyi\InvisibleCaptcha\Api\Webapi\EndpointInterface;

/**
 * Immutable endpoint descriptor. Instantiate via the auto-generated
 * EndpointFactory.
 */
class Endpoint implements EndpointInterface
{
    /**
     * @param string $class Service class (REST) or resolver class (GraphQL).
     * @param string $method Service method ('resolve' for GraphQL).
     * @param string $name Route path / GraphQL field name.
     */
    public function __construct(
        private readonly string $class,
        private readonly string $method,
        private readonly string $name
    ) {
    }

    public function getServiceClass(): string
    {
        return $this->class;
    }

    public function getServiceMethod(): string
    {
        return $this->method;
    }

    public function getRoutePath(): string
    {
        return $this->name;
    }
}
