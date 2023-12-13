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

class Captcha extends Template
{
    /**
     * @var int
     */
    private static $widgetId = 0;

    /**
     * @var string
     */
    private $widgetIdClass = '';
    private $widgetScope = '';

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

        parent::__construct($context, $data);

        $this->generalConfig = $generalConfig;
        $this->frontendConfig = $frontendConfig;
        $this->layoutSettings = $layoutSettings;
    }

    /**
     * Constructor
     */
    public function _construct()
    {
        parent::_construct();

        $this->widgetIdClass = 'invisible-captcha-container-' . ++self::$widgetId;
        $this->widgetScope = 'invisible-captcha-scope-' . ++self::$widgetId;
    }

    /**
     * @return string
     */
    public function getWidgetId(): string
    {
        return $this->widgetIdClass;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->widgetScope;
    }

    /**
     * @inheritdoc
     */
    public function getJsLayout()
    {
        $layout = Json::decode(parent::getJsLayout());
        $layout['components'][$this->getScope()] = $layout['components']['invisible-captcha'];
        unset($layout['components']['invisible-captcha']);

        if ($this->frontendConfig->hasEnabled() && $this->isModuleOn()) {
            $layout['components'][$this->getScope()]['config'] = $this->layoutSettings->getCaptchaSettings();
        }

        if (
            (!$this->frontendConfig->hasEnabled() || !$this->isModuleOn())
            && isset($layout['components'][$this->getScope()])
        ) {
            unset($layout['components'][$this->getScope()]);
        }

        return Json::encode($layout);
    }

    /**
     * @return bool
     */
    public function isModuleOn(): bool
    {
        return $this->generalConfig->hasEnabled();
    }

    /**
     * @return bool
     */
    public function isLazyLoad(): bool
    {
        return $this->generalConfig->isLazyLoad();
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
