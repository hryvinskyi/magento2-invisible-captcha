<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\HttpClientInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token\RequestParam;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Base class for storefront form captcha integration tests.
 *
 * Provides the helpers used by every form test:
 *  - enableModule()/enableForm()/disable() flip this module's own config tree
 *    (hryvinskyi_invisible_captcha/*) deterministically via MutableScopeConfig;
 *  - setProviderKeys() stores a plain site key and an EncryptorInterface-encrypted
 *    secret so the active provider reports isConfigured() === true;
 *  - mockVerification() swaps the outbound HTTP transport for a stub so the
 *    server-side siteverify result is deterministic without any real network call.
 *
 * The active provider is Cloudflare Turnstile (isScoreBased() === false,
 * supportsAction() === false) so the verification verdict depends only on the
 * mocked JSON body.
 */
abstract class AbstractFormCaptchaTest extends AbstractController
{
    /** Neutral hidden field the storefront JS populates for every provider. */
    public const TOKEN_FIELD = RequestParam::DEFAULT_FIELD;

    /** Provider used for verification tests (pass/fail depends only on the JSON). */
    protected const PROVIDER = 'turnstile';

    /** User-facing message produced for the mocked "invalid-input-response" code. */
    protected const CAPTCHA_ERROR = 'Captcha Error: The response parameter is invalid or malformed.';

    private const XML_GENERAL_ENABLED = 'hryvinskyi_invisible_captcha/general/enabled';
    private const XML_ACTIVE_PROVIDER = 'hryvinskyi_invisible_captcha/general/active_provider';
    private const XML_FORM_ENABLED = 'hryvinskyi_invisible_captcha/form_protection/enabled';
    private const XML_FORM_FRONTEND_ENABLED = 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled';
    private const XML_FORM_ENABLED_PREFIX = 'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_';
    private const XML_PROVIDER_PREFIX = 'hryvinskyi_invisible_captcha/providers/';

    /**
     * @var MutableScopeConfig
     */
    protected $mutableConfig;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Config paths mutated during a test, reset on tearDown.
     *
     * @var array<int, string>
     */
    private $changedPaths = [];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mutableConfig = $this->_objectManager->get(MutableScopeConfig::class);
        $this->formKey = $this->_objectManager->get(FormKey::class);
        $this->encryptor = $this->_objectManager->get(EncryptorInterface::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        foreach ($this->changedPaths as $path) {
            $this->mutableConfig->setValue($path, null, ScopeInterface::SCOPE_STORE);
        }
        $this->changedPaths = [];
        parent::tearDown();
    }

    /**
     * Set a store-scoped config value and remember it for cleanup.
     *
     * @param string $path
     * @param string|null $value
     * @return void
     */
    protected function setConfig(string $path, ?string $value): void
    {
        $this->mutableConfig->setValue($path, $value, ScopeInterface::SCOPE_STORE);
        $this->changedPaths[] = $path;
    }

    /**
     * Turn the module on and select the active provider.
     *
     * @param string $activeProvider
     * @return void
     */
    protected function enableModule(string $activeProvider = self::PROVIDER): void
    {
        $this->setConfig(self::XML_GENERAL_ENABLED, '1');
        $this->setConfig(self::XML_ACTIVE_PROVIDER, $activeProvider);
        $this->setConfig(self::XML_FORM_ENABLED, '1');
        $this->setConfig(self::XML_FORM_FRONTEND_ENABLED, '1');
    }

    /**
     * Enable protection for a single storefront form.
     *
     * @param string $formKey One of ConfigInterface::FORM_* values.
     * @return void
     */
    protected function enableForm(string $formKey): void
    {
        $this->setConfig(self::XML_FORM_ENABLED_PREFIX . $formKey, '1');
    }

    /**
     * Turn the module (and form protection) fully off.
     *
     * @return void
     */
    protected function disable(): void
    {
        $this->setConfig(self::XML_GENERAL_ENABLED, '0');
        $this->setConfig(self::XML_FORM_ENABLED, '0');
        $this->setConfig(self::XML_FORM_FRONTEND_ENABLED, '0');
    }

    /**
     * Store a plain site key and an encrypted secret for the given provider so
     * the provider resolves as configured (secret decrypts to a non-empty value).
     *
     * @param string $code
     * @return void
     */
    protected function setProviderKeys(string $code = self::PROVIDER): void
    {
        $this->setConfig(self::XML_PROVIDER_PREFIX . $code . '/site_key', 'test_site_key');
        $this->setConfig(
            self::XML_PROVIDER_PREFIX . $code . '/secret_key',
            $this->encryptor->encrypt('test_secret_key')
        );
    }

    /**
     * Replace the outbound HTTP transport with a stub returning a fixed
     * siteverify payload (pass or fail) so verification is deterministic.
     *
     * @param bool $pass
     * @return void
     */
    protected function mockVerification(bool $pass): void
    {
        $body = $pass
            ? '{"success":true}'
            : '{"success":false,"error-codes":["invalid-input-response"]}';

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->method('post')->willReturn($body);
        $this->_objectManager->addSharedInstance($httpClientMock, HttpClientInterface::class);
    }

    /**
     * Convenience: fully enable a protected form and pin the verification verdict.
     *
     * @param string $formKey
     * @param bool $pass
     * @return void
     */
    protected function enableCaptcha(string $formKey, bool $pass): void
    {
        $this->enableModule();
        $this->enableForm($formKey);
        $this->setProviderKeys();
        $this->mockVerification($pass);
    }

    /**
     * Assert the storefront produced the captcha rejection error.
     *
     * @return void
     */
    protected function assertCaptchaError(): void
    {
        $this->assertSessionMessages(
            self::containsEqual(self::CAPTCHA_ERROR),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Assert the captcha rejection error is NOT present (the form passed the gate).
     *
     * @return void
     */
    protected function assertNoCaptchaError(): void
    {
        self::assertNotContains(
            self::CAPTCHA_ERROR,
            $this->getSessionMessages(MessageInterface::TYPE_ERROR)
        );
    }
}
