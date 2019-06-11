<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\AbstractFailure;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\FailureMessages;
use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Response;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\Plugin\AuthenticationException;

class AuthenticationExceptionFailure extends AbstractFailure
{
    /**
     * @var General
     */
    private $config;

    /**
     * AuthenticationExceptionFailure constructor.
     *
     * @param FailureMessages $failureMessages
     * @param General $config
     */
    public function __construct(
        FailureMessages $failureMessages,
        General $config
    ) {
        parent::__construct($failureMessages);

        $this->config = $config;
    }

    /**
     * Handle captcha failure
     *
     * @param Response $verifyReCaptcha
     * @param ResponseInterface $response
     *
     * @return void
     * @throws AuthenticationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Response $verifyReCaptcha, ResponseInterface $response = null)
    {
        throw new AuthenticationException(__($this->getMessagesString($verifyReCaptcha)));
    }
}
