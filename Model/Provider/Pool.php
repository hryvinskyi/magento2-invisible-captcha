<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Exception\ProviderNotFoundException;

/**
 * Registry of providers (DI-configured array keyed by code) plus resolution of
 * the active / route-gate / fallback providers from configuration.
 */
class Pool implements ProviderPoolInterface
{
    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    /**
     * @param ConfigInterface $config
     * @param ProviderInterface[] $providers
     */
    public function __construct(
        private readonly ConfigInterface $config,
        array $providers = []
    ) {
        foreach ($providers as $provider) {
            if (!$provider instanceof ProviderInterface) {
                throw new \InvalidArgumentException(
                    'Captcha provider must implement ' . ProviderInterface::class
                );
            }
            $this->providers[$provider->getCode()] = $provider;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return $this->providers;
    }

    /**
     * @inheritDoc
     */
    public function get(string $code): ProviderInterface
    {
        if (!isset($this->providers[$code])) {
            throw new ProviderNotFoundException(__('Unknown captcha provider "%1".', $code));
        }

        return $this->providers[$code];
    }

    /**
     * @inheritDoc
     */
    public function has(string $code): bool
    {
        return isset($this->providers[$code]);
    }

    /**
     * @inheritDoc
     */
    public function getActive(?string $scopeCode = null): ProviderInterface
    {
        $code = $this->config->getActiveProvider($scopeCode);
        if ($this->has($code)) {
            return $this->get($code);
        }

        // Fall back to the first registered provider rather than fatally failing a page render.
        if ($this->providers === []) {
            throw new ProviderNotFoundException(__('No captcha providers are registered.'));
        }

        return reset($this->providers);
    }

    /**
     * @inheritDoc
     */
    public function getRouteGateProvider(?string $scopeCode = null): ProviderInterface
    {
        $override = $this->config->getRouteProviderOverride($scopeCode);
        if ($override !== '' && $this->has($override)) {
            return $this->get($override);
        }

        return $this->getActive($scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function getFallbackProvider(?string $scopeCode = null): ?ProviderInterface
    {
        if (!$this->config->isRouteFallbackEnabled($scopeCode)) {
            return null;
        }

        $code = $this->config->getRouteFallbackProvider($scopeCode);
        if ($code === '' || !$this->has($code)) {
            return null;
        }

        $fallback = $this->get($code);

        return $fallback->isConfigured($scopeCode) ? $fallback : null;
    }
}
