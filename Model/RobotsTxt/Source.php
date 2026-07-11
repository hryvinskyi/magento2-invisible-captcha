<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\SourceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Source implements SourceInterface
{
    private const ROBOTS_FILE = 'robots.txt';
    private const XML_CUSTOM_INSTRUCTIONS = 'design/search_engine_robots/custom_instructions';

    /** @var array<string, string> Resolved content per website id. */
    private array $contentByWebsite = [];

    /**
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        $websiteId = $this->getCurrentWebsiteId();

        return $this->contentByWebsite[$websiteId] ??= $this->resolveContent();
    }

    /**
     * Resolve the served robots.txt: the physical pub/robots.txt wins because
     * the web server delivers it before Magento's /robots.txt route; without
     * one, Magento renders the "Search Engine Robots" custom instructions.
     * The config is read at website scope — the same scope
     * {@see \Magento\Robots\Model\Robots::getData()} serves at /robots.txt.
     *
     * @return string
     */
    private function resolveContent(): string
    {
        $fileContent = $this->readPhysicalFile();
        if ($fileContent !== null) {
            return $fileContent;
        }

        return (string)$this->scopeConfig->getValue(self::XML_CUSTOM_INSTRUCTIONS, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Read pub/robots.txt or return null when it is absent or unreadable.
     *
     * @return string|null
     */
    private function readPhysicalFile(): ?string
    {
        try {
            $pubDirectory = $this->filesystem->getDirectoryRead(DirectoryList::PUB);
            if (!$pubDirectory->isFile(self::ROBOTS_FILE)) {
                return null;
            }

            return $pubDirectory->readFile(self::ROBOTS_FILE);
        } catch (FileSystemException | ValidatorException $e) {
            return null;
        }
    }

    /**
     * Current website id used as the memoization key ("default" outside a
     * resolvable website scope, e.g. some CLI contexts).
     *
     * @return string
     */
    private function getCurrentWebsiteId(): string
    {
        try {
            return (string)$this->storeManager->getWebsite()->getId();
        } catch (LocalizedException $e) {
            return 'default';
        }
    }
}
