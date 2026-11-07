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
use Hryvinskyi\InvisibleCaptcha\Test\Integration\ControllerTestAccess;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager as TestObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Shared setup for the admin form captcha tests.
 *
 * - The module reads every one of its settings at store scope, and the test
 *   config answers a store-scope read only from a value written at store scope:
 *   a default-scope write is invisible to it, so the gate would never engage.
 *   {@see enableFormCaptcha()} therefore writes at store scope.
 * - The built-in admin image captcha reads its switch at default scope, and
 *   this framework applies config fixture annotations declared on a test
 *   method only, so it is switched off here, at runtime, for every test.
 * - The outbound HTTP transport is replaced by a stub answering
 *   {@see $verifyResponse}. The container resolves an interface through its
 *   preference before it looks up a shared instance, so the stub is registered
 *   under the class the preference of {@see HttpClientInterface} names;
 *   registered under the interface name it would never be handed out. It is
 *   registered before the parent setUp() logs the admin in, because that login
 *   already builds the verifier with whatever transport the container holds.
 */
abstract class AdminFormCaptchaTestCase extends AbstractBackendController
{
    use ControllerTestAccess;

    protected const TOKEN_FIELD = 'hryvinskyi_invisible_token';
    protected const SECRET = 'test_secret_key';

    private const XML_IMAGE_CAPTCHA_ENABLED = 'admin/captcha/enable';

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var MutableScopeConfig
     */
    protected $mutableConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Canned siteverify response body returned by the stubbed HTTP transport.
     *
     * @var string
     */
    protected $verifyResponse = '{"success":true}';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        // The stub is registered before AbstractBackendController::setUp(): its
        // admin auto-login dispatches the event the login gate observes, which
        // builds the verifier, and a verifier built then keeps the transport it
        // was given for the rest of the test.
        $objectManager = Bootstrap::getObjectManager();
        self::assertInstanceOf(TestObjectManager::class, $objectManager);
        $httpClientStub = $this->createMock(HttpClientInterface::class);
        $httpClientStub->method('post')->willReturnCallback(fn (): string => $this->verifyResponse);
        $objectManager->addSharedInstance($httpClientStub, HttpClientInterface::class, true);

        // The auto-login runs while the module is still disabled (config
        // defaults), so it is not intercepted by the captcha observer.
        parent::setUp();

        $this->formKey = $this->service(FormKey::class);
        $this->mutableConfig = $this->service(MutableScopeConfig::class);
        $this->encryptor = $this->service(EncryptorInterface::class);

        $this->mutableConfig->setValue(
            self::XML_IMAGE_CAPTCHA_ENABLED,
            '0',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Turn on the module, select Turnstile as the active provider, store its
     * (encrypted) credentials and enable one admin form gate.
     *
     * @param string $formField Field of the admin form gate, e.g. `enabled_login`.
     * @return void
     */
    protected function enableFormCaptcha(string $formField): void
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
        $this->setConfig('hryvinskyi_invisible_captcha/form_protection/backend/' . $formField, '1');
    }

    /**
     * Write a store-scope config value for the store the request is served in.
     *
     * @param string $path
     * @param string $value
     * @return void
     */
    private function setConfig(string $path, string $value): void
    {
        $this->mutableConfig->setValue($path, $value, ScopeInterface::SCOPE_STORE);
    }
}
