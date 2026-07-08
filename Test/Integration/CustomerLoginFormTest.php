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

/**
 * Storefront customer login form (customer/account/loginpost).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\Login
 * Event:    controller_action_predispatch_customer_account_loginPost
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoConfigFixture default_store customer/captcha/enable 0
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class CustomerLoginFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'customer_login';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->session = $this->_objectManager->get(Session::class);
        $this->url = $this->_objectManager->get(UrlInterface::class);
    }

    /**
     * (a) The login page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch('customer/account/login');
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) Login succeeds untouched when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        self::assertEquals(1, $this->session->getCustomerId());
    }

    /**
     * (c) Login is rejected (redirect + error, dispatch stopped) when verification fails.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_login 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);
        $this->session->setBeforeAuthUrl($this->url->getRouteUrl('customer/account/login'));

        $this->makePostRequest(true);

        $this->assertRedirect(self::stringStartsWith($this->url->getRouteUrl('customer/account/login')));
        $this->assertCaptchaError();
        self::assertNull($this->session->getCustomerId());
    }

    /**
     * (d) Login succeeds when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_login 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        self::assertEquals(1, $this->session->getCustomerId());
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $post = [
            'form_key' => $this->formKey->getFormKey(),
            'login' => [
                'username' => 'customer@example.com',
                'password' => 'password',
            ],
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('customer/account/loginpost');
    }
}
