<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Config\Backend;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb\FileCleaner;
use Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb\MarkerValidator;
use Magento\Config\Model\Config\Backend\File;
use Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\MediaStorage\Model\File\UploaderFactory;

/**
 * System-config file-upload backend for the MaxMind `.mmdb` database.
 *
 * On top of the core {@see File} upload flow this:
 *  - restricts the allowed extension to `mmdb`;
 *  - rejects a PHP-level upload failure loudly, so an oversized file does not
 *    silently leave the previous value in place while the admin believes the
 *    upload succeeded;
 *  - rejects a file that is not structurally a MaxMind database before it is
 *    moved into the upload directory;
 *  - stores the database under the node-local `var/` directory (not the
 *    web-accessible `pub/media`) and removes the previously stored file when it
 *    is replaced or deleted, so uploads do not accumulate.
 */
class MaxmindDb extends File
{
    /**
     * Var-relative directory the `.mmdb` database is uploaded into. Kept in sync
     * (by string) with {@see \Hryvinskyi\InvisibleCaptcha\Model\Config\Backend\MaxmindDb\FileCleaner}
     * and {@see \Hryvinskyi\InvisibleCaptcha\Model\Geo\Source\MaxmindDatabase}.
     */
    private const UPLOAD_DIR = 'hryvinskyi_invisible_captcha/geoip';

    /**
     * Filename recorded from the persisted config before the current save, used
     * by {@see afterSave()} to remove the file being replaced or deleted.
     *
     * @var string
     */
    private string $previousFile = '';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param UploaderFactory $uploaderFactory
     * @param RequestDataInterface $requestData
     * @param Filesystem $filesystem
     * @param MarkerValidator $markerValidator
     * @param FileCleaner $fileCleaner
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        UploaderFactory $uploaderFactory,
        RequestDataInterface $requestData,
        Filesystem $filesystem,
        private readonly MarkerValidator $markerValidator,
        private readonly FileCleaner $fileCleaner,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $uploaderFactory,
            $requestData,
            $filesystem,
            $resource,
            $resourceCollection,
            $data
        );

        // Storage lives under var/, not the web-accessible pub/media. The parent
        // binds `_mediaDirectory` to media and reuses it in its (private)
        // save-without-reupload validation; rebinding it to var keeps that check
        // consistent with the var-based _getUploadDir() below.
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Restrict uploads to MaxMind database files.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions(): array
    {
        return ['mmdb'];
    }

    /**
     * Absolute path of the var-relative upload directory, created on demand.
     *
     * Overriding this fully decouples the field from the system.xml `upload_dir`
     * node (removed) and moves storage from pub/media into the node-local var/
     * directory so the database is not web-accessible.
     *
     * @return string
     */
    protected function _getUploadDir(): string
    {
        $write = $this->_filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $write->create(self::UPLOAD_DIR);

        return $write->getAbsolutePath(self::UPLOAD_DIR);
    }

    /**
     * Guard the upload, then defer to the core save flow.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $this->assertUploadDidNotFail();
        $this->assertLooksLikeMaxmindDatabase();

        // getOldValue() reads the currently persisted value for this path/scope
        // (a bare filename) as a string — the file about to be replaced/removed.
        $this->previousFile = basename($this->getOldValue());

        return parent::beforeSave();
    }

    /**
     * Remove the previous file once the new value is persisted.
     *
     * A `null` value means the parent kept the old value untouched (no upload,
     * no delete) — nothing to clean. A delete resolves to `''` and a replacement
     * to a new filename; both differ from the previous name and free the old
     * file. Cleanup is best-effort and never surfaces an error.
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();

        // A non-string value (null) means the parent left the stored value
        // untouched — no upload and no delete — so the existing file must stay.
        // A delete resolves to '' and a replacement to a new filename.
        $newValue = $this->getData('value');
        if (!is_string($newValue)) {
            return $this;
        }

        $newFile = basename($newValue);
        if ($this->previousFile !== '' && $this->previousFile !== $newFile) {
            $this->fileCleaner->delete($this->previousFile);
        }

        return $this;
    }

    /**
     * Reject a PHP-level upload failure (e.g. the file exceeds
     * upload_max_filesize / post_max_size) instead of silently keeping the old
     * value. `UPLOAD_ERR_NO_FILE` is the normal "save without re-upload" case.
     *
     * @return void
     * @throws LocalizedException
     */
    private function assertUploadDidNotFail(): void
    {
        $value = $this->getData('value');
        if (is_array($value)
            && isset($value['error'])
            && !in_array((int)$value['error'], [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)
        ) {
            throw new LocalizedException(
                __(
                    'The database upload failed — the file may exceed your server\'s upload size limits '
                    . '(upload_max_filesize / post_max_size).'
                )
            );
        }
    }

    /**
     * Reject a freshly uploaded file that is not structurally a MaxMind database
     * before the parent moves it into the media directory.
     *
     * @return void
     * @throws LocalizedException
     */
    private function assertLooksLikeMaxmindDatabase(): void
    {
        $file = $this->getFileData();
        if (!empty($file['tmp_name']) && !$this->markerValidator->isValid((string)$file['tmp_name'])) {
            throw new LocalizedException(
                __('The uploaded file is not a valid MaxMind .mmdb database.')
            );
        }
    }
}
