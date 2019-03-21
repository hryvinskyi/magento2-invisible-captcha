<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block;

use Hryvinskyi\InvisibleCaptcha\Helper\Config;
use Magento\Framework\View\Element\Template\Context;

class Captcha extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Captcha constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->config = $config;
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
    public function isModuleOn(): bool
    {
        return $this->config->hasEnable();
    }

    /**
     * @return string
     */
    public function getCaptchaSelectorsJson(): string
    {
        return \Zend_Json::encode($this->config->getCaptchaSelectors());
    }

    /**
     * @return mixed
     */
    public function getSiteKey(): string
    {
        return $this->config->getSiteKey();
    }
}
