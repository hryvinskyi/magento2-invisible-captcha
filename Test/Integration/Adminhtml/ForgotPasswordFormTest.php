<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration\Adminhtml;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\HttpClientInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\TestFramework\TestCase\AbstractBackendController;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Integration coverage for the admin forgot-password form protection.
 *
 * Mirrors \Magento\ReCaptchaUser\Test\Integration\ForgotPasswordFormTest, but
 * wires the Hryvinskyi_InvisibleCaptcha config tree and observers. The
 * forgot-password POST is guarded by the
 * {@see \Hryvinskyi\InvisibleCaptcha\Observer\Form\AdminForgot} virtualType bound
 * to `controller_action_predispatch_adminhtml_auth_forgotpassword`.
 *
 * Verification is made deterministic via a mocked {@see HttpClientInterface}.
 * On failure the Redirect failure-strategy stops dispatch (no reset email is
 * sent); on success the controller emails a reset link captured by the test
 * transport mock. Turnstile is the active provider so success depends only on
 * the canned `success: true` payload.
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoAdminConfigFixture admin/captcha/enable 0
 * @magentoDataFixture Magento/User/_files/user_with_role.php
 */
class ForgotPasswordFormTest extends AbstractBackendController
{
    private const TOKEN_FIELD = 'hryvinskyi_invisible_token';
    private const SECRET = 'test_secret_key';
    private const USER_EMAIL = 'adminUser@example.com';

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var MutableScopeConfig
     */
    private $mutableConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var TransportBuilderMock
     */
    private $transportMock;

    /**
     * Canned siteverify response body returned by the mocked HTTP transport.
     *
     * @var string
     */
    private $verifyResponse = '{"success":true}';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->mutableConfig = $this->_objectManager->get(MutableScopeConfig::class);
        $this->encryptor = $this->_objectManager->get(EncryptorInterface::class);
        $this->transportMock = $this->_objectManager->get(TransportBuilderMock::class);

        /** @var HttpClientInterface&MockObject $httpClientMock */
        $httpClientMock = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpClientMock->method('post')->willReturnCallback(fn (): string => $this->verifyResponse);
        $this->_objectManager->addSharedInstance($httpClientMock, HttpClientInterface::class);
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
        $this->enableForgotCaptcha();
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
        $this->enableForgotCaptcha();
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
        $this->enableForgotCaptcha();

        $this->makeForgotPostRequest();

        $this->assertSessionMessages(
            self::equalTo(['Captcha Error: The response parameter is missing.']),
            MessageInterface::TYPE_ERROR
        );
        self::assertEmpty($this->transportMock->getSentMessage());
    }

    /**
     * Turn on the module, select Turnstile, store credentials and enable the
     * admin forgot-password form gate.
     */
    private function enableForgotCaptcha(): void
    {
        $this->setConfig('hryvinskyi_invisible_captcha/general/enabled', '1');
        $this->setConfig('hryvinskyi_invisible_captcha/general/active_provider', ProviderInterface::CODE_TURNSTILE);
        $this->setConfig('hryvinskyi_invisible_captcha/providers/turnstile/site_key', 'test_site_key');
        $this->setConfig(
            'hryvinskyi_invisible_captcha/providers/turnstile/secret_key',
            $this->encryptor->encrypt(self::SECRET)
        );
        $this->setConfig('hryvinskyi_invisible_captcha/form_protection/enabled', '1');
        $this->setConfig('hryvinskyi_invisible_captcha/form_protection/backend/enabled', '1');
        $this->setConfig('hryvinskyi_invisible_captcha/form_protection/backend/enabled_forgot', '1');
    }

    /**
     * Persist a default-scope config value via the mutable test config.
     *
     * @param string $path
     * @param string $value
     */
    private function setConfig(string $path, string $value): void
    {
        $this->mutableConfig->setValue($path, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * POST the forgot-password form for the fixture user.
     *
     * @param array $postValues
     */
    private function makeForgotPostRequest(array $postValues = []): void
    {
        $this->getRequest()
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
