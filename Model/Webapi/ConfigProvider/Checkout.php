<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Webapi\ConfigProvider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Webapi\EndpointInterface;

/**
 * Protects the place-order WebAPI (REST) and GraphQL mutations.
 */
class Checkout extends AbstractConfigProvider
{
    private const REST_METHODS = [
        'savePaymentInformationAndPlaceOrder',
    ];

    private const GRAPHQL_RESOLVERS = [
        'Magento\QuoteGraphQl\Model\Resolver\SetPaymentAndPlaceOrder',
        'Magento\QuoteGraphQl\Model\Resolver\PlaceOrder',
    ];

    /**
     * @inheritDoc
     */
    public function getFormKeyFor(EndpointInterface $endpoint): ?string
    {
        if (in_array($endpoint->getServiceMethod(), self::REST_METHODS, true)
            || in_array($endpoint->getServiceClass(), self::GRAPHQL_RESOLVERS, true)
        ) {
            return $this->keyIfEnabled(ConfigInterface::FORM_PLACE_ORDER);
        }

        return null;
    }
}
