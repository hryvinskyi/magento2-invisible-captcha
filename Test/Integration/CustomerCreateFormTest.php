<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

/**
 * Storefront customer registration form (customer/account/createpost).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\CustomerCreate
 * Event:    controller_action_predispatch_customer_account_createpost
 *
 * @magentoConfigFixture default_store customer/captcha/enable 0
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class CustomerCreateFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'customer_create';
    private const EMAIL = 'dummy@dummy.com';

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Registry
     */
    private $registry;

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
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->registry = $this->_objectManager->get(Registry::class);
        $this->url = $this->_objectManager->get(UrlInterface::class);
    }

    /**
     * (a) The registration page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch('customer/account/create');
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) Registration goes through when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        self::assertNotNull($this->customerRepository->get(self::EMAIL)->getId());
    }

    /**
     * (c) Registration is rejected when verification fails; no customer is created.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_create 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect(self::stringContains('customer/account/create'));
        $this->assertCaptchaError();
        try {
            $this->customerRepository->get(self::EMAIL);
            self::fail('Customer should not have been created.');
        } catch (NoSuchEntityException $e) {
            // expected: registration was blocked
        }
    }

    /**
     * (d) Registration succeeds when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_customer_create 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        self::assertNotNull($this->customerRepository->get(self::EMAIL)->getId());
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $post = [
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'email' => self::EMAIL,
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'form_key' => $this->formKey->getFormKey(),
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('customer/account/createpost');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->deleteCustomer();
        parent::tearDown();
    }

    /**
     * @return void
     */
    private function deleteCustomer(): void
    {
        try {
            $customer = $this->customerRepository->get(self::EMAIL);
        } catch (NoSuchEntityException $e) {
            return;
        }
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', true);
        $this->customerRepository->delete($customer);
        $this->registry->unregister('isSecureArea');
        $this->registry->register('isSecureArea', false);
    }
}
