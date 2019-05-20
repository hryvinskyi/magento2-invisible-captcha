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
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Json\EncoderInterface;

class AjaxResponseFailure implements FailureInterface
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
     */
    public function __construct(
        ActionFlag $actionFlag,
        EncoderInterface $encoder,
        General $config
    ) {
        $this->actionFlag = $actionFlag;
        $this->encoder = $encoder;
        $this->config = $config;
    }

    /**
     * Handle captcha failure
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function execute(ResponseInterface $response = null)
    {
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

        $jsonPayload = $this->encoder->encode([
            'errors'  => true,
            'message' => __($this->config->getValidationMessage()),
        ]);

        $response->representJson($jsonPayload);
    }
}
