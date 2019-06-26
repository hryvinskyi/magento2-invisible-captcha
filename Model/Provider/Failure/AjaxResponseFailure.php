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
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Json\EncoderInterface;

class AjaxResponseFailure extends AbstractFailure
{
    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var General
     */
    private $config;

    /**
     * AjaxResponseFailure constructor.
     *
     * @param ActionFlag $actionFlag
     * @param EncoderInterface $encoder
     * @param General $config
     * @param FailureMessages $failureMessages
     */
    public function __construct(
        ActionFlag $actionFlag,
        EncoderInterface $encoder,
        General $config,
        FailureMessages $failureMessages
    ) {
        $this->actionFlag = $actionFlag;
        $this->encoder = $encoder;
        $this->config = $config;

        parent::__construct($failureMessages);
    }

    /**
     * Handle captcha failure
     *
     * @param Response $verifyReCaptcha
     * @param ResponseInterface|null $response
     *
     * @return void
     */
    public function execute(Response $verifyReCaptcha, ResponseInterface $response = null)
    {
        if ($response === null) {
            return;
        }

        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

        $jsonPayload = $this->encoder->encode([
            'errors'  => true,
            'message' => $this->getMessagesString($verifyReCaptcha),
        ]);

        $response->representJson($jsonPayload);
    }
}
