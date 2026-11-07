<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * Storefront customer account edit form (customer/account/editpost).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\CustomerEdit
 * Event:    controller_action_predispatch_customer_account_editPost
 *
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class CustomerEditFormTest extends FormCaptchaTestCase
{
    private const FORM = 'customer_edit';

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

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
        $this->disableImageCaptcha();
        $this->customerRepository = $this->service(CustomerRepositoryInterface::class);
        $this->session = $this->service(Session::class);
        $this->url = $this->service(UrlInterface::class);
    }

    /**
     * (a) The edit page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();
        $this->session->loginById(1);

        $this->dispatch('customer/account/edit');
        $content = $this->responseBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) The profile is updated when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();
        $this->session->loginById(1);

        $this->makePostRequest(false);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        $customer = $this->customerRepository->getById(1);
        self::assertEquals('Test First Name', $customer->getFirstname());
        self::assertEquals('Test Last Name', $customer->getLastname());
    }

    /**
     * (c) The update is rejected when verification fails; profile is unchanged.
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);
        $this->session->loginById(1);

        $this->makePostRequest(true);

        $this->assertRedirect();
        $this->assertCaptchaError();
        $customer = $this->customerRepository->getById(1);
        self::assertEquals('John', $customer->getFirstname());
        self::assertEquals('Smith', $customer->getLastname());
    }

    /**
     * (d) The update succeeds when verification passes.
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);
        $this->session->loginById(1);

        $this->makePostRequest(true);

        $this->assertRedirect(self::equalTo($this->url->getRouteUrl('customer/account')));
        $this->assertNoCaptchaError();
        $customer = $this->customerRepository->getById(1);
        self::assertEquals('Test First Name', $customer->getFirstname());
        self::assertEquals('Test Last Name', $customer->getLastname());
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $post = [
            'form_key' => $this->formKey->getFormKey(),
            'firstname' => 'Test First Name',
            'lastname' => 'Test Last Name',
            'email' => 'customer@example.com',
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->httpRequest()->setMethod(Http::METHOD_POST)->setPostValue($post);
        $this->dispatch('customer/account/editpost');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->session->logout();
        parent::tearDown();
    }
}
