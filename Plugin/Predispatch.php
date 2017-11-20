<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\InvisibleCaptcha\Plugin;

use \Script\InvisibleCaptcha\Helper\Data;

class Predispatch
{
    /**
     * Google URl for checking captcha response
     */
    const GOOGLE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var \Magento\Backend\Model\View\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirector;

    /**
     * Action constructor.
     * @param \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory
     * @param \Script\InvisibleCaptcha\Helper\Data $helper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\Response\RedirectInterface $redirector
     */
    public function __construct(
        \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirectFactory,
        \Script\InvisibleCaptcha\Helper\Data $helper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Response\RedirectInterface $redirector
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->redirector = $redirector;
    }

    /**
     * @param \Magento\Framework\App\FrontControllerInterface $subject
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function beforeDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Magento\Framework\App\RequestInterface $request
    ) {
        if ($this->helper->isModuleOn()) {
            foreach ($this->helper->getCaptchaUrls() as $captchaUrl) {
                if (strpos($this->urlBuilder->getCurrentUrl(), $captchaUrl) !== false) {
                    if ($request->isPost()) {
                        $token = $request->getPost('script_invisible_token');
                        $validation = $this->verifyCaptcha($token);
                        if (!$validation) {
                            $this->messageManager->addErrorMessage(__('Something is wrong'));
                            $refererUrl = $this->redirector->getRefererUrl();
                            if(isset($refererUrl) && $refererUrl != '') {
                                header('Location: ' . $refererUrl);
                            }
                            exit;
                        }
                    }
                    break;
                }
            }
        }
    }

    protected function verifyCaptcha($token)
    {
        if ($token) {
            $curlParams = [
                'secret' => $this->helper->getConfigValueByPath(Data::CONFIG_PATH_GENERAL_SECRET_KEY),
                'response' => $token
            ];
            $this->curl->post(self::GOOGLE_VERIFY_URL, $curlParams);
            try {
                if (($this->curl->getStatus() == 200)
                    && array_key_exists('success', $answer = \Zend_Json::decode($this->curl->getBody()))
                ) {
                    return $answer['success'];
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
}
