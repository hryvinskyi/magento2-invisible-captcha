<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */


namespace Script\InvisibleCaptcha\Block;

use \Script\InvisibleCaptcha\Helper\Data;

class Captcha extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * Captcha constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Produce and return block's html output
     *
     * This method should not be overridden. You can override _toHtml() method in descendants if needed.
     *
     * @return string
     */
    public function toHtml()
    {
        if (!$this->isModuleOn()) {
            return '';
        }
        return parent::toHtml();
    }

    /**
     * @return bool
     */
    public function isModuleOn()
    {
        return $this->helper->isModuleOn();
    }

    /**
     * @return string
     */
    public function getCaptchaSelectorsJson()
    {
        $selectors = trim($this->helper->getConfigValueByPath(
            Data::CONFIG_PATH_DISPLAY_ALLOWED_SELECTORS,
            null,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));

        return \Zend_Json::encode($selectors ? $this->helper->stringValidationAndCovertInArray($selectors) : []);
    }

    /**
     * @return mixed
     */
    public function getSiteKey()
    {
        return $this->helper->getConfigValueByPath(
            Data::CONFIG_PATH_GENERAL_SITE_KEY,
            null,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
