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

class CheckEnabledVerify extends AbstractCheckEnabledVerify
{
    /**
     * @var string|null
     */
    private $configPath;

    /**
     * CheckEnabledVerify constructor.
     *
     * @param General $generalConfig
     * @param Frontend $frontendConfig
     * @param Backend $backendConfig
     * @param string $area
     * @param string|null $configPath
     */
    public function __construct(
        General $generalConfig,
        Frontend $frontendConfig,
        Backend $backendConfig,
        string $area,
        ?string $configPath
    ) {
        parent::__construct(
            $generalConfig,
            $frontendConfig,
            $backendConfig,
            $area
        );

        $this->configPath = $configPath;
    }

    /**
     * @inheritDoc
     */
    public function verify(): bool
    {
        if ($this->configPath) {
            return parent::verify() && !!$this->getGeneralConfig()->getConfigValueByPath($this->configPath);
        }

        return parent::verify();
    }
}
