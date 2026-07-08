<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Webapi\ConfigProvider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Webapi\WebapiConfigProviderInterface;

/**
 * Shared enablement gate for WebAPI endpoint config providers.
 */
abstract class AbstractConfigProvider implements WebapiConfigProviderInterface
{
    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        protected readonly ConfigInterface $config
    ) {
    }

    /**
     * Return the form key only when captcha protection is active for it.
     */
    protected function keyIfEnabled(string $formKey): ?string
    {
        if ($this->config->isEnabled()
            && $this->config->isFormProtectionEnabled()
            && $this->config->isFormAreaEnabled(ConfigInterface::AREA_FRONTEND)
            && $this->config->isFormEnabled($formKey)
        ) {
            return $formKey;
        }

        return null;
    }
}
