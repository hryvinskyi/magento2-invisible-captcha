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
 * Protects the apply-coupon WebAPI (REST) and GraphQL mutations.
 */
class SalesRule extends AbstractConfigProvider
{
    private const REST_METHODS = [
        'set', // Magento\Quote\Api\CouponManagementInterface::set / GuestCouponManagementInterface::set
    ];

    private const REST_CLASSES = [
        'Magento\Quote\Api\CouponManagementInterface',
        'Magento\Quote\Api\GuestCouponManagementInterface',
    ];

    private const GRAPHQL_RESOLVERS = [
        'Magento\QuoteGraphQl\Model\Resolver\ApplyCouponToCart',
    ];

    /**
     * @inheritDoc
     */
    public function getFormKeyFor(EndpointInterface $endpoint): ?string
    {
        $isRestCoupon = in_array($endpoint->getServiceMethod(), self::REST_METHODS, true)
            && in_array($endpoint->getServiceClass(), self::REST_CLASSES, true);

        if ($isRestCoupon || in_array($endpoint->getServiceClass(), self::GRAPHQL_RESOLVERS, true)) {
            return $this->keyIfEnabled(ConfigInterface::FORM_COUPON_CODE);
        }

        return null;
    }
}
