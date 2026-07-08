<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Integration;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Review\Model\ResourceModel\Review as ReviewResourceModel;

/**
 * Storefront product review form (review/product/post).
 *
 * Observer: Hryvinskyi\InvisibleCaptcha\Observer\Form\ProductReview
 * Event:    controller_action_predispatch_review_product_post
 *
 * @magentoDataFixture Magento/Catalog/_files/product_simple.php
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class ProductReviewFormTest extends AbstractFormCaptchaTest
{
    private const FORM = 'product_review';
    private const PRODUCT_ID = 1;

    /**
     * @var ReviewResourceModel
     */
    private $reviewResourceModel;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reviewResourceModel = $this->_objectManager->get(ReviewResourceModel::class);
    }

    /**
     * (a) The product page renders when the module is disabled.
     */
    public function testGetRequestRendersWhenDisabled(): void
    {
        $this->disable();

        $this->dispatch('/simple-product.html');
        $content = $this->getResponse()->getBody();

        self::assertNotEmpty($content);
        self::assertStringNotContainsString('field-recaptcha', $content);
        self::assertEmpty($this->getSessionMessages(MessageInterface::TYPE_ERROR));
    }

    /**
     * (b) The review is accepted when the captcha is disabled.
     */
    public function testPostPassesThroughWhenDisabled(): void
    {
        $this->disable();

        $this->makePostRequest(false);

        $this->assertSessionMessages(
            self::containsEqual('You submitted your review for moderation.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertNoCaptchaError();
        self::assertEquals(1, $this->reviewResourceModel->getTotalReviews(self::PRODUCT_ID));
    }

    /**
     * (c) The review is rejected when verification fails; nothing is stored.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_product_review 1
     */
    public function testPostIsRejectedWhenVerificationFails(): void
    {
        $this->enableCaptcha(self::FORM, false);

        $this->makePostRequest(true);

        $this->assertRedirect();
        $this->assertCaptchaError();
        self::assertEquals(0, $this->reviewResourceModel->getTotalReviews(self::PRODUCT_ID));
    }

    /**
     * (d) The review is accepted when verification passes.
     *
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/general/active_provider turnstile
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled 1
     * @magentoConfigFixture default_store hryvinskyi_invisible_captcha/form_protection/frontend/enabled_product_review 1
     */
    public function testPostSucceedsWhenVerificationPasses(): void
    {
        $this->enableCaptcha(self::FORM, true);

        $this->makePostRequest(true);

        $this->assertSessionMessages(
            self::containsEqual('You submitted your review for moderation.'),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertNoCaptchaError();
        self::assertEquals(1, $this->reviewResourceModel->getTotalReviews(self::PRODUCT_ID));
    }

    /**
     * @param bool $withToken
     * @return void
     */
    private function makePostRequest(bool $withToken): void
    {
        $expectedRedirectUrl = 'http://localhost/index.php/simple-product.html';

        $post = [
            'form_key' => $this->formKey->getFormKey(),
            'nickname' => 'review_author',
            'title' => 'review_title',
            'detail' => 'review_detail',
        ];
        if ($withToken) {
            $post[self::TOKEN_FIELD] = 'test-token';
        }

        $this->getRequest()
            ->setMethod(Http::METHOD_POST)
            ->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $expectedRedirectUrl)
            ->setPostValue($post);

        $this->dispatch('review/product/post/id/' . self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->reviewResourceModel->deleteReviewsByProductId(self::PRODUCT_ID);
        parent::tearDown();
    }
}
