<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\Backend;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\Frontend;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use InvalidArgumentException;
use Magento\Framework\App\Area;

abstract class AbstractCheckEnabledVerify implements CheckEnabledVerifyInterface
{
    /**
     * @var string
     */
    private $area;

    /**
     * @var General
     */
    private $generalConfig;

    /**
     * @var Frontend
     */
    private $frontendConfig;

    /**
     * @var Backend
     */
    private $backendConfig;

    /**
     * AbstractCheckEnableVerify constructor.
     *
     * @param General $generalConfig
     * @param Frontend $frontendConfig
     * @param Backend $backendConfig
     * @param string $area
     */
    public function __construct(
        General $generalConfig,
        Frontend $frontendConfig,
        Backend $backendConfig,
        string $area
    ) {
        $this->generalConfig = $generalConfig;
        $this->frontendConfig = $frontendConfig;
        $this->backendConfig = $backendConfig;
        $this->area = $area;

        if (!in_array($area, [Area::AREA_FRONTEND, Area::AREA_ADMINHTML])) {
            throw new InvalidArgumentException('Area parameter must be one of frontend or adminhtml');
        }
    }

    /**
     * @return General
     */
    public function getGeneralConfig(): General
    {
        return $this->generalConfig;
    }

    /**
     * @return Frontend
     */
    public function getFrontendConfig(): Frontend
    {
        return $this->frontendConfig;
    }

    /**
     * @return Backend
     */
    public function getBackendConfig(): Backend
    {
        return $this->backendConfig;
    }

    /**
     * Return true if area is configured to be active
     *
     * @return bool
     */
    public function checkArea(): bool
    {
        $return = false;

        if ($this->getGeneralConfig()->hasEnabled() === false) {
            return $return;
        }

        switch ($this->area) {
            case Area::AREA_FRONTEND:
                $return = $this->getFrontendConfig()->hasEnabled();
                break;

            case Area::AREA_ADMINHTML:
                $return = $this->getBackendConfig()->hasEnabled();
                break;
        }

        return $return;
    }

    /**
     * @inheritDoc
     */
    public function verify(): bool
    {
        return $this->checkArea();
    }
}
