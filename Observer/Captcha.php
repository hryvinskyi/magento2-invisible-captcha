<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Observer;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\CaptchaInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\VerifyReCaptcha;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Captcha implements ObserverInterface
{
    /**
     * @var General
     */
    private $config;

    /**
     * @var VerifyReCaptcha
     */
    private $verifyReCaptcha;

    /**
     * @var CaptchaInterface
     */
    private $provider;

    /**
     * Action constructor.
     *
     * @param General $config
     * @param VerifyReCaptcha $verifyReCaptcha
     * @param CaptchaInterface $provider
     */
    public function __construct(
        General $config,
        VerifyReCaptcha $verifyReCaptcha,
        CaptchaInterface $provider
    ) {
        $this->config = $config;
        $this->verifyReCaptcha = $verifyReCaptcha;
        $this->provider = $provider;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->provider->isEnabled() && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            $verifyReCaptcha = $this->verifyReCaptcha
                ->setSecret($this->config->getSecretKey())
                ->setExpectedAction($this->provider->getAction())
                ->setScoreThreshold($this->provider->getScoreThreshold())
                ->verify($this->provider->getToken());

            if ($verifyReCaptcha->isSuccess() === false) {
                /** @var Action|null $controller */
                $controller = $observer->getData('controller_action');
                $this->provider->getFailure()->execute($verifyReCaptcha, $controller ? $controller->getResponse() : null);
            }
        }
    }
}
