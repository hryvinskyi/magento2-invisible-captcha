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
 * Renders the MaxMind database upload field.
 *
 * The core `type="file"` widget shows only the file input — no indication of
 * what is already stored and no way to remove it. This appends the current
 * filename plus a delete checkbox. The checkbox posts as `<name>[delete]=1`;
 * because a config file element's name already ends in `[value]`, the field is
 * received as `…[value][delete]=1`, which is exactly the shape the parent
 * {@see \Magento\Config\Model\Config\Backend\File::beforeSave()} delete branch
 * consumes to clear the stored value.
 */
class MaxmindDbFile extends Field
{
    /**
     * Append the current-file line and delete checkbox to the core file input.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $html = parent::_getElementHtml($element);

        $value = $element->getValue();
        $name = $element->getName();
        if (!is_string($value) || $value === '' || !is_string($name)) {
            return $html;
        }

        $htmlId = (string)$element->getHtmlId();

        $html .= '<p class="note"><span>'
            . $this->_escaper->escapeHtml(__('Current file:'))
            . ' <strong>' . $this->_escaper->escapeHtml($value) . '</strong></span></p>';
        $html .= '<input type="checkbox" id="' . $this->_escaper->escapeHtmlAttr($htmlId) . '_delete"'
            . ' name="' . $this->_escaper->escapeHtmlAttr($name) . '[delete]" value="1" />';
        $html .= '<label for="' . $this->_escaper->escapeHtmlAttr($htmlId) . '_delete">'
            . $this->_escaper->escapeHtml(__('Delete current file')) . '</label>';

        return $html;
    }
}
