<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\Adminhtml;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\HttpClientInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\Bootstrap;

/**
 * Integration coverage for the admin login form protection.
 *
 * The admin login form is guarded by the
 * {@see \Hryvinskyi\InvisibleCaptcha\Observer\Form\AdminLogin} virtualType bound to
 * the `admin_user_authenticate_before` event.
 *
 * Verification is made deterministic by replacing the outbound transport
 * ({@see HttpClientInterface}) with a stub that returns a canned siteverify JSON
 * payload, so login success/failure depends only on that payload. The active
 * provider is Turnstile (pass/fail, no score, no action) so a "success: true"
 * payload is sufficient for a pass.
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class LoginFormTest extends AdminFormCaptchaTestCase
{
    /**
     * With protection disabled (defaults), login proceeds without any token.
     */
    public function testLoginAllowedWhenCaptchaDisabled(): void
    {
        $this->makeLoginPostRequest();

        self::assertTrue($this->_auth->isLoggedIn());
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * A successful server-side verification lets the admin authenticate.
     */
    public function testLoginAllowedWhenVerificationSucceeds(): void
    {
        $this->enableFormCaptcha('enabled_login');
        $this->verifyResponse = '{"success":true}';

        $this->makeLoginPostRequest([self::TOKEN_FIELD => 'valid-token']);

        self::assertTrue($this->_auth->isLoggedIn());
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * A failed server-side verification blocks authentication and surfaces the
     * captcha error message.
     */
    public function testLoginBlockedWhenVerificationFails(): void
    {
        $this->enableFormCaptcha('enabled_login');
        $this->verifyResponse = '{"success":false,"error-codes":["invalid-input-response"]}';

        $this->makeLoginPostRequest([self::TOKEN_FIELD => 'tampered-token']);

        self::assertFalse($this->_auth->isLoggedIn());
        $this->assertSessionMessages(
            self::equalTo(['Captcha Error: The response parameter is invalid or malformed.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * A missing token short-circuits before any HTTP call and blocks the login.
     */
    public function testLoginBlockedWhenTokenMissing(): void
    {
        $this->enableFormCaptcha('enabled_login');

        $this->makeLoginPostRequest();

        self::assertFalse($this->_auth->isLoggedIn());
        $this->assertSessionMessages(
            self::equalTo(['Captcha Error: The response parameter is missing.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Log out the auto-authenticated admin and POST a fresh login attempt so the
     * backend authentication plugin runs the credential login (and thus the
     * captcha observer).
     *
     * @param array<string, string> $postValues
     * @return void
     */
    private function makeLoginPostRequest(array $postValues = []): void
    {
        $this->_auth->logout();

        $this->httpRequest()
            ->setMethod(Http::METHOD_POST)
            ->setPostValue(array_replace_recursive(
                [
                    'form_key' => $this->formKey->getFormKey(),
                    'login' => [
                        'username' => Bootstrap::ADMIN_NAME,
                        'password' => Bootstrap::ADMIN_PASSWORD,
                    ],
                ],
                $postValues
            ));

        $this->dispatch('backend/admin/index/index');
    }
}
