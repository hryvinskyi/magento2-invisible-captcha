<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Api\GraphQl;

use Magento\TestFramework\Fixture\Config as ConfigFixture;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * GraphQL test for the resendConfirmationEmail mutation with Hryvinskyi InvisibleCaptcha enabled.
 *
 * Mirrors Magento\ReCaptchaResendConfirmationEmail\Test\Api\GraphQl, but wired to this module's
 * config tree and to the turnstile provider (pass/fail, not score-based). With form protection
 * enabled for resend_confirmation_email, a mutation submitted without a captcha token is rejected
 * by Hryvinskyi\InvisibleCaptcha\Plugin\Webapi\GraphQlValidator before any customer logic runs.
 */
class ResendConfirmationEmailTest extends GraphQlAbstract
{
    #[
        ConfigFixture('hryvinskyi_invisible_captcha/general/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/general/active_provider', 'turnstile'),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled', 1),
        ConfigFixture('hryvinskyi_invisible_captcha/form_protection/frontend/enabled_resend_confirmation_email', 1)
    ]
    public function testResendConfirmationEmailCaptchaValidationFailed(): void
    {
        $this->expectExceptionMessage('Captcha validation failed, please try again.');
        $this->graphQlMutation($this->getQuery('test@example.com'));
    }

    /**
     * @param string $email
     * @return string
     */
    private function getQuery(string $email): string
    {
        return <<<QUERY
mutation {
    resendConfirmationEmail(
        email: "{$email}"
    )
}
QUERY;
    }
}
