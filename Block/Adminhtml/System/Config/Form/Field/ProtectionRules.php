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
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldValueHintInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorMetadataInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

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
     * @param StoreManagerInterface $storeManager
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly FieldProviderInterface $fieldProvider,
        private readonly OperatorProviderInterface $operatorProvider,
        private readonly Json $serializer,
        private readonly StoreManagerInterface $storeManager,
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
                'valueYes'            => (string)__('Yes'),
                'valueNo'             => (string)__('No'),
                'tagsPlaceholder'     => (string)__('Add value…'),
                'tagsRemove'          => (string)__('Remove value'),
                'valErrNumber'            => (string)__('Enter a number.'),
                'valErrRegexEmpty'        => (string)__('Enter a regular expression.'),
                'valErrRegexInvalid'      => (string)__('This is not a valid regular expression.'),
                'valErrListEmpty'         => (string)__('Enter one or more values, separated by commas.'),
                'valErrListNumber'        => (string)__('Every list item must be a number.'),
                'valErrEmptyNeverMatches' => (string)__('Enter a value — an empty one never matches.'),
                'valErrInvalidValue'      => (string)__('This value looks invalid for the selected field.'),
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
                'testerTitle'          => (string)__('Test Rules'),
                'testerHint'           => (string)__('Simulate a storefront request against the rules above (unsaved changes included).'),
                'testerUrl'            => (string)__('URL or path'),
                'testerUrlPlaceholder' => (string)__('/checkout/cart or https://example.com/lamps?price=10-20'),
                'testerStore'          => (string)__('Store View'),
                'testerMethod'         => (string)__('Method'),
                'testerUserAgent'      => (string)__('User-Agent'),
                'testerClientIp'       => (string)__('Client IP'),
                'testerReferer'        => (string)__('Referer'),
                'testerActionName'     => (string)__('Full Action Name'),
                'testerActionNameHint' => (string)__('auto-detected — override e.g. catalog_product_view'),
                'testerRun'            => (string)__('Run Test'),
                'testerRunning'        => (string)__('Testing…'),
                'verdictChallenge'     => (string)__('CHALLENGE — this request would get the captcha interstitial'),
                'verdictMatchedIdle'   => (string)__('MATCHED — but no challenge would fire'),
                'verdictPass'          => (string)__('PASS — the rules do not match this request'),
                'testerMatched'        => (string)__('matched'),
                'testerNotMatched'     => (string)__('not matched'),
                'testerGroup'          => (string)__('Group'),
                'testerActual'         => (string)__('actual'),
                'testerFieldsTitle'    => (string)__('Resolved field values'),
                'testerError'          => (string)__('The test request failed. Check the logs and try again.'),
                'reasonExcludedIp'     => (string)__('the client IP is on the Excluded IPs list'),
                'reasonExcludedUa'     => (string)__('the user agent is on the Excluded User Agents list'),
                'reasonVerifyEndpoint' => (string)__('the verify endpoint is never gated'),
                'reasonDisabled'       => (string)__('route protection is disabled in this scope'),
                'reasonNotConfigured'  => (string)__('the route-gate provider is not configured'),
            ],
            'tester' => [
                'endpoint' => $this->getUrl('hryvinskyi_invisible_captcha/tester/run'),
                'stores' => $this->buildStoreOptions(),
                'defaultStoreId' => $this->resolveScopeStoreId(),
            ],
        ]);
    }

    /**
     * Store-view options for the tester's scope selector.
     *
     * @return array<int, array{value: int, label: string, baseUrl: string}>
     */
    private function buildStoreOptions(): array
    {
        $options = [];
        foreach ($this->storeManager->getStores() as $store) {
            if (!$store instanceof Store || !$store->isActive()) {
                continue;
            }

            try {
                $websiteName = (string)$store->getWebsite()->getName();
            } catch (LocalizedException $e) {
                $websiteName = '';
            }

            $label = $store->getName() . ' (' . $store->getCode() . ')';
            $options[] = [
                'value' => (int)$store->getId(),
                'label' => $websiteName !== '' ? $websiteName . ' / ' . $label : $label,
                'baseUrl' => $store->getBaseUrl(UrlInterface::URL_TYPE_LINK),
            ];
        }

        return $options;
    }

    /**
     * Store view matching the system-config scope being edited: the store
     * itself, a website's default store, or the default store view.
     *
     * @return int
     */
    private function resolveScopeStoreId(): int
    {
        $storeParam = (string)$this->getRequest()->getParam('store');
        if ($storeParam !== '') {
            try {
                return (int)$this->storeManager->getStore($storeParam)->getId();
            } catch (LocalizedException $e) {
                // fall through to the website / default resolution
            }
        }

        $websiteParam = (string)$this->getRequest()->getParam('website');
        if ($websiteParam !== '') {
            try {
                $website = $this->storeManager->getWebsite($websiteParam);
                if ($website instanceof Website && $website->getDefaultStore() !== null) {
                    return (int)$website->getDefaultStore()->getId();
                }
            } catch (LocalizedException $e) {
                // fall through to the default store view
            }
        }

        $defaultStore = $this->storeManager->getDefaultStoreView();

        return $defaultStore !== null ? (int)$defaultStore->getId() : 0;
    }

    /**
     * Build the field dropdown options, exposing each field's value-type
     * (drives operator filtering) and, when the field provides one, its value
     * hint (drives the placeholder and exact-match validation).
     *
     * @return array<int, array{value: string, label: string, type: string, hint?: array<string, string>}>
     */
    private function buildFieldOptions(): array
    {
        $options = [];
        foreach ($this->fieldProvider->getAll() as $field) {
            $option = [
                'value' => $field->getCode(),
                'label' => (string)$field->getLabel(),
                'type'  => $field->getType(),
            ];
            if ($field instanceof FieldValueHintInterface) {
                $hint = $field->getValueHint();
                if ($hint !== []) {
                    $option['hint'] = $hint;
                }
            }
            $options[] = $option;
        }

        return $options;
    }

    /**
     * Build the operator dropdown options, including which field types each
     * operator supports (so the JS can hide incompatible ones) and the shape
     * of value the operator consumes (so the JS can validate it).
     *
     * @return array<int, array{value: string, label: string, supports: array<int, string>, valueKind: string}>
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
                'value'     => $operator->getCode(),
                'label'     => (string)$operator->getLabel(),
                'supports'  => $supports,
                'valueKind' => $operator instanceof OperatorMetadataInterface
                    ? $operator->getValueKind()
                    : OperatorMetadataInterface::VALUE_TEXT,
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
