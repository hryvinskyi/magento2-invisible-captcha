<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Failure;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationResultInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Json\EncoderInterface;

/**
 * Failure strategy for AJAX form submissions: stops dispatch and returns a JSON
 * error payload.
 */
class AjaxResponse extends AbstractFailure
{
    /**
     * @param ActionFlag $actionFlag
     * @param EncoderInterface $encoder
     * @param FailureMessages $failureMessages
     */
    public function __construct(
        private readonly ActionFlag $actionFlag,
        private readonly EncoderInterface $encoder,
        FailureMessages $failureMessages
    ) {
        parent::__construct($failureMessages);
    }

    /**
     * @inheritDoc
     */
    public function execute(VerificationResultInterface $result, ?ResponseInterface $response = null): void
    {
        if (!$response instanceof Http) {
            return;
        }

        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

        $response->representJson($this->encoder->encode([
            'errors' => true,
            'message' => $this->getMessagesString($result),
        ]));
    }
}
