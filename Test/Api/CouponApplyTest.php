<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Api;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Quote\Test\Fixture\GuestCart;
use Magento\Quote\Test\Fixture\QuoteIdMask;
use Magento\SalesRule\Test\Fixture\Rule as SalesRuleFixture;
use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Throwable;

/**
 * Verifies that the apply-coupon REST endpoints are guarded by the Hryvinskyi
 * InvisibleCaptcha module when `coupon_code` form protection is enabled.
 *
 * Mirrors {@see \Magento\ReCaptchaCheckoutSalesRule\Test\Api\CouponApplyFormRecaptchaTest}
 * but wires to this module's config tree (hryvinskyi_invisible_captcha/*). The
 * captcha gate is enforced by
 * {@see \Hryvinskyi\InvisibleCaptcha\Plugin\Webapi\RestValidationPlugin} which
 * protects Magento\Quote\Api\CouponManagementInterface::set and
 * Magento\Quote\Api\GuestCouponManagementInterface::set.
 *
 * Only the enable flags are configured: protection gating does not require the
 * provider to be fully configured, and a missing X-Captcha-Token header fails
 * validation before the coupon is ever applied.
 */
class CouponApplyTest extends WebapiAbstract
{
    private const API_ROUTE = '/V1/carts/mine/coupons/%s';

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_markTestAsRestOnly();
        $this->quoteFactory = Bootstrap::getObjectManager()->get(QuoteFactory::class);
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    /**
     * Customer cart: applying a coupon without the X-Captcha-Token header is
     * rejected with a 400 web API exception.
     */
    #[
        ConfigFixture('customer/captcha/enable', 0),
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/general/active_provider', 'turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled_coupon_code', 1),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(
            AddProductToCart::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$product.id$', 'qty' => 5],
            'cart_item'
        ),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart.id$']),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 5.00,
                'coupon_type' => 2,
                'simple_action' => 'by_fixed'
            ],
            'sales_rule'
        )
    ]
    public function testRequired(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/.*Captcha validation failed, please try again.*/');

        $this->_webApiCall(
            [
                'rest' => [
                    'resourcePath' => sprintf(
                        self::API_ROUTE,
                        $this->fixtures->get('sales_rule')->getCouponCode()
                    ),
                    'httpMethod' => Request::HTTP_METHOD_PUT,
                    'token' => $this->getCustomerAuthToken(
                        $this->fixtures->get('customer')->getEmail()
                    ),
                ],
            ],
            [
                'cart_id' => $this->fixtures->get('cart')->getId()
            ]
        );
    }

    /**
     * Guest cart: applying a coupon without the X-Captcha-Token header is
     * rejected with a 400 web API exception.
     */
    #[
        ConfigFixture('customer/captcha/enable', 0),
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/general/active_provider', 'turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled_coupon_code', 1),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(GuestCart::class, as: 'quote'),
        DataFixture(QuoteIdMask::class, ['cart_id' => '$quote.id$'], 'cart_mask'),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(
            AddProductToCart::class,
            ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 5],
            'cart_item'
        ),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(
            SalesRuleFixture::class,
            [
                'coupon_code' => 'coupon%uniqid%',
                'discount_amount' => 5.00,
                'coupon_type' => 2,
                'simple_action' => 'by_fixed'
            ],
            'sales_rule'
        )
    ]
    public function testGuestCartTest(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/.*Captcha validation failed, please try again.*/');

        $cartId = $this->fixtures->get('cart_mask')->getMaskedId();
        $couponCode = $this->fixtures->get('sales_rule')->getCouponCode();
        $this->_webApiCall(
            [
                'rest' => [
                    'resourcePath' => "/V1/guest-carts/$cartId/coupons/" . $couponCode,
                    'httpMethod' => Request::HTTP_METHOD_PUT,
                    'token' => null
                ],
            ],
            []
        );
    }

    /**
     * Get customer authentication token.
     *
     * @param string $email
     * @return string
     * @throws AuthenticationException
     * @throws EmailNotConfirmedException
     */
    private function getCustomerAuthToken(string $email): string
    {
        return $this->customerTokenService->createCustomerAccessToken($email, 'password');
    }
}
