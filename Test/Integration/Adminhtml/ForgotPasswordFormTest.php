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
use Magento\TestFramework\Mail\Template\TransportBuilderMock;

/**
 * Integration coverage for the admin forgot-password form protection.
 *
 * The forgot-password POST is guarded by the
 * {@see \Hryvinskyi\InvisibleCaptcha\Observer\Form\AdminForgot} virtualType bound
 * to `controller_action_predispatch_adminhtml_auth_forgotpassword`.
 *
 * Verification is made deterministic via a stubbed {@see HttpClientInterface}.
 * On failure the Redirect failure-strategy stops dispatch (no reset email is
 * sent); on success the controller emails a reset link captured by the test
 * transport mock. Turnstile is the active provider so success depends only on
 * the canned `success: true` payload.
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoDataFixture Magento/User/_files/user_with_role.php
 */
class ForgotPasswordFormTest extends AdminFormCaptchaTestCase
{
    private const USER_EMAIL = 'adminUser@example.com';

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

        $this->transportMock = $this->service(TransportBuilderMock::class);
    }

    /**
     * With protection disabled (defaults), the reset email is sent without a token.
     */
    public function testForgotPasswordAllowedWhenCaptchaDisabled(): void
    {
        $this->makeForgotPostRequest();

        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
        self::assertNotEmpty($this->transportMock->getSentMessage());
    }

    /**
     * A successful verification lets the reset email through.
     */
    public function testForgotPasswordAllowedWhenVerificationSucceeds(): void
    {
        $this->enableFormCaptcha('enabled_forgot');
        $this->verifyResponse = '{"success":true}';

        $this->makeForgotPostRequest([self::TOKEN_FIELD => 'valid-token']);

        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
        $message = $this->transportMock->getSentMessage();
        self::assertNotEmpty($message);
        self::assertEquals(
            (string)__('Password Reset Confirmation for %1', ['John Doe']),
            $message->getSubject()
        );
    }

    /**
     * A failed verification stops dispatch: the error is shown and no email sent.
     */
    public function testForgotPasswordBlockedWhenVerificationFails(): void
    {
        $this->enableFormCaptcha('enabled_forgot');
        $this->verifyResponse = '{"success":false,"error-codes":["invalid-input-response"]}';

        $this->makeForgotPostRequest([self::TOKEN_FIELD => 'tampered-token']);

        $this->assertSessionMessages(
            self::equalTo(['Captcha Error: The response parameter is invalid or malformed.']),
            MessageInterface::TYPE_ERROR
        );
        self::assertEmpty($this->transportMock->getSentMessage());
    }

    /**
     * A missing token short-circuits before any HTTP call and blocks the email.
     */
    public function testForgotPasswordBlockedWhenTokenMissing(): void
    {
        $this->enableFormCaptcha('enabled_forgot');

        $this->makeForgotPostRequest();

        $this->assertSessionMessages(
            self::equalTo(['Captcha Error: The response parameter is missing.']),
            MessageInterface::TYPE_ERROR
        );
        self::assertEmpty($this->transportMock->getSentMessage());
    }

    /**
     * POST the forgot-password form for the fixture user.
     *
     * @param array<string, string> $postValues
     * @return void
     */
    private function makeForgotPostRequest(array $postValues = []): void
    {
        $this->httpRequest()
            ->setMethod(Http::METHOD_POST)
            ->setPostValue(array_replace_recursive(
                [
                    'form_key' => $this->formKey->getFormKey(),
                    'email' => self::USER_EMAIL,
                ],
                $postValues
            ));

        $this->dispatch('backend/admin/auth/forgotpassword');
    }
}
