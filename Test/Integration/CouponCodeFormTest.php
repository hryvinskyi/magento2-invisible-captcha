<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;

/**
 * Storefront coupon apply form (checkout/cart/couponPost).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\CouponCode
 * Event:    controller_action_predispatch_checkout_cart_couponPost
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class CouponCodeFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'coupon_code';
    private const CUSTOMER_ID = 1;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->customerSession->setCustomerId(self::CUSTOMER_ID);
    }

    /**
     * (a) The cart page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->getRequest()->setMethod(Http::METHOD_GET);
        $this->dispatch('checkout/cart/');
        $content = $this->getResponse()->getContent();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) The coupon controller runs (captcha gate not triggered) when disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertRedirect();
        $this->assertNoCaptchaError();
    }

    /**
     * (c) The request is rejected by the captcha gate when verification fails.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_coupon_code 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect();
        $this->assertCaptchaError();
    }

    /**
     * (d) The captcha gate passes through to the coupon controller when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_coupon_code 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertRedirect();
        $this->assertNoCaptchaError();
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $post = [
            'remove' => 0,
            'coupon_code' => 'test',
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('checkout/cart/couponPost/');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->customerSession->setCustomerId(null);
        parent::tearDown();
    }
}
