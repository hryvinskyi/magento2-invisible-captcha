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
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * Storefront newsletter subscription form (newsletter/subscriber/new).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\Newsletter
 * Event:    controller_action_predispatch_newsletter_subscriber_new
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class NewsletterFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'newsletter';
    private const EMAIL = 'user@example.com';

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->url = $this->_objectManager->get(UrlInterface::class);
        $this->subscriberFactory = $this->_objectManager->get(SubscriberFactory::class);
    }

    /**
     * (a) The home page (with the newsletter block) renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch($this->url->getRouteUrl());
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) Subscription goes through when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertSessionMessages(
            self::containsEqual('Thank you for your subscription.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertNoCaptchaError();
        self::assertNotEmpty($this->getSubscriberId());
    }

    /**
     * (c) Subscription is rejected when verification fails; no subscriber is stored.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_newsletter 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect();
        $this->assertCaptchaError();
        self::assertEmpty($this->getSubscriberId());
    }

    /**
     * (d) Subscription succeeds when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_newsletter 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertSessionMessages(
            self::containsEqual('Thank you for your subscription.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertNoCaptchaError();
        self::assertNotEmpty($this->getSubscriberId());
    }

    /**
     * @return int|null
     */
    private function getSubscriberId(): ?int
    {
        $id = $this->subscriberFactory->create()->loadBySubscriberEmail(self::EMAIL, 1)->getId();

        return $id ? (int)$id : null;
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $post = [
            'form_key' => $this->formKey->getFormKey(),
            'email' => self::EMAIL,
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('newsletter/subscriber/new');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->subscriberFactory->create()->loadBySubscriberEmail(self::EMAIL, 1)->delete();
        parent::tearDown();
    }
}
