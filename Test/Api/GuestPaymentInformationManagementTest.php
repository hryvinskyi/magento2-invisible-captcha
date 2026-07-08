<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Api;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\HttpClientInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\TestFramework\App\MutableScopeConfig;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Verifies that the guest place-order REST endpoint is guarded by the
 * Hryvinskyi InvisibleCaptcha module when `place_order` form protection is on.
 *
 * Mirrors {@see \Magento\ReCaptchaCheckout\Test\Api\GuestPaymentInformationManagementTest}
 * but wires to this module's config tree (hryvinskyi_invisible_captcha/*) and to
 * the X-Captcha-Token request header consumed by
 * {@see \Hryvinskyi\InvisibleCaptcha\Plugin\Webapi\RestValidationPlugin}.
 */
class GuestPaymentInformationManagementTest extends WebapiAbstract
{
    private const API_ROUTE = '/V1/guest-carts/%s/payment-information';

    /** Provider response that passes verification (turnstile is pass/fail only). */
    private const PASS_RESPONSE = '{"success":true}';

    /** Provider response that fails verification. */
    private const FAIL_RESPONSE = '{"success":false,"error-codes":["invalid-input-response"]}';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var MutableScopeConfig
     */
    private $mutableConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_markTestAsRestOnly();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->mutableConfig = $this->objectManager->get(MutableScopeConfig::class);
        $this->encryptor = $this->objectManager->get(EncryptorInterface::class);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->objectManager->removeSharedInstance(HttpClientInterface::class);
        parent::tearDown();
    }

    /**
     * Without the X-Captcha-Token header the protected endpoint must be rejected
     * with a 400 web API exception before the order is ever placed.
     *
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_check_payment.php
     * @magentoConfigFixture default_store customer/captcha/enable 0
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_place_order 1
     */
    public function testRequired(): void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/Captcha validation failed, please try again\./');

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();
        $quote->load('test_order_1', 'reserved_order_id');
        $cartId = $quote->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(self::API_ROUTE, $cartId),
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => null,
            ],
        ];

        $this->_webApiCall($serviceInfo, $this->buildRequestData($quote, $cartId));
    }

    /**
     * With a valid X-Captcha-Token header and a mocked-pass HTTP transport the
     * captcha gate is satisfied and the order is placed (an order id is returned).
     *
     * The verification transport is replaced with a deterministic mock so the
     * outcome depends only on the mocked siteverify JSON and never on a real
     * network call. This relies on the WebAPI request being dispatched within the
     * same process as the test bootstrap.
     *
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_check_payment.php
     */
    public function testPlacesOrderWithValidToken(): void
    {
        $this->configureCaptcha();
        $this->mockHttpClient(self::PASS_RESPONSE);

        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();
        $quote->load('test_order_1', 'reserved_order_id');

        /** @var QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->objectManager->create(QuoteIdMask::class);
        $quoteIdMask->load($quote->getId(), 'quote_id');
        $cartId = $quoteIdMask->getMaskedId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => sprintf(self::API_ROUTE, $cartId),
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => null,
                'headers' => ['X-Captcha-Token: valid-captcha-token'],
            ],
        ];

        $orderId = $this->_webApiCall($serviceInfo, $this->buildRequestData($quote, $cartId));

        $this->assertIsNumeric($orderId);
        $this->assertGreaterThan(0, (int)$orderId);
    }

    /**
     * Enable the module's place-order protection with the turnstile provider and
     * store an encrypted secret so the provider reports itself as configured.
     */
    private function configureCaptcha(): void
    {
        $values = [
            'customer/captcha/enable' => '0',
            'hryvinskyi_invisible_captcha/general/enabled' => '1',
            'hryvinskyi_invisible_captcha/general/active_provider' => ProviderInterface::CODE_TURNSTILE,
            'hryvinskyi_invisible_captcha/form_protection/enabled' => '1',
            'hryvinskyi_invisible_captcha/form_protection/frontend/enabled' => '1',
            'hryvinskyi_invisible_captcha/form_protection/frontend/enabled_' . ConfigInterface::FORM_PLACE_ORDER => '1',
            'hryvinskyi_invisible_captcha/providers/turnstile/site_key' => 'test_site_key',
            'hryvinskyi_invisible_captcha/providers/turnstile/secret_key' => $this->encryptor->encrypt('test_secret_key'),
        ];

        foreach ($values as $path => $value) {
            $this->mutableConfig->setValue($path, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }
    }

    /**
     * Replace the outbound verification transport with a mock returning a fixed
     * siteverify body, so verification is deterministic and offline.
     */
    private function mockHttpClient(string $response): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('post')->willReturn($response);
        $this->objectManager->addSharedInstance($client, HttpClientInterface::class);
    }

    /**
     * Build the place-order request payload from the fixture quote, mirroring the
     * native Magento ReCaptcha checkout test.
     *
     * @param Quote $quote
     * @param int|string $cartId
     * @return array<string, mixed>
     */
    private function buildRequestData(Quote $quote, $cartId): array
    {
        $payment = $quote->getPayment();
        $address = $quote->getBillingAddress();
        $addressData = [];
        $addressProperties = [
            'city', 'company', 'countryId', 'firstname', 'lastname', 'postcode',
            'region', 'regionCode', 'regionId', 'saveInAddressBook', 'street', 'telephone', 'email',
        ];
        foreach ($addressProperties as $property) {
            $method = 'get' . $property;
            $addressData[$property] = $address->$method();
        }

        return [
            'cart_id' => $cartId,
            'billingAddress' => $addressData,
            'email' => $quote->getCustomerEmail(),
            'paymentMethod' => [
                'additional_data' => $payment->getAdditionalData(),
                'method' => $payment->getMethod(),
                'po_number' => $payment->getPoNumber(),
            ],
        ];
    }
}
