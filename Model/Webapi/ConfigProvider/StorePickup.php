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
 * Protects the in-store-pickup place-order WebAPI.
 */
class StorePickup extends AbstractConfigProvider
{
    private const REST_METHODS = [
        'savePaymentInformationAndPlaceOrder',
    ];

    private const REST_CLASSES = [
        'Magento\InStorePickupQuote\Api\PaymentInformationManagementInterface',
        'Magento\InStorePickupQuoteGraphQl\Model\Resolver\SetPaymentMethodAndPlaceOrder',
    ];

    /**
     * @inheritDoc
     */
    public function getFormKeyFor(EndpointInterface $endpoint): ?string
    {
        if (in_array($endpoint->getServiceClass(), self::REST_CLASSES, true)
            && in_array($endpoint->getServiceMethod(), array_merge(self::REST_METHODS, ['resolve']), true)
        ) {
            return $this->keyIfEnabled(ConfigInterface::FORM_STORE_PICKUP);
        }

        return null;
    }
}
