<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\AbstractFailure;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\FailureMessages;
use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Response;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;

class ObserverRedirectFailure extends AbstractFailure
{
    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var General
     */
    private $config;

    /**
     * @var RedirectUrlInterface
     */
    private $redirectUrlProvider;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * RedirectFailure constructor.
     *
     * @param MessageManagerInterface $messageManager
     * @param ActionFlag $actionFlag
     * @param General $config
     * @param UrlInterface $url
     * @param FailureMessages $failureMessages
     * @param RedirectUrlInterface|null $redirectUrlProvider
     */
    public function __construct(
        MessageManagerInterface $messageManager,
        ActionFlag $actionFlag,
        General $config,
        UrlInterface $url,
        FailureMessages $failureMessages,
        RedirectUrlInterface $redirectUrlProvider = null
    ) {
        $this->messageManager = $messageManager;
        $this->actionFlag = $actionFlag;
        $this->config = $config;
        $this->redirectUrlProvider = $redirectUrlProvider;
        $this->url = $url;

        parent::__construct($failureMessages);
    }

    /**
     * Get redirect URL
     *
     * @return string
     */
    private function getUrl()
    {
        return $this->redirectUrlProvider->getRedirectUrl();
    }

    /**
     * Handle captcha failure
     *
     * @param Response $verifyReCaptcha
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function execute(Response $verifyReCaptcha, ResponseInterface $response = null)
    {
        if ($response === null || !$response instanceof Http) {
            return;
        }

        $this->messageManager->addErrorMessage($this->getMessagesString($verifyReCaptcha));
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, /** @scrutinizer ignore-type */ true);

        $response->setRedirect($this->getUrl());
    }
}
