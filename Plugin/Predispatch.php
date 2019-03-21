<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Plugin;

use \Hryvinskyi\InvisibleCaptcha\Helper\Config;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class Predispatch
{
    /**
     * Google URl for checking captcha response
     */
    const GOOGLE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var RedirectInterface
     */
    private $redirector;

    /**
     * Action constructor.
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param Config $config
     * @param Curl $curl
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $messageManager
     * @param RedirectInterface $redirector
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        Config $config,
        Curl $curl,
        UrlInterface $urlBuilder,
        ManagerInterface $messageManager,
        RedirectInterface $redirector
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->config = $config;
        $this->curl = $curl;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->redirector = $redirector;
    }

    /**
     * @param FrontControllerInterface $subject
     * @param RequestInterface $request
     *
     * @return void
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request
    ) {
        if ($this->config->hasEnable()) {

            foreach ($this->config->getCaptchaUrls() as $captchaUrl) {

                if (strpos($this->urlBuilder->getCurrentUrl(), $captchaUrl) !== false) {

                    if ($request->isPost()) {
                        $token = $request->getPost('hryvinskyi_invisible_token');
                        $validation = $this->verifyCaptcha($token);

                        if (!$validation) {
                            $this->messageManager->addErrorMessage(__('Invalid Recaptcha'));
                            $refererUrl = $this->redirector->getRefererUrl();

                            if(isset($refererUrl) && $refererUrl != '') {
                                header('Location: ' . $refererUrl);
                            }
                            die;
                        }
                    }
                    break;
                }
            }
        }
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function verifyCaptcha($token)
    {
        if ($token) {
            $curlParams = [
                'secret'   => $this->config->getSecretKey(),
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
