<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Webapi\ConfigProvider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Webapi\EndpointInterface;

/**
 * Protects the resend-confirmation-email GraphQL mutation.
 */
class ResendConfirmationEmail extends AbstractConfigProvider
{
    private const GRAPHQL_RESOLVERS = [
        'Magento\CustomerGraphQl\Model\Resolver\ResendConfirmationEmail',
    ];

    /**
     * @inheritDoc
     */
    public function getFormKeyFor(EndpointInterface $endpoint): ?string
    {
        if (in_array($endpoint->getServiceClass(), self::GRAPHQL_RESOLVERS, true)) {
            return $this->keyIfEnabled(ConfigInterface::FORM_RESEND_CONFIRMATION_EMAIL);
        }

        return null;
    }
}
