<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Renders the MaxMind database upload field with a custom template.
 *
 * Unlike the default `type="file"` widget — whose element already appends a
 * "Delete File" checkbox and a hidden `[value]` input — this block replaces the
 * core element output entirely (it never calls {@see AbstractElement::getElementHtml()}),
 * so the delete UI is rendered exactly once. The template posts the same field
 * shape the parent {@see \Magento\Config\Model\Config\Backend\File::beforeSave()}
 * consumes: the file input under `<name>`, a delete checkbox `<name>[delete]=1`,
 * and a hidden `<name>[value]` carrying the stored filename so a save without a
 * re-upload preserves the current value. It also surfaces whether the optional
 * PECL `maxminddb` extension is loaded so the admin knows which reader is active.
 */
class MaxmindDbFile extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Hryvinskyi_InvisibleCaptcha::system/config/maxmind-db-file.phtml';

    /**
     * Render the field via the template instead of the core file element.
     *
     * Copies the element's name, html id, current value and disabled state onto
     * the block, then returns the template output.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $value = $element->getValue();

        $this->setData('input_name', (string)$element->getName());
        $this->setData('input_id', (string)$element->getHtmlId());
        $this->setData('current_file', is_string($value) ? $value : '');
        $this->setData('is_disabled', (bool)$element->getData('disabled'));

        return $this->_toHtml();
    }

    /**
     * Form field name posted for this element (already ends in `[value]` for a
     * config field); the delete/hidden inputs nest under it.
     *
     * @return string
     */
    public function getInputName(): string
    {
        return (string)$this->getData('input_name');
    }

    /**
     * DOM id of the file input.
     *
     * @return string
     */
    public function getInputId(): string
    {
        return (string)$this->getData('input_id');
    }

    /**
     * Currently stored filename, or '' when nothing is uploaded yet.
     *
     * @return string
     */
    public function getCurrentFile(): string
    {
        return (string)$this->getData('current_file');
    }

    /**
     * Whether the field is disabled (scope-inherited value).
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return (bool)$this->getData('is_disabled');
    }

    /**
     * Whether the optional PECL `maxminddb` C extension is loaded — when present
     * the reader uses the fast mmap path, otherwise the pure-PHP fallback.
     *
     * @return bool
     */
    public function isMaxmindExtensionLoaded(): bool
    {
        return extension_loaded('maxminddb');
    }
}
