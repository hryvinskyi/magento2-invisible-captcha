<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\NoRouteActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class NoRouteAction implements NoRouteActionInterface
{
    /**
     * Config path and part fallbacks used by Magento's NoRouteHandler —
     * mirrored verbatim, including the "default" scope read.
     */
    private const XML_NO_ROUTE = 'web/default/no_route';
    private const FALLBACK_PARTS = ['core', 'index', 'index'];

    /** @var array{route: string, controller: string, action: string}|null */
    private ?array $routeParts = null;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getRouteParts(): array
    {
        if ($this->routeParts !== null) {
            return $this->routeParts;
        }

        $noRoutePath = trim((string)$this->scopeConfig->getValue(self::XML_NO_ROUTE, 'default'), '/');
        $segments = $noRoutePath !== '' ? explode('/', $noRoutePath) : [];

        return $this->routeParts = [
            'route' => ($segments[0] ?? '') !== '' ? strtolower($segments[0]) : self::FALLBACK_PARTS[0],
            'controller' => ($segments[1] ?? '') !== '' ? strtolower($segments[1]) : self::FALLBACK_PARTS[1],
            'action' => ($segments[2] ?? '') !== '' ? strtolower($segments[2]) : self::FALLBACK_PARTS[2],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFullActionName(): string
    {
        $parts = $this->getRouteParts();

        return $parts['route'] . '_' . $parts['controller'] . '_' . $parts['action'];
    }
}
