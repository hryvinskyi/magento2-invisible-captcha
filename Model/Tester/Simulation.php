<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Tester;

use Hryvinskyi\InvisibleCaptcha\Api\Tester\SimulationInterface;

class Simulation implements SimulationInterface
{
    /**
     * @param string $url
     * @param string $method
     * @param string $userAgent
     * @param string $clientIp
     * @param string $referer
     * @param string|null $actionName
     * @param int|null $storeId
     * @param array<int, array<string, string>>|null $draftRules
     */
    public function __construct(
        private readonly string $url,
        private readonly string $method = 'GET',
        private readonly string $userAgent = '',
        private readonly string $clientIp = '',
        private readonly string $referer = '',
        private readonly ?string $actionName = null,
        private readonly ?int $storeId = null,
        private readonly ?array $draftRules = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @inheritDoc
     */
    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    /**
     * @inheritDoc
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * @inheritDoc
     */
    public function getActionName(): ?string
    {
        return $this->actionName;
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): ?int
    {
        return $this->storeId;
    }

    /**
     * @inheritDoc
     */
    public function getDraftRules(): ?array
    {
        return $this->draftRules;
    }
}
