<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block;

/**
 * Newsletter form captcha block. Uses a fixed jsLayout component key
 * ("invisible-captcha-newsletter") so the template can relocate the widget into
 * the newsletter form without the auto-generated per-instance scope.
 */
class CaptchaNewsletter extends Captcha
{
    private const COMPONENT_KEY = 'invisible-captcha-newsletter';

    /**
     * @inheritDoc
     */
    public function getJsLayout(): string
    {
        $layout = $this->decodeJsLayout();

        if ($this->isModuleOn() && isset($layout['components'][self::COMPONENT_KEY])) {
            $layout['components'][self::COMPONENT_KEY]['config'] = $this->getFormConfig();
        } elseif (isset($layout['components'][self::COMPONENT_KEY])) {
            unset($layout['components'][self::COMPONENT_KEY]);
        }

        return $this->encodeJsLayout($layout);
    }
}
