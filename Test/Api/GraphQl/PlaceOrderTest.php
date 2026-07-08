<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Api\GraphQl;

use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * GraphQL test for the checkout place-order mutations with Hryvinskyi InvisibleCaptcha enabled.
 *
 * When form protection is enabled for place_order with the turnstile provider (pass/fail), both the
 * placeOrder and the deprecated setPaymentMethodAndPlaceOrder mutations are rejected by
 * Hryvinskyi\InvisibleCaptcha\Plugin\Webapi\GraphQlValidator when no captcha token is supplied.
 * The validator runs in beforeResolve, so the request fails before the cart is ever loaded; the
 * dummy cart_id therefore never reaches the quote layer.
 */
class PlaceOrderTest extends GraphQlAbstract
{
    #[
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/general/active_provider', 'turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled_place_order', 1)
    ]
    public function testPlaceOrderCaptchaValidationFailed(): void
    {
        $this->expectExceptionMessage('Captcha validation failed, please try again.');
        $this->graphQlMutation($this->getPlaceOrderMutation('non_existent_masked_cart_id'));
    }

    #[
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/general/active_provider', 'turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled_place_order', 1)
    ]
    public function testSetPaymentMethodAndPlaceOrderCaptchaValidationFailed(): void
    {
        $this->expectExceptionMessage('Captcha validation failed, please try again.');
        $this->graphQlMutation($this->getSetPaymentAndPlaceOrderMutation('non_existent_masked_cart_id'));
    }

    /**
     * @param string $cartId
     * @return string
     */
    private function getPlaceOrderMutation(string $cartId): string
    {
        return <<<MUTATION
mutation {
    placeOrder(input: {cart_id: "{$cartId}"}) {
        order {
            order_number
        }
    }
}
MUTATION;
    }

    /**
     * @param string $cartId
     * @return string
     */
    private function getSetPaymentAndPlaceOrderMutation(string $cartId): string
    {
        return <<<MUTATION
mutation {
    setPaymentMethodAndPlaceOrder(input: {
        cart_id: "{$cartId}",
        payment_method: {
            code: "checkmo"
        }
    }) {
        order {
            order_number
        }
    }
}
MUTATION;
    }
}
