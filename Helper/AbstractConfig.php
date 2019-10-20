<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Helper;

use Hryvinskyi\InvisibleCaptcha\Model\Area\ConfigInterface as AreaConfigInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Class AbstractConfig
 */
abstract class AbstractConfig extends AbstractHelper implements AreaConfigInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * General constructor.
     *
     * @param Context $context
     * @param ConfigInterface $config
     */
    public function __construct(
        Context $context,
        ConfigInterface $config = null
    ) {
        $this->config = $config ?: ObjectManager::getInstance()->get(ConfigInterface::class);

        parent::__construct($context);
    }

    /**
     * Is captcha enable
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    abstract public function hasEnabled(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool;

    /**
     * Disable captcha by area
     *
     * @param null|string $website
     */
    public function disableCaptcha(string $website = null): void
    {
        if (!$this->hasEnabled(ScopeInterface::SCOPE_WEBSITES, $website)) {
            return;
        }

        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;

        if ($website !== null) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $website;
        }

        $this->config->saveConfig(
            $this->disableConfigPath(),
            0,
            $scope,
            $scopeId
        );
    }
}
