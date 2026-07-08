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

/**
 * Storefront contact form (contact/index/post).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\Contact
 * Event:    controller_action_predispatch_contact_index_post
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class ContactFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'contact';

    /**
     * (a) The contact page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch('contact/index');
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) The message is accepted when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertSessionMessages(
            self::containsEqual(
                "Thanks for contacting us with your comments and questions. We&#039;ll respond to you very soon."
            ),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertNoCaptchaError();
    }

    /**
     * (c) The message is rejected when verification fails.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_contact 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect(self::stringContains('contact/index'));
        $this->assertCaptchaError();
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_SUCCESS));
    }

    /**
     * (d) The message is accepted when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_contact 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertSessionMessages(
            self::containsEqual(
                "Thanks for contacting us with your comments and questions. We&#039;ll respond to you very soon."
            ),
            MessageInterface::TYPE_SUCCESS
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
            'name' => 'customer name',
            'comment' => 'comment',
            'email' => 'user@example.com',
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('contact/index/post');
    }
}
