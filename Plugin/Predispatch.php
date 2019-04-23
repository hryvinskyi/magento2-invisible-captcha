<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Plugin;

use Exception;
use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\ListCaptcha;
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
     * @var General
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
     * @var ListCaptcha
     */
    private $listCaptcha;

    /**
     * Action constructor.
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param General $config
     * @param Curl $curl
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $messageManager
     * @param RedirectInterface $redirector
     * @param ListCaptcha $listCaptcha
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        General $config,
        Curl $curl,
        UrlInterface $urlBuilder,
        ManagerInterface $messageManager,
        RedirectInterface $redirector,
        ListCaptcha $listCaptcha
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->config = $config;
        $this->curl = $curl;
        $this->urlBuilder = $urlBuilder;
        $this->messageManager = $messageManager;
        $this->redirector = $redirector;
        $this->listCaptcha = $listCaptcha;
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
        $token = Json::encode($_POST);
        $this->messageManager->addErrorMessage($token);exit;
        foreach ($this->listCaptcha->getList() as $captcha) {
            if ($captcha->isEnabled($this->urlBuilder->getCurrentUrl())) {
                if ($request->isPost()) {
                    $token = $request->getPost('hryvinskyi_invisible_token');
                    $validation = $this->verifyCaptcha($token);

                    if (!$validation) {
                        $this->messageManager->addErrorMessage(__('Invalid Recaptcha'));
//                        var_dump($request->isXmlHttpRequest());exit;
                        if ($request->isXmlHttpRequest()) {
                            break;
                        }
                        $refererUrl = $this->redirector->getRefererUrl();
                        if (isset($refererUrl) && $refererUrl != '') {
                            header('Location: ' . $refererUrl);
                        }

                        die;
                    }
                }

                break;
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
                'response' => $token,
            ];
            $this->curl->post(self::GOOGLE_VERIFY_URL, $curlParams);
            try {
                $answer = Json::decode($this->curl->getBody());
                if (($this->curl->getStatus() == 200) && array_key_exists('success', $answer)) {
                    return $answer['success'];
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }
}
