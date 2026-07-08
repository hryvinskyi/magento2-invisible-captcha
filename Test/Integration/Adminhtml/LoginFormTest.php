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
use Magento\TestFramework\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Integration coverage for the admin login form protection.
 *
 * Mirrors \Magento\ReCaptchaUser\Test\Integration\LoginFormTest, but wires the
 * Hryvinskyi_InvisibleCaptcha config tree and observers. The admin login form is
 * guarded by the {@see \Hryvinskyi\InvisibleCaptcha\Observer\Form\AdminLogin}
 * virtualType bound to the `admin_user_authenticate_before` event.
 *
 * Verification is made deterministic by replacing the outbound transport
 * ({@see HttpClientInterface}) with a mock that returns a canned siteverify JSON
 * payload, so login success/failure depends only on that payload. The active
 * provider is Turnstile (pass/fail, no score, no action) so a "success: true"
 * payload is sufficient for a pass.
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoAdminConfigFixture admin/captcha/enable 0
 * @magentoAdminConfigFixture admin/security/use_form_key 0
 */
class LoginFormTest extends AbstractBackendController
{
    private const TOKEN_FIELD = 'hryvinskyi_invisible_token';
    private const SECRET = 'test_secret_key';

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
        // AbstractBackendController::setUp() authenticates the default admin while
        // the module is still disabled (config defaults), so the auto-login is not
        // intercepted by the captcha observer.
        parent::setUp();

        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->mutableConfig = $this->_objectManager->get(MutableScopeConfig::class);
        $this->encryptor = $this->_objectManager->get(EncryptorInterface::class);

        /** @var HttpClientInterface&MockObject $httpClientMock */
        $httpClientMock = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $httpClientMock->method('post')->willReturnCallback(fn (): string => $this->verifyResponse);
        $this->_objectManager->addSharedInstance($httpClientMock, HttpClientInterface::class);
    }

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
        $this->enableLoginCaptcha();
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
        $this->enableLoginCaptcha();
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
        $this->enableLoginCaptcha();

        $this->makeLoginPostRequest();

        self::assertFalse($this->_auth->isLoggedIn());
        $this->assertSessionMessages(
            self::equalTo(['Captcha Error: The response parameter is missing.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Turn on the module, select Turnstile as the active provider, store its
     * (encrypted) credentials and enable the admin-login form gate.
     */
    private function enableLoginCaptcha(): void
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
        $this->setConfig('hryvinskyi_invisible_captcha/form_protection/backend/enabled_login', '1');
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
     * Log out the auto-authenticated admin and POST a fresh login attempt so the
     * backend authentication plugin runs the credential login (and thus the
     * captcha observer).
     *
     * @param array $postValues
     */
    private function makeLoginPostRequest(array $postValues = []): void
    {
        $this->_auth->logout();

        $this->getRequest()
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
