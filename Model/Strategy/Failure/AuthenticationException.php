<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\Plugin\AuthenticationException as PluginAuthenticationException;

/**
 * Failure strategy for the admin login flow: throws an authentication exception
 * so Magento's auth controller renders the error inline.
 */
class AuthenticationException extends AbstractFailure
{
    /**
     * @inheritDoc
     * @throws PluginAuthenticationException
     */
    public function execute(VerificationResultInterface $result, ?ResponseInterface $response = null): void
    {
        throw new PluginAuthenticationException(__($this->getMessagesString($result)));
    }
}
