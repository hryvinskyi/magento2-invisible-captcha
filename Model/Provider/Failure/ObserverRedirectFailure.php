<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider\Failure;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\FailureInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\UrlInterface;

class ObserverRedirectFailure implements FailureInterface
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
     * @param RedirectUrlInterface|null $redirectUrlProvider
     */
    public function __construct(
        MessageManagerInterface $messageManager,
        ActionFlag $actionFlag,
        General $config,
        UrlInterface $url,
        RedirectUrlInterface $redirectUrlProvider = null
    ) {
        $this->messageManager = $messageManager;
        $this->actionFlag = $actionFlag;
        $this->config = $config;
        $this->redirectUrlProvider = $redirectUrlProvider;
        $this->url = $url;
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
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function execute(ResponseInterface $response = null)
    {
        $this->messageManager->addErrorMessage($this->config->getValidationMessage());
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);

        $response->setRedirect($this->getUrl());
    }
}
