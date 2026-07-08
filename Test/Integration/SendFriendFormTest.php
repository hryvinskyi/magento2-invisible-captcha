<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;

/**
 * Storefront "email to a friend" form (sendfriend/product/sendmail).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\SendFriend
 * Event:    controller_action_predispatch_sendfriend_product_sendmail
 *
 * @magentoDataFixture Magento/Catalog/_files/product_simple.php
 * @magentoConfigFixture default_store customer/captcha/enable 0
 * @magentoConfigFixture default_store sendfriend/email/enabled 1
 * @magentoConfigFixture default_store sendfriend/email/allow_guest 1
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class SendFriendFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'send_friend';
    private const PRODUCT_ID = 1;

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
        $this->transportMock = $this->_objectManager->get(TransportBuilderMock::class);
    }

    /**
     * (a) The share form renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch('sendfriend/product/send/id/' . self::PRODUCT_ID);
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) The link is sent when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertSessionMessages(
            self::containsEqual('The link to a friend was sent.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertNoCaptchaError();
        self::assertNotEmpty($this->transportMock->getSentMessage());
    }

    /**
     * (c) The link is rejected when verification fails; no email is sent.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_send_friend 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect();
        $this->assertCaptchaError();
        self::assertEmpty($this->transportMock->getSentMessage());
    }

    /**
     * (d) The link is sent when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_send_friend 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertSessionMessages(
            self::containsEqual('The link to a friend was sent.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertNoCaptchaError();
        self::assertNotEmpty($this->transportMock->getSentMessage());
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $expectedUrl = 'http://localhost/index.php/simple-product.html';

        $post = [
            'sender' => [
                'name' => 'Sender',
                'email' => 'sender@example.com',
                'message' => 'Message',
            ],
            'recipients' => [
                'name' => ['Recipient'],
                'email' => ['recipient@example.com'],
            ],
            'form_key' => $this->formKey->getFormKey(),
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()
            ->setMethod(Http::METHOD_POST)
            ->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $expectedUrl)
            ->setPostValue($post);

        $this->dispatch('sendfriend/product/sendmail/id/' . self::PRODUCT_ID);
    }
}
