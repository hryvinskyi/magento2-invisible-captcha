<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block\Adminhtml\System\Config\Form\Field;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Cloudflare-style filter expression editor.
 *
 * Each AND-chain renders as a card; OR places a labelled divider between
 * cards. Underlying storage is still the same flat list of triplets
 * (combinator/field/operator/value) consumed by the existing parser, so the
 * UI is purely a visual reshaping of that list — adding a card writes a row
 * with `combinator=or`, adding a condition inside a card writes `and`.
 */
class ProtectionRules extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Hryvinskyi_InvisibleCaptcha::system/config/protection-rules.phtml';

    /**
     * @param Context $context
     * @param FieldProviderInterface $fieldProvider
     * @param OperatorProviderInterface $operatorProvider
     * @param Json $serializer
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly FieldProviderInterface $fieldProvider,
        private readonly OperatorProviderInterface $operatorProvider,
        private readonly Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Render the element using the custom template — Magento's `Field` base
     * delegates HTML for the element to {@see _getElementHtml()}, which we
     * override to invoke the template.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->setNamePrefix($element->getName());
        $this->setHtmlId($element->getHtmlId());
        $this->setInitialValue($element->getValue());

        return $this->_toHtml();
    }

    /**
     * Editor configuration consumed by the front-end JS module.
     *
     * @return string JSON
     */
    public function getEditorConfigJson(): string
    {
        return $this->serializer->serialize([
            'inputName' => (string)$this->getNamePrefix(),
            'fields' => $this->buildFieldOptions(),
            'operators' => $this->buildOperatorOptions(),
            'initial' => $this->normalizeInitialValue($this->getInitialValue()),
            'defaults' => $this->buildDefaults(),
            'labels' => [
                'groupHeader'     => (string)__('Match ALL of'),
                'andLabel'        => (string)__('AND'),
                'orLabel'         => (string)__('OR'),
                'addAnd'          => (string)__('AND condition'),
                'addOrGroup'      => (string)__('OR group'),
                'clearAll'        => (string)__('Clear all'),
                'removeCondition' => (string)__('Remove condition'),
                'removeGroup'     => (string)__('Remove group'),
                'fieldPlaceholder'    => (string)__('Field'),
                'operatorPlaceholder' => (string)__('Operator'),
                'valuePlaceholder'    => (string)__('Value'),
                'previewHeader'   => (string)__('Expression Preview'),
                'previewEmpty'    => (string)__('(no conditions — challenge will not fire)'),
                'modeBuilder'     => (string)__('Builder'),
                'modeExpression'  => (string)__('Expression'),
                'statGroups'      => (string)__('OR groups'),
                'statConditions'  => (string)__('conditions'),
                'rawTitle'        => (string)__('Raw Expression'),
                'rawHint'         => (string)__('Edit freely. Press Tab or Enter to accept a suggestion.'),
                'rawStatusValid'  => (string)__('Valid'),
                'rawStatusEmpty'  => (string)__('Empty expression — add at least one condition.'),
                'rawStatusError'  => (string)__('Parse error'),
                'suggestEmpty'    => (string)__('No matching suggestions'),
            ],
        ]);
    }

    /**
     * Build the field dropdown options, exposing each field's value-type so
     * the JS can filter operators when the field changes.
     *
     * @return array<int, array{value: string, label: string, type: string}>
     */
    private function buildFieldOptions(): array
    {
        $options = [];
        foreach ($this->fieldProvider->getAll() as $field) {
            $options[] = [
                'value' => $field->getCode(),
                'label' => (string)$field->getLabel(),
                'type'  => $field->getType(),
            ];
        }

        return $options;
    }

    /**
     * Build the operator dropdown options, including which field types each
     * operator supports so the JS can hide incompatible ones.
     *
     * @return array<int, array{value: string, label: string, supports: array<int, string>}>
     */
    private function buildOperatorOptions(): array
    {
        $fieldTypes = [];
        foreach ($this->fieldProvider->getAll() as $field) {
            $fieldTypes[$field->getType()] = true;
        }

        $options = [];
        foreach ($this->operatorProvider->getAll() as $operator) {
            $supports = [];
            foreach (array_keys($fieldTypes) as $type) {
                if ($operator->supports($type)) {
                    $supports[] = $type;
                }
            }

            $options[] = [
                'value'    => $operator->getCode(),
                'label'    => (string)$operator->getLabel(),
                'supports' => $supports,
            ];
        }

        return $options;
    }

    /**
     * Default field/operator codes used when adding a brand-new row so the
     * dropdowns never start empty.
     *
     * @return array{field: string, operator: string}
     */
    private function buildDefaults(): array
    {
        $fields = $this->fieldProvider->getAll();
        $operators = $this->operatorProvider->getAll();

        $firstField = $fields !== [] ? reset($fields)->getCode() : '';
        $firstOperator = $operators !== [] ? reset($operators)->getCode() : '';

        return [
            'field' => $firstField,
            'operator' => $firstOperator,
        ];
    }

    /**
     * Sanitize the persisted value into a strict list of triplets the editor
     * understands.
     *
     * @param mixed $value
     * @return array<int, array{combinator: string, field: string, operator: string, value: string}>
     */
    private function normalizeInitialValue(mixed $value): array
    {
        if (is_string($value) && trim($value) !== '') {
            try {
                $value = $this->serializer->unserialize($value);
            } catch (\InvalidArgumentException $e) {
                $value = [];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $rows = [];
        foreach ($value as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rows[] = [
                'combinator' => strtolower((string)($row['combinator'] ?? ConditionInterface::COMBINATOR_AND)) === ConditionInterface::COMBINATOR_OR
                    ? ConditionInterface::COMBINATOR_OR
                    : ConditionInterface::COMBINATOR_AND,
                'field'      => (string)($row['field'] ?? ''),
                'operator'   => (string)($row['operator'] ?? ''),
                'value'      => (string)($row['value'] ?? ''),
            ];
        }

        return $rows;
    }
}
