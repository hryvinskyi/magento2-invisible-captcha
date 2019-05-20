<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\FailureInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\Plugin\AuthenticationException;

class AuthenticationExceptionFailure implements FailureInterface
{
    /**
     * @var General
     */
    private $config;

    /**
     * AuthenticationExceptionFailure constructor.
     *
     * @param General $config
     */
    public function __construct(
        General $config
    ) {
        $this->config = $config;
    }

    /**
     * Handle captcha failure
     *
     * @param ResponseInterface $response
     *
     * @return void
     * @throws AuthenticationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(ResponseInterface $response = null)
    {
        throw new AuthenticationException(__($this->config->getValidationMessage()));
    }
}
