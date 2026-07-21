<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Geo\Source;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourceInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Geo\Source\Maxmind\ReaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\Phrase;
use MaxMind\Db\Reader;
use Psr\Log\LoggerInterface;

/**
 * Resolves the visitor's country by looking up the client IP in an
 * admin-uploaded MaxMind `.mmdb` database (GeoLite2 / GeoIP2 Country or City —
 * both carry `country.iso_code`).
 *
 * The reader is opened lazily and cached for the request; a corrupt database or
 * any reader failure marks the source "broken" for the remainder of the request
 * (no retry storm) and degrades to null. Being a singleton that holds
 * per-request state, it implements {@see ResetAfterRequestInterface} so a
 * stateful application server can drop the cached reader between requests.
 *
 * This class never throws from {@see self::isConfigured()} or
 * {@see self::resolve()} — a manually edited config value containing a
 * traversal segment would otherwise make the framework's path validator throw.
 */
class MaxmindDatabase implements CountrySourceInterface, ResetAfterRequestInterface
{
    /**
     * Var-relative directory the upload backend stores the `.mmdb` file in.
     * The admin-config task references the same location.
     */
    public const UPLOAD_DIR = 'hryvinskyi_invisible_captcha/geoip';

    /**
     * Lazily opened, request-cached reader; null until the first resolve.
     */
    private ?Reader $reader = null;

    /**
     * Set once the reader fails to open or a lookup throws, so the rest of the
     * request short-circuits to null without touching the reader again.
     */
    private bool $broken = false;

    /**
     * @param ConfigInterface $config
     * @param Filesystem $filesystem
     * @param ReaderFactory $readerFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly Filesystem $filesystem,
        private readonly ReaderFactory $readerFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'maxmind';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('MaxMind database (GeoLite2 / GeoIP2)');
    }

    /**
     * @inheritDoc
     *
     * True only when a filename is stored and the resolved file actually exists.
     * The filesystem probe is wrapped so a framework path-validation exception
     * (e.g. a traversal segment in the stored value) degrades to false rather
     * than escaping onto the request path.
     */
    public function isConfigured(): bool
    {
        $fileName = $this->config->getMaxmindDbFile();
        if ($fileName === '') {
            return false;
        }

        try {
            return $this->filesystem
                ->getDirectoryRead(DirectoryList::VAR_DIR)
                ->isFile($this->relativePath($fileName));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     *
     * Invalid/empty IP, a lookup miss, or a record without `country.iso_code`
     * all return null. Any reader failure logs a single warning, marks the
     * source broken for the request, and returns null.
     */
    public function resolve(string $clientIp): ?string
    {
        if (filter_var($clientIp, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        if ($this->broken) {
            return null;
        }

        try {
            if ($this->reader === null) {
                $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
                $this->reader = $this->readerFactory->create(
                    $varDir->getAbsolutePath($this->relativePath($this->config->getMaxmindDbFile()))
                );
            }

            $record = $this->reader->get($clientIp);
        } catch (\Throwable $e) {
            $this->broken = true;
            $this->logger->warning(
                'Invisible Captcha: MaxMind country lookup failed; source disabled for this request.',
                ['exception' => $e]
            );

            return null;
        }

        if (
            is_array($record)
            && isset($record['country']['iso_code'])
            && is_string($record['country']['iso_code'])
            && $record['country']['iso_code'] !== ''
        ) {
            return strtoupper($record['country']['iso_code']);
        }

        return null;
    }

    /**
     * @inheritDoc
     *
     * Drops the cached reader (closing its file handle best-effort) and clears
     * the broken flag so the next request starts from a clean slate.
     */
    public function _resetState(): void
    {
        if ($this->reader !== null) {
            try {
                $this->reader->close();
            } catch (\Throwable $e) {
                // Best-effort close; nothing actionable if the handle is already gone.
            }
        }

        $this->reader = null;
        $this->broken = false;
    }

    /**
     * Compose the var-relative path for the stored filename, defending against
     * traversal by reducing the value to its basename first.
     *
     * @param string $fileName
     * @return string
     */
    private function relativePath(string $fileName): string
    {
        return self::UPLOAD_DIR . '/' . basename($fileName);
    }
}
