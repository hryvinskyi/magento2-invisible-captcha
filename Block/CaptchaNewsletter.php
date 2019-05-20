<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block;

use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\Frontend;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\LayoutSettings;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class CaptchaNewsletter extends Captcha
{
    /**
     * @var General
     */
    private $generalConfig;

    /**
     * @var Frontend
     */
    private $frontendConfig;

    /**
     * @var LayoutSettings
     */
    private $layoutSettings;

    /**
     * Captcha constructor.
     *
     * @param Context $context
     * @param General $generalConfig
     * @param Frontend $frontendConfig
     * @param LayoutSettings $layoutSettings
     * @param array $data
     */
    public function __construct(
        Context $context,
        General $generalConfig,
        Frontend $frontendConfig,
        LayoutSettings $layoutSettings,
        array $data = []
    ) {
        parent::__construct($context, $generalConfig, $frontendConfig, $layoutSettings, $data);

        $this->generalConfig = $generalConfig;
        $this->frontendConfig = $frontendConfig;
        $this->layoutSettings = $layoutSettings;
    }

    /**
     * @inheritdoc
     */
    public function getJsLayout()
    {
        $layout = $this->jsLayout;

        if ($this->frontendConfig->hasEnabled() && $this->isModuleOn()) {
            $layout['components']['invisible-captcha-newsletter']['config'] = $this->layoutSettings->getCaptchaSettings();
        }

        if (
            (!$this->frontendConfig->hasEnabled() || !$this->isModuleOn())
            && isset($layout['components']['invisible-captcha-newsletter'])
        ) {
            unset($layout['components']['invisible-captcha-newsletter']);
        }

        return Json::encode($layout);
    }

    /**
     * @return bool
     */
    public function isModuleOn(): bool
    {
        return $this->generalConfig->hasEnabled() || $this->frontendConfig->hasEnabledNewsletter();
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if (!$this->isModuleOn()) {
            return '';
        }

        return parent::toHtml();
    }
}
