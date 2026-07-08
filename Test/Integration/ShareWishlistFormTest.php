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
use Magento\Framework\UrlInterface;
use Magento\TestFramework\Wishlist\Model\GetWishlistByCustomerId;

/**
 * Storefront "share wish list" form (wishlist/index/send).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\Wishlist
 * Event:    controller_action_predispatch_wishlist_index_send
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class ShareWishlistFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'wishlist';
    private const CUSTOMER_ID = 1;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var int
     */
    private $wishlistId;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customerSession = $this->_objectManager->get(Session::class);
        $this->customerSession->setCustomerId(self::CUSTOMER_ID);
        $this->url = $this->_objectManager->get(UrlInterface::class);
        $this->wishlistId = (int)$this->_objectManager->get(GetWishlistByCustomerId::class)
            ->execute(self::CUSTOMER_ID)
            ->getId();
    }

    /**
     * (a) The share page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch('wishlist/index/share/wishlist_id/' . $this->wishlistId);
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) Sharing goes through when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertRedirect(
            self::equalTo($this->url->getRouteUrl('wishlist/index/index/wishlist_id/' . $this->wishlistId . '/'))
        );
        $this->assertNoCaptchaError();
    }

    /**
     * (c) Sharing is rejected when verification fails.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_wishlist 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect();
        $this->assertCaptchaError();
    }

    /**
     * (d) Sharing succeeds when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_wishlist 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertRedirect(
            self::equalTo($this->url->getRouteUrl('wishlist/index/index/wishlist_id/' . $this->wishlistId . '/'))
        );
        $this->assertNoCaptchaError();
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $post = [
            'form_key' => $this->formKey->getFormKey(),
            'emails' => 'example1@example.com, example2@example.com, example3@example.com',
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('wishlist/index/send/wishlist_id/' . $this->wishlistId);
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
