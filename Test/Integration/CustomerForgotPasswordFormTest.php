<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\UrlInterface;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;

/**
 * Storefront forgot-password form (customer/account/forgotpasswordpost).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\CustomerForgot
 * Event:    controller_action_predispatch_customer_account_forgotpasswordpost
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoConfigFixture default_store customer/captcha/enable 0
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class CustomerForgotPasswordFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'customer_forgot';

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var TransportBuilderMock
     */
    private $transportMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->url = $this->_objectManager->get(UrlInterface::class);
        $this->transportMock = $this->_objectManager->get(TransportBuilderMock::class);
    }

    /**
     * (a) The forgot-password page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch('customer/account/forgotpassword');
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) The reset email is sent when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        self::assertNotEmpty($this->transportMock->getSentMessage());
    }

    /**
     * (c) The request is rejected when verification fails; no email is sent.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_forgot 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect(self::stringContains('customer/account/forgotpassword'));
        $this->assertCaptchaError();
        self::assertEmpty($this->transportMock->getSentMessage());
    }

    /**
     * (d) The reset email is sent when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_forgot 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        self::assertNotEmpty($this->transportMock->getSentMessage());
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $post = [
            'email' => 'customer@example.com',
            'form_key' => $this->formKey->getFormKey(),
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('customer/account/forgotpasswordpost');
    }
}
