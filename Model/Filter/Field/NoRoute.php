<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\NoRouteActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

/**
 * Resolves to 1 when the request dispatched to the configured 404 no-route
 * action (`web/default/no_route`, cms_noroute_index by default) — URLs that
 * exist nowhere on the site. Real visitors rarely hit 404s; bots probing
 * paths hit them constantly, so `is_404 eq 1` is a strong challenge signal.
 */
class NoRoute implements FieldInterface
{
    /**
     * @param NoRouteActionInterface $noRouteAction
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly NoRouteActionInterface $noRouteAction,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'is_404';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Is 404 (No-Route) Page');
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::TYPE_BOOLEAN;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): int
    {
        if (!$this->request instanceof HttpRequest) {
            return 0;
        }

        $fullActionName = (string)$this->request->getFullActionName();

        return strcasecmp($fullActionName, $this->noRouteAction->getFullActionName()) === 0 ? 1 : 0;
    }
}
