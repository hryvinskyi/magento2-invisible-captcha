<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ScoreThreshold;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\Backend;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\Frontend;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\ScoreThresholdInterface;

/**
 * Class AbstractScoreThreshold
 */
abstract class AbstractScoreThreshold implements ScoreThresholdInterface
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
     * @var Backend
     */
    private $backendConfig;

    /**
     * AbstractScoreThreshold constructor.
     *
     * @param General $generalConfig
     * @param Frontend $frontendConfig
     * @param Backend $backendConfig
     */
    public function __construct(
        General $generalConfig,
        Frontend $frontendConfig,
        Backend $backendConfig
    ) {
        $this->generalConfig = $generalConfig;
        $this->frontendConfig = $frontendConfig;
        $this->backendConfig = $backendConfig;
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
}