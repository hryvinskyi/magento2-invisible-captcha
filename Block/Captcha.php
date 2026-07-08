<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Renders an invisible-captcha container + jsLayout for a protected form,
 * provider-agnostically.
 */
class Captcha extends Template
{
    private static int $widgetSequence = 0;

    private string $widgetIdClass = '';
    private string $widgetScope = '';

    /** @var array<string, mixed>|null */
    private ?array $formConfig = null;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param ClientConfigProvider $clientConfigProvider
     * @param Json $json
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly ConfigInterface $config,
        private readonly ClientConfigProvider $clientConfigProvider,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        parent::_construct();

        $this->widgetIdClass = 'invisible-captcha-container-' . ++self::$widgetSequence;
        $this->widgetScope = 'invisible-captcha-scope-' . ++self::$widgetSequence;
    }

    public function getWidgetId(): string
    {
        return $this->widgetIdClass;
    }

    public function getScope(): string
    {
        return $this->widgetScope;
    }

    /**
     * @inheritDoc
     */
    public function getJsLayout(): string
    {
        $layout = $this->decodeJsLayout();

        if (isset($layout['components']['invisible-captcha'])) {
            $layout['components'][$this->getScope()] = $layout['components']['invisible-captcha'];
            unset($layout['components']['invisible-captcha']);
        }

        if ($this->isModuleOn() && isset($layout['components'][$this->getScope()])) {
            $layout['components'][$this->getScope()]['config'] = $this->getFormConfig();
        } elseif (isset($layout['components'][$this->getScope()])) {
            unset($layout['components'][$this->getScope()]);
        }

        return $this->encodeJsLayout($layout);
    }

    /**
     * Decode the configured jsLayout into an array.
     *
     * @return array<string, mixed>
     */
    protected function decodeJsLayout(): array
    {
        $decoded = $this->json->unserialize(parent::getJsLayout());

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $layout
     */
    protected function encodeJsLayout(array $layout): string
    {
        return $this->json->serialize($layout);
    }

    public function isModuleOn(): bool
    {
        return $this->config->isEnabled();
    }

    public function isLazyLoad(): bool
    {
        return $this->config->isLazyLoad();
    }

    public function isDisabledSubmitForm(): bool
    {
        return $this->config->isDisableSubmitForm();
    }

    public function isHideBadge(): bool
    {
        return (bool)($this->getFormConfig()['hideBadge'] ?? false);
    }

    public function getHideBadgeText(): string
    {
        return (string)($this->getFormConfig()['hideBadgeText'] ?? '');
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        if (!$this->isModuleOn()) {
            return '';
        }

        return parent::toHtml();
    }

    /**
     * Resolve (and cache) the active provider's client form config.
     *
     * @return array<string, mixed>
     */
    protected function getFormConfig(): array
    {
        if ($this->formConfig === null) {
            $this->formConfig = $this->clientConfigProvider->getFormConfig();
        }

        return $this->formConfig;
    }
}
