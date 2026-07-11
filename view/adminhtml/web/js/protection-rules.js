/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
define([], function () {
    'use strict';

    var COMBINATOR_AND = 'and';
    var COMBINATOR_OR = 'or';

    var MODE_BUILDER = 'builder';
    var MODE_EXPRESSION = 'code';

    var SVG_PLUS = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M6 2v8M2 6h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    var SVG_CLOSE = '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    var SVG_CHEVRON = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M4 2.5L8 6l-4 3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    function toGroups(rows) {
        var groups = [];
        var current = null;
        rows.forEach(function (row) {
            if (current === null || row.combinator === COMBINATOR_OR) {
                current = [];
                groups.push(current);
            }
            current.push({
                field: row.field || '',
                operator: row.operator || '',
                value: row.value || ''
            });
        });
        return groups;
    }

    function createElement(tag, options) {
        var el = document.createElement(tag);
        options = options || {};
        if (options.className) {
            el.className = options.className;
        }
        if (options.text != null) {
            el.textContent = options.text;
        }
        if (options.html != null) {
            el.innerHTML = options.html;
        }
        if (options.attrs) {
            Object.keys(options.attrs).forEach(function (key) {
                el.setAttribute(key, options.attrs[key]);
            });
        }
        return el;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'})[c];
        });
    }

    /**
     * Split text by a top-level separator (paren depth 0, outside strings).
     * Used to break an expression by " OR " into groups and by " AND " into
     * conditions inside a group.
     */
    function splitAtDepth(text, separatorRegex) {
        var parts = [];
        var current = '';
        var depth = 0;
        var inString = false;
        var i = 0;
        while (i < text.length) {
            var ch = text[i];
            if (inString) {
                current += ch;
                if (ch === '\\' && i + 1 < text.length) {
                    current += text[i + 1];
                    i += 2;
                    continue;
                }
                if (ch === '"') {
                    inString = false;
                }
                i++;
                continue;
            }
            if (ch === '"') {
                inString = true;
                current += ch;
                i++;
                continue;
            }
            if (ch === '(') { depth++; current += ch; i++; continue; }
            if (ch === ')') { depth--; current += ch; i++; continue; }
            if (depth === 0) {
                var m = text.slice(i).match(separatorRegex);
                if (m && m.index === 0) {
                    parts.push(current);
                    current = '';
                    i += m[0].length;
                    continue;
                }
            }
            current += ch;
            i++;
        }
        parts.push(current);
        return parts;
    }

    /** If text is wrapped in matching outer parens, return its inner content. */
    function stripOuterParens(text) {
        text = text.trim();
        if (text.length < 2 || text.charAt(0) !== '(' || text.charAt(text.length - 1) !== ')') {
            return text;
        }
        var depth = 0;
        var inString = false;
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            if (inString) {
                if (ch === '\\' && i + 1 < text.length) {
                    i++;
                    continue;
                }
                if (ch === '"') {
                    inString = false;
                }
                continue;
            }
            if (ch === '"') {
                inString = true;
                continue;
            }
            if (ch === '(') {
                depth++;
            } else if (ch === ')') {
                depth--;
                if (depth === 0 && i < text.length - 1) {
                    return text;
                }
            }
        }
        return text.slice(1, -1).trim();
    }

    function ProtectionRulesEditor(root) {
        this.root = root;
        var raw = root.getAttribute('data-captcha-config');
        this.config = JSON.parse(raw);
        this.labels = this.config.labels || {};
        this.fields = this.config.fields || [];
        this.operators = this.config.operators || [];
        this.fieldTypeByCode = {};
        this.fieldsByCode = {};
        this.fieldsByLabel = {};
        this.fieldHintByCode = {};
        this.fields.forEach(function (field) {
            this.fieldTypeByCode[field.value] = field.type;
            this.fieldsByCode[field.value] = field;
            this.fieldsByLabel[field.label] = field;
            if (field.hint) {
                var hintRegex = null;
                if (field.hint.pattern) {
                    try {
                        hintRegex = new RegExp(field.hint.pattern);
                    } catch (e) {
                        hintRegex = null;
                    }
                }
                this.fieldHintByCode[field.value] = {
                    regex: hintRegex,
                    message: field.hint.message || '',
                    placeholder: field.hint.placeholder || ''
                };
            }
        }.bind(this));
        this.operatorsByCode = {};
        this.operatorsByLabel = {};
        this.operators.forEach(function (op) {
            this.operatorsByCode[op.value] = op;
            this.operatorsByLabel[op.label] = op;
        }.bind(this));

        // Sort by code length desc so longest matches win when prefix-matching
        // (e.g., `not_contains` must be tried before `contains`).
        this.fieldsSorted = this.fields.slice().sort(function (a, b) {
            return b.value.length - a.value.length;
        });
        this.operatorsSorted = this.operators.slice().sort(function (a, b) {
            return b.value.length - a.value.length;
        });

        this.groups = toGroups(this.config.initial || []);
        if (this.groups.length === 0) {
            this.groups = [[this.makeDefaultCondition()]];
        }

        this.mode = MODE_BUILDER;
        this.rowCounter = 0;
        this.suggestItems = [];
        this.suggestIndex = 0;
        this.suggestContext = null;

        this.mount();
        this.refresh();
    }

    ProtectionRulesEditor.prototype.makeDefaultCondition = function () {
        var defaults = this.config.defaults || {};
        var fieldCode = defaults.field || (this.fields[0] && this.fields[0].value) || '';
        var operatorCode = this.firstOperatorFor(fieldCode) || defaults.operator || (this.operators[0] && this.operators[0].value) || '';
        return {field: fieldCode, operator: operatorCode, value: ''};
    };

    ProtectionRulesEditor.prototype.operatorsFor = function (fieldCode) {
        var type = this.fieldTypeByCode[fieldCode];
        if (!type) {
            return this.operators.slice();
        }
        return this.operators.filter(function (op) {
            return (op.supports || []).indexOf(type) !== -1;
        });
    };

    ProtectionRulesEditor.prototype.firstOperatorFor = function (fieldCode) {
        var allowed = this.operatorsFor(fieldCode);
        return allowed.length ? allowed[0].value : '';
    };

    ProtectionRulesEditor.prototype.nextRowKey = function () {
        this.rowCounter += 1;
        return '_' + this.rowCounter;
    };

    ProtectionRulesEditor.prototype.inputName = function (rowKey, field) {
        return this.config.inputName + '[' + rowKey + '][' + field + ']';
    };

    // ── Mount: one-time DOM construction ─────────────────────────────────
    ProtectionRulesEditor.prototype.mount = function () {
        this.root.innerHTML = '';
        this.root.classList.add('hryvinskyi-fx');

        this.modesEl = this.buildModeTabs();
        this.root.appendChild(this.modesEl);

        this.builderEl = createElement('div', {className: 'hryvinskyi-fx-builder'});
        this.root.appendChild(this.builderEl);

        this.actionsEl = this.buildGroupActions();
        this.root.appendChild(this.actionsEl);

        this.editorEl = this.buildEditorPanel();
        this.root.appendChild(this.editorEl);

        this.previewEl = this.buildPreview();
        this.root.appendChild(this.previewEl);

        if (this.config.tester && this.config.tester.endpoint) {
            this.testerEl = this.buildTester();
            this.root.appendChild(this.testerEl);
        }
    };

    ProtectionRulesEditor.prototype.buildModeTabs = function () {
        var self = this;
        var wrap = createElement('div', {
            className: 'hryvinskyi-fx-modes',
            attrs: {role: 'tablist'}
        });

        [
            [MODE_BUILDER, this.labels.modeBuilder || 'Builder'],
            [MODE_EXPRESSION, this.labels.modeExpression || 'Expression']
        ].forEach(function (entry) {
            var mode = entry[0];
            var btn = createElement('button', {
                attrs: {type: 'button', 'data-mode': mode, 'aria-pressed': 'false'},
                html: '<span class="hryvinskyi-fx-mode-dot"></span>' + escapeHtml(entry[1])
            });
            btn.addEventListener('click', function () {
                self.switchMode(mode);
            });
            wrap.appendChild(btn);
        });
        return wrap;
    };

    ProtectionRulesEditor.prototype.buildGroupActions = function () {
        var self = this;
        var wrap = createElement('div', {className: 'hryvinskyi-fx-actions'});

        var addGroup = createElement('button', {
            className: 'hryvinskyi-fx-btn hryvinskyi-fx-btn-primary',
            attrs: {type: 'button'},
            html: SVG_PLUS + '<span>' + escapeHtml(this.labels.addOrGroup || 'OR group') + '</span>'
        });
        addGroup.addEventListener('click', function () { self.addGroup(); });
        wrap.appendChild(addGroup);

        var clearAll = createElement('button', {
            className: 'hryvinskyi-fx-btn hryvinskyi-fx-btn-ghost',
            attrs: {type: 'button'},
            text: this.labels.clearAll || 'Clear all'
        });
        clearAll.addEventListener('click', function () { self.clearAll(); });
        wrap.appendChild(clearAll);
        return wrap;
    };

    ProtectionRulesEditor.prototype.buildEditorPanel = function () {
        var self = this;
        var wrap = createElement('div', {className: 'hryvinskyi-fx-editor'});
        var inner = createElement('div', {className: 'hryvinskyi-fx-editor-wrap'});

        var head = createElement('div', {className: 'hryvinskyi-fx-editor-head'});
        head.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-editor-title',
            text: this.labels.rawTitle || 'Raw Expression'
        }));
        head.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-editor-hint',
            text: this.labels.rawHint || ''
        }));
        inner.appendChild(head);

        var textareaWrap = createElement('div', {className: 'hryvinskyi-fx-textarea-wrap'});
        this.rawTextarea = createElement('textarea', {
            className: 'hryvinskyi-fx-textarea',
            attrs: {spellcheck: 'false', autocomplete: 'off', autocorrect: 'off', autocapitalize: 'off'}
        });
        textareaWrap.appendChild(this.rawTextarea);

        this.suggestEl = createElement('div', {className: 'hryvinskyi-fx-suggest', attrs: {hidden: 'hidden'}});
        textareaWrap.appendChild(this.suggestEl);
        inner.appendChild(textareaWrap);

        this.editorStatus = createElement('div', {className: 'hryvinskyi-fx-editor-status'});
        inner.appendChild(this.editorStatus);

        this.rawTextarea.addEventListener('input', function () { self.handleEditorInput(); });
        this.rawTextarea.addEventListener('keydown', function (e) { self.handleEditorKeydown(e); });
        this.rawTextarea.addEventListener('click', function () { self.refreshSuggestions(); });
        this.rawTextarea.addEventListener('keyup', function (e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Home' || e.key === 'End') {
                self.refreshSuggestions();
            }
        });
        this.rawTextarea.addEventListener('focus', function () { self.refreshSuggestions(); });
        this.rawTextarea.addEventListener('blur', function () {
            // Delay so a click on a suggestion item can still register.
            setTimeout(function () {
                if (document.activeElement !== self.rawTextarea) {
                    self.hideSuggestions();
                }
            }, 150);
        });

        wrap.appendChild(inner);
        return wrap;
    };

    ProtectionRulesEditor.prototype.buildPreview = function () {
        var wrap = createElement('div', {className: 'hryvinskyi-fx-preview'});

        var head = createElement('div', {className: 'hryvinskyi-fx-preview-head'});
        var title = createElement('div', {className: 'hryvinskyi-fx-preview-title'});
        title.appendChild(createElement('span', {className: 'hryvinskyi-fx-preview-live'}));
        title.appendChild(createElement('span', {
            text: this.labels.previewHeader || 'Expression Preview'
        }));
        head.appendChild(title);

        var meta = createElement('div', {className: 'hryvinskyi-fx-preview-meta'});
        this.statGroupsEl = createElement('b', {text: '0'});
        this.statRowsEl = createElement('b', {text: '0'});

        var statG = createElement('span');
        statG.appendChild(this.statGroupsEl);
        statG.appendChild(document.createTextNode(' ' + (this.labels.statGroups || 'OR groups')));
        var statR = createElement('span');
        statR.appendChild(this.statRowsEl);
        statR.appendChild(document.createTextNode(' ' + (this.labels.statConditions || 'conditions')));

        meta.appendChild(statG);
        meta.appendChild(statR);
        head.appendChild(meta);
        wrap.appendChild(head);

        this.previewBody = createElement('div', {className: 'hryvinskyi-fx-preview-body'});
        wrap.appendChild(this.previewBody);
        return wrap;
    };

    // ── Refresh: idempotent state → DOM sync ─────────────────────────────
    ProtectionRulesEditor.prototype.refresh = function () {
        this.refreshModeClasses();
        this.refreshBuilder();
        this.refreshPreview();
    };

    ProtectionRulesEditor.prototype.refreshModeClasses = function () {
        this.root.classList.remove('hryvinskyi-fx-mode-code');
        if (this.mode === MODE_EXPRESSION) {
            this.root.classList.add('hryvinskyi-fx-mode-code');
        }
        var self = this;
        this.modesEl.querySelectorAll('button').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.getAttribute('data-mode') === self.mode ? 'true' : 'false');
        });
    };

    ProtectionRulesEditor.prototype.refreshBuilder = function () {
        this.builderEl.innerHTML = '';
        this.rowCounter = 0;
        var self = this;
        this.groups.forEach(function (group, groupIndex) {
            self.builderEl.appendChild(self.renderGroup(group, groupIndex));
            if (groupIndex < self.groups.length - 1) {
                self.builderEl.appendChild(self.renderOrDivider());
            }
        });
    };

    ProtectionRulesEditor.prototype.renderOrDivider = function () {
        var divider = createElement('div', {className: 'hryvinskyi-fx-or'});
        divider.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-or-badge',
            text: this.labels.orLabel || 'OR'
        }));
        return divider;
    };

    ProtectionRulesEditor.prototype.renderGroup = function (group, groupIndex) {
        var self = this;
        var wrapper = createElement('div', {className: 'hryvinskyi-fx-group'});

        var head = createElement('div', {className: 'hryvinskyi-fx-group-head'});
        var title = createElement('div', {className: 'hryvinskyi-fx-group-title'});
        title.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-group-index',
            text: ('0' + (groupIndex + 1)).slice(-2)
        }));
        head.appendChild(title);

        if (this.groups.length > 1) {
            var removeGroupBtn = createElement('button', {
                className: 'hryvinskyi-fx-group-remove',
                text: this.labels.removeGroup || 'Remove group',
                attrs: {type: 'button'}
            });
            removeGroupBtn.addEventListener('click', function () { self.removeGroup(groupIndex); });
            head.appendChild(removeGroupBtn);
        }
        wrapper.appendChild(head);

        group.forEach(function (condition, condIndex) {
            wrapper.appendChild(self.renderCondition(condition, groupIndex, condIndex));
        });

        var addRow = createElement('button', {
            className: 'hryvinskyi-fx-add-row',
            attrs: {type: 'button'},
            html: SVG_PLUS + '<span>' + escapeHtml(this.labels.addAnd || 'AND condition') + '</span>'
        });
        addRow.addEventListener('click', function () { self.addCondition(groupIndex); });
        wrapper.appendChild(addRow);

        return wrapper;
    };

    ProtectionRulesEditor.prototype.renderCondition = function (condition, groupIndex, condIndex) {
        var self = this;
        var rowKey = this.nextRowKey();
        var row = createElement('div', {className: 'hryvinskyi-fx-row'});
        var combinator = (groupIndex > 0 && condIndex === 0) ? COMBINATOR_OR : COMBINATOR_AND;
        var isFirst = condIndex === 0;

        var conn = createElement('div', {className: 'hryvinskyi-fx-row-conn'});
        if (!isFirst) {
            conn.appendChild(createElement('div', {className: 'hryvinskyi-fx-row-pip'}));
            conn.appendChild(createElement('div', {
                className: 'hryvinskyi-fx-row-and',
                text: this.labels.andLabel || 'AND'
            }));
            conn.appendChild(createElement('div', {className: 'hryvinskyi-fx-row-pip'}));
        }
        row.appendChild(conn);

        var fieldSelect = this.buildFieldSelect(condition, rowKey);
        var operatorSelect = this.buildOperatorSelect(condition, rowKey);
        var currentValueControl = this.buildValueControl(condition, rowKey);
        var validationErrorEl = null;

        // Live value validation: red state + message under the Value cell.
        var applyValidation = function () {
            var verdict = self.validateConditionValue(condition);
            currentValueControl.classList.toggle('is-invalid', !verdict.ok);
            if (!verdict.ok) {
                if (!validationErrorEl) {
                    validationErrorEl = createElement('span', {className: 'hryvinskyi-fx-cell-error'});
                    valCell.appendChild(validationErrorEl);
                }
                validationErrorEl.textContent = verdict.message;
            } else if (validationErrorEl) {
                valCell.removeChild(validationErrorEl);
                validationErrorEl = null;
            }
        };
        var bindValueValidation = function (control) {
            control.addEventListener('input', applyValidation);
            control.addEventListener('change', applyValidation);
        };
        bindValueValidation(currentValueControl);

        var combinatorInput = createElement('input', {
            attrs: {type: 'hidden', name: this.inputName(rowKey, 'combinator'), value: combinator}
        });

        var removeBtn = createElement('button', {
            className: 'hryvinskyi-fx-row-remove',
            attrs: {type: 'button', title: this.labels.removeCondition || 'Remove condition'},
            html: SVG_CLOSE
        });
        removeBtn.addEventListener('click', function () { self.removeCondition(groupIndex, condIndex); });

        var fieldCell = createElement('div', {className: 'hryvinskyi-fx-cell hryvinskyi-fx-cell-field'});
        fieldCell.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-cell-cap',
            text: this.labels.fieldPlaceholder || 'Field'
        }));
        fieldCell.appendChild(fieldSelect);

        var opCell = createElement('div', {className: 'hryvinskyi-fx-cell hryvinskyi-fx-cell-operator'});
        opCell.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-cell-cap',
            text: this.labels.operatorPlaceholder || 'Operator'
        }));
        opCell.appendChild(operatorSelect);

        var valCell = createElement('div', {className: 'hryvinskyi-fx-cell hryvinskyi-fx-cell-value'});
        valCell.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-cell-cap',
            text: this.labels.valuePlaceholder || 'Value'
        }));
        valCell.appendChild(currentValueControl);

        row.appendChild(fieldCell);
        row.appendChild(opCell);
        row.appendChild(valCell);
        row.appendChild(removeBtn);
        row.appendChild(combinatorInput);

        // The value control's shape depends on the field type and operator
        // (boolean fields use a Yes/No select, list operators a tag input) —
        // rebuild it in place whenever either changes.
        var swapValueControl = function () {
            var freshValueControl = self.buildValueControl(condition, rowKey);
            valCell.replaceChild(freshValueControl, currentValueControl);
            currentValueControl = freshValueControl;
            bindValueValidation(freshValueControl);
        };

        fieldSelect.addEventListener('change', function () {
            condition.field = fieldSelect.value;
            self.repopulateOperator(operatorSelect, condition);
            swapValueControl();
            applyValidation();
            self.refreshPreview();
        });
        operatorSelect.addEventListener('change', function () {
            condition.operator = operatorSelect.value;
            swapValueControl();
            applyValidation();
            self.refreshPreview();
        });

        applyValidation();

        return row;
    };

    /**
     * Validate a condition's value against the operator's value kind and the
     * field's type/hint. Advisory only — the server evaluates fail-safe — but
     * it catches values that would silently never match.
     */
    ProtectionRulesEditor.prototype.validateConditionValue = function (condition) {
        var valid = {ok: true, message: ''};
        var type = this.fieldTypeByCode[condition.field];
        if (type === 'boolean') {
            return valid;
        }

        var operator = this.operatorsByCode[condition.operator];
        var kind = (operator && operator.valueKind) || 'text';
        var value = condition.value == null ? '' : String(condition.value);
        var trimmed = value.trim();
        var hint = this.fieldHintByCode[condition.field] || null;
        var numberRe = /^-?\d+(\.\d+)?$/;

        if (kind === 'number') {
            if (!numberRe.test(trimmed)) {
                return {ok: false, message: this.labels.valErrNumber || 'Enter a number.'};
            }
            return valid;
        }

        if (kind === 'pattern') {
            if (trimmed === '') {
                return {ok: false, message: this.labels.valErrRegexEmpty || 'Enter a regular expression.'};
            }
            if (!this.isValidPattern(value)) {
                return {ok: false, message: this.labels.valErrRegexInvalid || 'This is not a valid regular expression.'};
            }
            return valid;
        }

        if (kind === 'list') {
            var items = trimmed.split(/[\s,]+/).filter(Boolean);
            if (!items.length) {
                return {ok: false, message: this.labels.valErrListEmpty || 'Enter one or more values, separated by commas.'};
            }
            for (var i = 0; i < items.length; i++) {
                var itemError = this.listItemError(condition, items[i]);
                if (itemError !== null) {
                    return {ok: false, message: itemError};
                }
            }
            return valid;
        }

        if (kind === 'text_required' && value === '') {
            return {ok: false, message: this.labels.valErrEmptyNeverMatches || 'Enter a value — an empty one never matches.'};
        }

        if (type === 'numeric') {
            if (!numberRe.test(trimmed)) {
                return {ok: false, message: this.labels.valErrNumber || 'Enter a number.'};
            }
            return valid;
        }

        // Exact-match text against the field's own format hint. Substring and
        // regex operators legitimately take fragments, so only `text` applies.
        if (kind === 'text' && value !== '' && hint && hint.regex && !hint.regex.test(value)) {
            return {
                ok: false,
                message: hint.message || this.labels.valErrInvalidValue || 'This value looks invalid for the selected field.'
            };
        }

        return valid;
    };

    /**
     * Validate one list item against the condition's field: numeric fields
     * require numbers, hinted fields their format pattern. Returns the error
     * message, or null when the item is fine.
     */
    ProtectionRulesEditor.prototype.listItemError = function (condition, item) {
        var type = this.fieldTypeByCode[condition.field];
        if (type === 'numeric') {
            if (!/^-?\d+(\.\d+)?$/.test(item)) {
                return this.labels.valErrListNumber || 'Every list item must be a number.';
            }
            return null;
        }

        var hint = this.fieldHintByCode[condition.field] || null;
        if (hint && hint.regex && !hint.regex.test(item)) {
            return hint.message || this.labels.valErrInvalidValue || 'This value looks invalid for the selected field.';
        }

        return null;
    };

    /**
     * Tag-style value control for list operators: each item is a removable
     * chip; Enter, comma, space, paste and blur commit the typed text. The
     * canonical comma-separated string lives in a hidden input carrying the
     * row's form name, so persistence and the parser see the same format a
     * plain text input would produce.
     */
    ProtectionRulesEditor.prototype.buildTagValueControl = function (condition, rowKey) {
        var self = this;
        var tags = String(condition.value == null ? '' : condition.value).split(/[\s,]+/).filter(Boolean);

        var wrap = createElement('div', {className: 'hryvinskyi-fx-tags'});
        var entry = createElement('input', {
            className: 'hryvinskyi-fx-tags-entry',
            attrs: {
                type: 'text',
                placeholder: this.labels.tagsPlaceholder || 'Add value…'
            }
        });
        var hidden = createElement('input', {
            attrs: {type: 'hidden', name: this.inputName(rowKey, 'value'), value: tags.join(', ')}
        });

        var sync = function () {
            condition.value = tags.join(', ');
            hidden.value = condition.value;
            self.refreshPreview();
            // Bubbles to the row's validation listener bound on the wrapper.
            wrap.dispatchEvent(new Event('change'));
        };

        var renderTags = function () {
            wrap.querySelectorAll('.hryvinskyi-fx-tag').forEach(function (chip) {
                wrap.removeChild(chip);
            });
            tags.forEach(function (tag, index) {
                var chip = createElement('span', {className: 'hryvinskyi-fx-tag'});
                if (self.listItemError(condition, tag) !== null) {
                    chip.classList.add('is-bad');
                }
                chip.appendChild(createElement('span', {className: 'hryvinskyi-fx-tag-text', text: tag}));
                var remove = createElement('button', {
                    className: 'hryvinskyi-fx-tag-remove',
                    attrs: {type: 'button', title: self.labels.tagsRemove || 'Remove value'},
                    html: '&times;'
                });
                remove.addEventListener('click', function () {
                    tags.splice(index, 1);
                    renderTags();
                    sync();
                });
                chip.appendChild(remove);
                wrap.insertBefore(chip, entry);
            });
        };

        var commitEntry = function () {
            var pieces = entry.value.split(/[\s,]+/).filter(Boolean);
            pieces.forEach(function (piece) {
                if (tags.indexOf(piece) === -1) {
                    tags.push(piece);
                }
            });
            entry.value = '';
            if (pieces.length) {
                renderTags();
                sync();
            }
        };

        entry.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
                e.preventDefault();
                commitEntry();
            } else if (e.key === 'Backspace' && entry.value === '' && tags.length) {
                tags.pop();
                renderTags();
                sync();
            }
        });
        entry.addEventListener('blur', commitEntry);
        entry.addEventListener('paste', function () {
            window.setTimeout(commitEntry, 0);
        });
        wrap.addEventListener('click', function (e) {
            if (e.target === wrap) {
                entry.focus();
            }
        });

        wrap.appendChild(entry);
        wrap.appendChild(hidden);
        renderTags();

        return wrap;
    };

    /**
     * Approximate regex validation: accepts a bare pattern the way the server
     * auto-wraps it, or a delimited PCRE like `~^/checkout~i` (the delimited
     * body is compiled without its PCRE-only flags).
     */
    ProtectionRulesEditor.prototype.isValidPattern = function (pattern) {
        try {
            new RegExp(pattern);
            return true;
        } catch (e) {
            // fall through to the delimited-PCRE attempt
        }

        var delimited = pattern.match(/^(.)([\s\S]*)\1([imsuxADSUXJ]*)$/);
        if (delimited && /[^A-Za-z0-9\s\\]/.test(delimited[1])) {
            try {
                new RegExp(delimited[2]);
                return true;
            } catch (e2) {
                return false;
            }
        }

        return false;
    };

    /**
     * Build the Value control for a condition: boolean fields get a strict
     * Yes/No select (persisted as "1"/"0"), list operators a tag input
     * (persisted comma-separated), everything else a free text input.
     */
    ProtectionRulesEditor.prototype.buildValueControl = function (condition, rowKey) {
        var self = this;
        var operator = this.operatorsByCode[condition.operator];

        if ((operator && operator.valueKind) === 'list' && this.fieldTypeByCode[condition.field] !== 'boolean') {
            return this.buildTagValueControl(condition, rowKey);
        }

        if (this.fieldTypeByCode[condition.field] === 'boolean') {
            if (condition.value !== '0' && condition.value !== '1') {
                condition.value = '1';
            }
            var select = createElement('select', {
                className: 'hryvinskyi-fx-select',
                attrs: {name: this.inputName(rowKey, 'value')}
            });
            [
                ['1', this.labels.valueYes || 'Yes'],
                ['0', this.labels.valueNo || 'No']
            ].forEach(function (entry) {
                var option = createElement('option', {text: entry[1], attrs: {value: entry[0]}});
                if (entry[0] === condition.value) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            select.addEventListener('change', function () {
                condition.value = select.value;
                self.refreshPreview();
            });
            return select;
        }

        var hint = this.fieldHintByCode[condition.field] || null;
        var input = createElement('input', {
            className: 'hryvinskyi-fx-input hryvinskyi-fx-mono',
            attrs: {
                type: 'text',
                name: this.inputName(rowKey, 'value'),
                value: condition.value || '',
                placeholder: (hint && hint.placeholder) || this.labels.valuePlaceholder || 'Value'
            }
        });
        input.addEventListener('input', function () {
            condition.value = input.value;
            self.refreshPreview();
        });
        return input;
    };

    ProtectionRulesEditor.prototype.buildFieldSelect = function (condition, rowKey) {
        var select = createElement('select', {
            className: 'hryvinskyi-fx-select',
            attrs: {name: this.inputName(rowKey, 'field')}
        });
        this.fields.forEach(function (field) {
            var option = createElement('option', {text: field.label, attrs: {value: field.value}});
            if (field.value === condition.field) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        if (!condition.field && this.fields.length) {
            condition.field = this.fields[0].value;
        }
        return select;
    };

    ProtectionRulesEditor.prototype.buildOperatorSelect = function (condition, rowKey) {
        var select = createElement('select', {
            className: 'hryvinskyi-fx-select',
            attrs: {name: this.inputName(rowKey, 'operator')}
        });
        this.fillOperatorOptions(select, condition);
        return select;
    };

    ProtectionRulesEditor.prototype.repopulateOperator = function (select, condition) {
        var keep = condition.operator;
        select.innerHTML = '';
        this.fillOperatorOptions(select, condition);
        if (select.value !== keep) {
            condition.operator = select.value;
        }
    };

    ProtectionRulesEditor.prototype.fillOperatorOptions = function (select, condition) {
        var allowed = this.operatorsFor(condition.field);
        var currentSupported = false;
        allowed.forEach(function (op) {
            var option = createElement('option', {text: op.label, attrs: {value: op.value}});
            if (op.value === condition.operator) {
                option.selected = true;
                currentSupported = true;
            }
            select.appendChild(option);
        });
        if (!currentSupported && allowed.length) {
            select.value = allowed[0].value;
            condition.operator = allowed[0].value;
        }
    };

    // ── Preview / textarea sync ──────────────────────────────────────────
    ProtectionRulesEditor.prototype.refreshPreview = function (options) {
        options = options || {};
        if (!this.previewBody) {
            return;
        }

        var rendered = this.renderExpression();

        if (rendered.anyContent) {
            this.previewBody.innerHTML = rendered.html;
        } else {
            this.previewBody.innerHTML = '<span class="hryvinskyi-fx-preview-empty">' +
                escapeHtml(this.labels.previewEmpty || '') + '</span>';
        }

        var textareaFocused = document.activeElement === this.rawTextarea;
        if (this.rawTextarea && !textareaFocused && !options.skipTextarea) {
            this.rawTextarea.value = rendered.anyContent ? rendered.raw : '';
        }

        if (this.statGroupsEl) {
            this.statGroupsEl.textContent = String(this.groups.length);
        }
        if (this.statRowsEl) {
            this.statRowsEl.textContent = String(rendered.conditionCount);
        }

        if (this.editorStatus && !textareaFocused) {
            this.setEditorStatus(rendered.anyContent ? 'ok' : 'empty');
        }
    };

    ProtectionRulesEditor.prototype.renderExpression = function () {
        var rawParts = [];
        var htmlParts = [];
        var rowCount = 0;
        var anyContent = false;

        // Expression uses codes (field.value / op.value) so identifiers stay
        // word-safe — labels like "Request URI (path + query)" would otherwise
        // confuse the parser with embedded parens.
        this.groups.forEach(function (group) {
            var rawSub = [];
            var htmlSub = [];
            group.forEach(function (cond) {
                rowCount += 1;
                if (cond.value !== '') {
                    anyContent = true;
                }
                var fieldCode = cond.field || '?';
                var opCode = cond.operator || '?';
                var valDisplay = cond.value === '' ? '""' : JSON.stringify(cond.value);

                rawSub.push('(' + fieldCode + ' ' + opCode + ' ' + valDisplay + ')');
                htmlSub.push(
                    '<span class="hryvinskyi-fx-tok-paren">(</span>' +
                    '<span class="hryvinskyi-fx-tok-field">' + escapeHtml(fieldCode) + '</span> ' +
                    '<span class="hryvinskyi-fx-tok-op">' + escapeHtml(opCode) + '</span> ' +
                    '<span class="hryvinskyi-fx-tok-val">' + escapeHtml(valDisplay) + '</span>' +
                    '<span class="hryvinskyi-fx-tok-paren">)</span>'
                );
            });
            rawParts.push('(' + rawSub.join(' AND ') + ')');
            htmlParts.push(
                '<span class="hryvinskyi-fx-tok-paren">(</span>' +
                htmlSub.join(' <span class="hryvinskyi-fx-tok-and">AND</span> ') +
                '<span class="hryvinskyi-fx-tok-paren">)</span>'
            );
        });

        return {
            raw: rawParts.join(' OR\n'),
            html: htmlParts.join(' <span class="hryvinskyi-fx-tok-or">OR</span>\n'),
            conditionCount: rowCount,
            anyContent: anyContent
        };
    };

    // ── Editor (raw textarea) handlers ───────────────────────────────────
    ProtectionRulesEditor.prototype.switchMode = function (newMode) {
        if (this.mode === MODE_EXPRESSION && newMode !== MODE_EXPRESSION) {
            // Commit any pending edits from the textarea before leaving.
            this.parseEditorText({silent: true});
        }
        this.mode = newMode;
        if (this.mode === MODE_EXPRESSION) {
            var rendered = this.renderExpression();
            this.rawTextarea.value = rendered.anyContent ? rendered.raw : '';
            this.setEditorStatus(rendered.anyContent ? 'ok' : 'empty');
        } else {
            this.hideSuggestions();
        }
        this.refresh();
    };

    ProtectionRulesEditor.prototype.handleEditorInput = function () {
        var result = this.parseEditorText({silent: false});
        // refreshSuggestions also runs on input via this call.
        this.refreshSuggestions();
        if (result.ok) {
            // Stats / preview need to reflect newly-parsed groups, but don't
            // overwrite the textarea while the user is typing.
            this.refreshPreview({skipTextarea: true});
        }
    };

    ProtectionRulesEditor.prototype.parseEditorText = function (options) {
        options = options || {};
        var text = (this.rawTextarea.value || '').trim();
        if (text === '') {
            this.setEditorStatus('empty');
            return {ok: true, empty: true};
        }
        try {
            var groups = this.parseExpression(text);
            if (groups.length === 0 || groups.every(function (g) { return g.length === 0; })) {
                this.setEditorStatus('empty');
                return {ok: true, empty: true};
            }
            this.groups = groups;
            this.refreshBuilder();
            this.setEditorStatus('ok');
            return {ok: true, groups: groups};
        } catch (e) {
            this.setEditorStatus('error', e.message);
            return {ok: false, error: e.message};
        }
    };

    ProtectionRulesEditor.prototype.parseExpression = function (text) {
        var self = this;
        var groupTexts = splitAtDepth(text, /^\s+OR\s+/i);
        var groups = [];
        groupTexts.forEach(function (groupText) {
            var inner = stripOuterParens(groupText.trim());
            if (inner === '') {
                return;
            }
            var condTexts = splitAtDepth(inner, /^\s+AND\s+/i);
            var conds = [];
            condTexts.forEach(function (condText) {
                var condInner = stripOuterParens(condText.trim());
                if (condInner === '') {
                    return;
                }
                conds.push(self.parseCondition(condInner));
            });
            if (conds.length) {
                groups.push(conds);
            }
        });
        return groups;
    };

    ProtectionRulesEditor.prototype.parseCondition = function (text) {
        text = text.trim();
        var fieldEntry = null;
        for (var i = 0; i < this.fieldsSorted.length; i++) {
            var f = this.fieldsSorted[i];
            if (text === f.value || text.indexOf(f.value + ' ') === 0) {
                fieldEntry = f;
                break;
            }
        }
        if (!fieldEntry) {
            throw new Error('Unknown field near "' + text.substring(0, 40) + '"');
        }
        var rest = text.slice(fieldEntry.value.length).trim();

        var opEntry = null;
        for (var j = 0; j < this.operatorsSorted.length; j++) {
            var op = this.operatorsSorted[j];
            if (rest === op.value
                || rest.indexOf(op.value + ' ') === 0
                || rest.indexOf(op.value + '"') === 0) {
                opEntry = op;
                break;
            }
        }
        if (!opEntry) {
            throw new Error('Unknown operator near "' + rest.substring(0, 40) + '"');
        }
        var valRaw = rest.slice(opEntry.value.length).trim();
        var value = '';
        if (valRaw === '' || valRaw === '""') {
            value = '';
        } else if (valRaw.charAt(0) === '"') {
            try {
                value = JSON.parse(valRaw);
            } catch (e) {
                throw new Error('Invalid value literal: ' + valRaw.substring(0, 40));
            }
        } else {
            value = valRaw;
        }
        return {field: fieldEntry.value, operator: opEntry.value, value: String(value)};
    };

    ProtectionRulesEditor.prototype.setEditorStatus = function (kind, message) {
        if (!this.editorStatus) {
            return;
        }
        this.editorStatus.innerHTML = '';
        this.editorStatus.classList.remove('is-error', 'is-empty');
        var rendered = this.renderExpression();
        if (kind === 'error') {
            this.editorStatus.classList.add('is-error');
            this.editorStatus.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-editor-status-tag is-error',
                text: this.labels.rawStatusError || 'Parse error'
            }));
            if (message) {
                this.editorStatus.appendChild(createElement('span', {
                    className: 'hryvinskyi-fx-editor-status-msg',
                    text: message
                }));
            }
            return;
        }
        if (kind === 'empty') {
            this.editorStatus.classList.add('is-empty');
            this.editorStatus.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-editor-status-tag is-empty',
                text: this.labels.rawStatusEmpty || 'Empty'
            }));
            return;
        }
        // ok
        this.editorStatus.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-editor-status-tag is-ok',
            text: this.labels.rawStatusValid || 'Valid'
        }));
        this.editorStatus.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-editor-status-msg',
            text: this.groups.length + ' / ' + rendered.conditionCount
        }));
    };

    // ── Autosuggestion ───────────────────────────────────────────────────
    /**
     * Inspect the textarea content around the caret to decide what kind of
     * token is expected next (field / operator / value / connector) and what
     * prefix the user has already typed for the current token.
     *
     * The returned `wordStart`/`wordEnd` bound the replacement region for an
     * accepted suggestion, and `word` is what the user has typed for the
     * current token (field labels and operator labels can both contain
     * spaces, so the bounds are computed per-context rather than via a
     * generic word-break rule).
     */
    ProtectionRulesEditor.prototype.getCaretContext = function () {
        var pos = this.rawTextarea.selectionStart;
        var text = this.rawTextarea.value;
        var before = text.slice(0, pos);

        // Inside a quoted string? No suggestions there.
        var quoteCount = 0;
        for (var i = 0; i < before.length; i++) {
            var ch = before.charAt(i);
            if (ch === '\\' && i + 1 < before.length) {
                i++;
                continue;
            }
            if (ch === '"') {
                quoteCount++;
            }
        }
        if (quoteCount % 2 === 1) {
            return {expecting: 'value', suppress: true};
        }

        // String-aware depth pass: also record the position of the outermost
        // currently-open `(` so we know where the active condition started.
        var depth = 0;
        var lastOpen = -1;
        var inStr = false;
        for (var k = 0; k < before.length; k++) {
            var bch = before.charAt(k);
            if (inStr) {
                if (bch === '\\' && k + 1 < before.length) {
                    k++;
                    continue;
                }
                if (bch === '"') {
                    inStr = false;
                }
                continue;
            }
            if (bch === '"') {
                inStr = true;
                continue;
            }
            if (bch === '(') {
                if (depth === 0) {
                    lastOpen = k;
                }
                depth++;
            } else if (bch === ')') {
                depth--;
            }
        }

        if (depth <= 0) {
            // Outside any group. Find current token (chars before cursor up to a separator).
            var tokStart = pos;
            while (tokStart > 0) {
                var c = text.charAt(tokStart - 1);
                if (c === '(' || c === ')' || c === '"' || c === ' ' || c === '\n' || c === '\t') {
                    break;
                }
                tokStart--;
            }
            var tokWord = text.slice(tokStart, pos);
            var beforeTok = before.slice(0, tokStart).replace(/\s+$/, '');
            var lastChar = beforeTok.charAt(beforeTok.length - 1);
            if (lastChar === ')') {
                return {expecting: 'connector', word: tokWord, wordStart: tokStart, wordEnd: pos};
            }
            return {expecting: 'group-start', word: tokWord, wordStart: tokStart, wordEnd: pos};
        }

        // Inside parens — examine what's between the last `(` and the cursor.
        var inner = before.slice(lastOpen + 1);

        var matchedField = null;
        for (var f = 0; f < this.fieldsSorted.length; f++) {
            var fv = this.fieldsSorted[f].value;
            if (inner === fv || inner.indexOf(fv + ' ') === 0) {
                matchedField = this.fieldsSorted[f];
                break;
            }
        }
        if (!matchedField) {
            return {
                expecting: 'field',
                word: inner,
                wordStart: lastOpen + 1,
                wordEnd: pos
            };
        }

        // Field is complete. Where does the operator start? Right after the
        // field code, past any whitespace.
        var opStart = lastOpen + 1 + matchedField.value.length;
        while (opStart < pos && /\s/.test(text.charAt(opStart))) {
            opStart++;
        }
        var afterField = text.slice(opStart, pos);

        var matchedOp = null;
        for (var o = 0; o < this.operatorsSorted.length; o++) {
            var ov = this.operatorsSorted[o].value;
            if (afterField === ov
                || afterField.indexOf(ov + ' ') === 0
                || afterField.indexOf(ov + '"') === 0) {
                matchedOp = this.operatorsSorted[o];
                break;
            }
        }
        if (!matchedOp) {
            return {
                expecting: 'operator',
                word: afterField,
                wordStart: opStart,
                wordEnd: pos,
                field: matchedField
            };
        }

        // Operator complete — the value comes next; let the user type it freely.
        return {
            expecting: 'value',
            word: '',
            wordStart: pos,
            wordEnd: pos,
            field: matchedField,
            op: matchedOp
        };
    };

    ProtectionRulesEditor.prototype.refreshSuggestions = function () {
        if (this.mode !== MODE_EXPRESSION) {
            return;
        }
        var ctx = this.getCaretContext();
        this.suggestContext = ctx;
        if (ctx.suppress) {
            this.hideSuggestions();
            return;
        }

        var items = [];
        if (ctx.expecting === 'field') {
            items = this.fields.map(function (f) {
                return {label: f.value, kind: 'field', insert: f.value, hint: f.label};
            });
        } else if (ctx.expecting === 'operator') {
            var allowed = ctx.field ? this.operatorsFor(ctx.field.value) : this.operators;
            items = allowed.map(function (op) {
                return {label: op.value, kind: 'operator', insert: op.value, hint: op.label};
            });
        } else if (ctx.expecting === 'connector') {
            items = [
                {label: 'AND', kind: 'connector', insert: 'AND'},
                {label: 'OR', kind: 'connector', insert: 'OR'}
            ];
        } else if (ctx.expecting === 'group-start') {
            items = [{label: '(', kind: 'literal', insert: '('}];
            this.fields.forEach(function (f) {
                items.push({label: f.value, kind: 'field', insert: '(' + f.value + ' ', hint: f.label});
            });
        } else {
            this.hideSuggestions();
            return;
        }

        var prefix = (ctx.word || '').toLowerCase();
        if (prefix !== '') {
            items = items.filter(function (it) {
                if (it.label.toLowerCase().indexOf(prefix) !== -1) {
                    return true;
                }
                if (it.hint && it.hint.toLowerCase().indexOf(prefix) !== -1) {
                    return true;
                }
                return false;
            });
        }
        items = items.slice(0, 8);

        this.suggestItems = items;
        this.suggestIndex = 0;
        this.renderSuggestions();
    };

    ProtectionRulesEditor.prototype.renderSuggestions = function () {
        if (!this.suggestEl) {
            return;
        }
        this.suggestEl.innerHTML = '';
        if (!this.suggestItems.length) {
            this.suggestEl.setAttribute('hidden', 'hidden');
            return;
        }
        this.suggestEl.removeAttribute('hidden');

        var self = this;
        this.suggestItems.forEach(function (item, index) {
            var row = createElement('div', {
                className: 'hryvinskyi-fx-suggest-item' + (index === self.suggestIndex ? ' is-active' : ''),
                attrs: {'data-index': String(index)}
            });
            row.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-suggest-kind hryvinskyi-fx-suggest-kind-' + item.kind,
                text: item.kind
            }));
            row.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-suggest-label',
                text: item.label
            }));
            if (item.hint) {
                row.appendChild(createElement('span', {
                    className: 'hryvinskyi-fx-suggest-hint',
                    text: item.hint
                }));
            }
            row.addEventListener('mousedown', function (e) {
                // mousedown so we beat the textarea blur
                e.preventDefault();
                self.applySuggestion(index);
            });
            self.suggestEl.appendChild(row);
        });
    };

    ProtectionRulesEditor.prototype.highlightSuggestion = function () {
        if (!this.suggestEl) {
            return;
        }
        var rows = this.suggestEl.querySelectorAll('.hryvinskyi-fx-suggest-item');
        var self = this;
        rows.forEach(function (row) {
            var idx = parseInt(row.getAttribute('data-index'), 10);
            row.classList.toggle('is-active', idx === self.suggestIndex);
        });
    };

    ProtectionRulesEditor.prototype.hideSuggestions = function () {
        if (this.suggestEl) {
            this.suggestEl.setAttribute('hidden', 'hidden');
            this.suggestEl.innerHTML = '';
        }
        this.suggestItems = [];
        this.suggestIndex = 0;
    };

    ProtectionRulesEditor.prototype.applySuggestion = function (index) {
        if (typeof index === 'number') {
            this.suggestIndex = index;
        }
        var item = this.suggestItems[this.suggestIndex];
        var ctx = this.suggestContext;
        if (!item || !ctx) {
            return;
        }
        var text = this.rawTextarea.value;
        var before = text.slice(0, ctx.wordStart);
        var after = text.slice(ctx.wordEnd);

        var insert = item.insert;
        // Tail follow-ups: a field gets " ", an operator gets ' "', a connector
        // gets " (" so the user can keep typing without re-thinking syntax.
        if (item.kind === 'field') {
            insert += ' ';
        } else if (item.kind === 'operator') {
            insert += ' "';
        } else if (item.kind === 'connector') {
            insert += ' (';
        }

        this.rawTextarea.value = before + insert + after;
        var newPos = before.length + insert.length;
        this.rawTextarea.setSelectionRange(newPos, newPos);
        this.rawTextarea.focus();
        this.handleEditorInput();
    };

    ProtectionRulesEditor.prototype.handleEditorKeydown = function (e) {
        var visible = this.suggestEl && !this.suggestEl.hasAttribute('hidden') && this.suggestItems.length > 0;
        if (!visible) {
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.suggestIndex = (this.suggestIndex + 1) % this.suggestItems.length;
            this.highlightSuggestion();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.suggestIndex = (this.suggestIndex - 1 + this.suggestItems.length) % this.suggestItems.length;
            this.highlightSuggestion();
        } else if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            this.applySuggestion();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            this.hideSuggestions();
        }
    };

    // ── State mutations from the builder ─────────────────────────────────
    ProtectionRulesEditor.prototype.addCondition = function (groupIndex) {
        this.captureLiveValues();
        this.groups[groupIndex].push(this.makeDefaultCondition());
        this.refresh();
    };

    ProtectionRulesEditor.prototype.removeCondition = function (groupIndex, condIndex) {
        this.captureLiveValues();
        this.groups[groupIndex].splice(condIndex, 1);
        if (this.groups[groupIndex].length === 0) {
            this.groups.splice(groupIndex, 1);
        }
        if (this.groups.length === 0) {
            this.groups = [[this.makeDefaultCondition()]];
        }
        this.refresh();
    };

    ProtectionRulesEditor.prototype.addGroup = function () {
        this.captureLiveValues();
        this.groups.push([this.makeDefaultCondition()]);
        this.refresh();
    };

    ProtectionRulesEditor.prototype.removeGroup = function (groupIndex) {
        this.captureLiveValues();
        this.groups.splice(groupIndex, 1);
        if (this.groups.length === 0) {
            this.groups = [[this.makeDefaultCondition()]];
        }
        this.refresh();
    };

    ProtectionRulesEditor.prototype.clearAll = function () {
        this.groups = [[this.makeDefaultCondition()]];
        this.refresh();
    };

    /**
     * Read the current value of every live select/input before mutating state
     * so in-flight edits survive structural changes.
     */
    ProtectionRulesEditor.prototype.captureLiveValues = function () {
        var rows = this.builderEl.querySelectorAll('.hryvinskyi-fx-row');
        var flat = [];
        rows.forEach(function (row) {
            var combinator = row.querySelector('input[type="hidden"]');
            var field = row.querySelector('select[name$="[field]"]');
            var operator = row.querySelector('select[name$="[operator]"]');
            // The value control is a text input or, for boolean fields, a select.
            var value = row.querySelector('input[name$="[value]"], select[name$="[value]"]');
            flat.push({
                combinator: combinator ? combinator.value : COMBINATOR_AND,
                field: field ? field.value : '',
                operator: operator ? operator.value : '',
                value: value ? value.value : ''
            });
        });
        if (flat.length) {
            this.groups = toGroups(flat);
        }
    };

    // ── Rule tester panel ────────────────────────────────────────────────
    ProtectionRulesEditor.prototype.buildTester = function () {
        var self = this;
        var wrap = createElement('div', {className: 'hryvinskyi-fx-tester'});
        this.testerWrapEl = wrap;

        // Collapsed by default — the whole header is the toggle.
        var head = createElement('button', {
            className: 'hryvinskyi-fx-tester-head',
            attrs: {type: 'button', 'aria-expanded': 'false'}
        });
        head.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-tester-chevron',
            html: SVG_CHEVRON
        }));
        head.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-tester-title',
            text: this.labels.testerTitle || 'Test Rules'
        }));
        head.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-tester-hint',
            text: this.labels.testerHint || ''
        }));
        head.addEventListener('click', function () {
            self.toggleTester();
        });
        this.testerHeadEl = head;
        wrap.appendChild(head);

        this.testerBodyEl = createElement('div', {className: 'hryvinskyi-fx-tester-body'});
        this.testerBodyEl.hidden = true;
        wrap.appendChild(this.testerBodyEl);

        var grid = createElement('div', {className: 'hryvinskyi-fx-tester-grid'});

        this.testerUrlInput = createElement('input', {
            className: 'hryvinskyi-fx-tester-input hryvinskyi-fx-mono',
            attrs: {type: 'text', placeholder: this.labels.testerUrlPlaceholder || '/checkout/cart'}
        });
        grid.appendChild(this.buildTesterField(this.labels.testerUrl || 'URL or path', this.testerUrlInput, 'wide'));

        this.testerStoreSelect = createElement('select', {className: 'hryvinskyi-fx-tester-input'});
        (this.config.tester.stores || []).forEach(function (store) {
            var option = createElement('option', {text: store.label, attrs: {value: String(store.value)}});
            if (String(store.value) === String(self.config.tester.defaultStoreId)) {
                option.selected = true;
            }
            self.testerStoreSelect.appendChild(option);
        });
        grid.appendChild(this.buildTesterField(this.labels.testerStore || 'Store View', this.testerStoreSelect));

        this.testerMethodSelect = createElement('select', {className: 'hryvinskyi-fx-tester-input'});
        ['GET', 'POST', 'HEAD', 'PUT', 'DELETE'].forEach(function (method) {
            self.testerMethodSelect.appendChild(createElement('option', {text: method, attrs: {value: method}}));
        });
        grid.appendChild(this.buildTesterField(this.labels.testerMethod || 'Method', this.testerMethodSelect));

        this.testerUaInput = createElement('input', {
            className: 'hryvinskyi-fx-tester-input',
            attrs: {type: 'text', placeholder: 'Mozilla/5.0 (compatible; SomeBot/1.0)'}
        });
        grid.appendChild(this.buildTesterField(this.labels.testerUserAgent || 'User-Agent', this.testerUaInput, 'wide'));

        this.testerIpInput = createElement('input', {
            className: 'hryvinskyi-fx-tester-input hryvinskyi-fx-mono',
            attrs: {type: 'text', placeholder: '203.0.113.10'}
        });
        grid.appendChild(this.buildTesterField(this.labels.testerClientIp || 'Client IP', this.testerIpInput));

        this.testerRefererInput = createElement('input', {
            className: 'hryvinskyi-fx-tester-input hryvinskyi-fx-mono',
            attrs: {type: 'text', placeholder: 'https://www.google.com/'}
        });
        grid.appendChild(this.buildTesterField(this.labels.testerReferer || 'Referer', this.testerRefererInput));

        this.testerActionInput = createElement('input', {
            className: 'hryvinskyi-fx-tester-input hryvinskyi-fx-mono',
            attrs: {type: 'text', placeholder: this.labels.testerActionNameHint || 'auto-detected'}
        });
        grid.appendChild(this.buildTesterField(this.labels.testerActionName || 'Full Action Name', this.testerActionInput));

        this.testerBodyEl.appendChild(grid);

        var actions = createElement('div', {className: 'hryvinskyi-fx-tester-actions'});
        this.testerRunBtn = createElement('button', {
            className: 'hryvinskyi-fx-btn hryvinskyi-fx-btn-primary',
            attrs: {type: 'button'},
            text: this.labels.testerRun || 'Run Test'
        });
        this.testerRunBtn.addEventListener('click', function () {
            self.runRuleTest();
        });
        actions.appendChild(this.testerRunBtn);
        this.testerStatusEl = createElement('span', {className: 'hryvinskyi-fx-tester-status'});
        actions.appendChild(this.testerStatusEl);
        this.testerBodyEl.appendChild(actions);

        this.testerResultEl = createElement('div', {className: 'hryvinskyi-fx-tester-result'});
        this.testerResultEl.hidden = true;
        this.testerBodyEl.appendChild(this.testerResultEl);

        this.testerUrlInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                self.runRuleTest();
            }
        });

        return wrap;
    };

    ProtectionRulesEditor.prototype.toggleTester = function () {
        var open = this.testerBodyEl.hidden;
        this.testerBodyEl.hidden = !open;
        this.testerWrapEl.classList.toggle('is-open', open);
        this.testerHeadEl.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            this.testerUrlInput.focus();
        }
    };

    ProtectionRulesEditor.prototype.buildTesterField = function (labelText, input, modifier) {
        var field = createElement('label', {
            className: 'hryvinskyi-fx-tester-field' + (modifier ? ' hryvinskyi-fx-tester-field-' + modifier : '')
        });
        field.appendChild(createElement('span', {className: 'hryvinskyi-fx-tester-label', text: labelText}));
        field.appendChild(input);
        return field;
    };

    /**
     * Flatten the editor state back into the persisted row list so the
     * tester evaluates exactly what the admin sees (unsaved included).
     */
    ProtectionRulesEditor.prototype.serializeDraftRows = function () {
        if (this.mode === MODE_BUILDER) {
            this.captureLiveValues();
        }
        var rows = [];
        this.groups.forEach(function (group, groupIndex) {
            group.forEach(function (cond, condIndex) {
                rows.push({
                    combinator: groupIndex > 0 && condIndex === 0 ? COMBINATOR_OR : COMBINATOR_AND,
                    field: cond.field || '',
                    operator: cond.operator || '',
                    value: cond.value || ''
                });
            });
        });
        return rows;
    };

    ProtectionRulesEditor.prototype.runRuleTest = function () {
        var self = this;
        var url = (this.testerUrlInput.value || '').trim();
        this.testerStatusEl.textContent = '';

        if (url === '') {
            this.testerUrlInput.focus();
            return;
        }

        var body = new URLSearchParams();
        body.append('form_key', window.FORM_KEY || '');
        body.append('url', url);
        body.append('store_id', this.testerStoreSelect.value || '');
        body.append('method', this.testerMethodSelect.value || 'GET');
        body.append('user_agent', this.testerUaInput.value || '');
        body.append('client_ip', (this.testerIpInput.value || '').trim());
        body.append('referer', (this.testerRefererInput.value || '').trim());
        body.append('action_name', (this.testerActionInput.value || '').trim());
        body.append('rules', JSON.stringify(this.serializeDraftRows()));

        this.testerRunBtn.disabled = true;
        this.testerRunBtn.textContent = this.labels.testerRunning || 'Testing…';

        window.fetch(this.config.tester.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: body
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            if (data && data.ok) {
                self.renderTestResult(data);
            } else {
                self.testerResultEl.hidden = true;
                self.testerStatusEl.textContent = (data && data.message) || self.labels.testerError || 'Request failed.';
            }
        }).catch(function () {
            self.testerResultEl.hidden = true;
            self.testerStatusEl.textContent = self.labels.testerError || 'Request failed.';
        }).then(function () {
            self.testerRunBtn.disabled = false;
            self.testerRunBtn.textContent = self.labels.testerRun || 'Run Test';
        });
    };

    ProtectionRulesEditor.prototype.renderTestResult = function (data) {
        var self = this;
        this.testerResultEl.innerHTML = '';
        this.testerResultEl.hidden = false;

        var verdictClass = 'pass';
        var verdictText = this.labels.verdictPass || 'PASS';
        if (data.wouldChallenge) {
            verdictClass = 'challenge';
            verdictText = this.labels.verdictChallenge || 'CHALLENGE';
        } else if (data.matched) {
            verdictClass = 'idle';
            verdictText = this.labels.verdictMatchedIdle || 'MATCHED — inactive';
        }

        var banner = createElement('div', {className: 'hryvinskyi-fx-tester-verdict hryvinskyi-fx-tester-verdict-' + verdictClass});
        banner.appendChild(createElement('strong', {text: verdictText}));

        var reasons = this.collectIdleReasons(data);
        if (data.matched && !data.wouldChallenge && reasons.length) {
            banner.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-tester-reasons',
                text: reasons.join('; ')
            }));
        }
        this.testerResultEl.appendChild(banner);

        var context = data.context || {};
        var fields = data.fields || {};
        var metaBits = [];
        if (context.store) {
            metaBits.push((this.labels.testerStore || 'Store View') + ': ' + context.store);
        }
        if (context.requestUri) {
            metaBits.push(context.requestUri);
        }
        if (fields.action_name) {
            metaBits.push('action: ' + fields.action_name + (context.actionNameSource ? ' (' + context.actionNameSource + ')' : ''));
        }
        if (metaBits.length) {
            this.testerResultEl.appendChild(createElement('div', {
                className: 'hryvinskyi-fx-tester-meta hryvinskyi-fx-mono',
                text: metaBits.join('  ·  ')
            }));
        }

        (data.warnings || []).forEach(function (warning) {
            self.testerResultEl.appendChild(createElement('div', {
                className: 'hryvinskyi-fx-tester-warning',
                text: warning
            }));
        });

        (data.groups || []).forEach(function (group, index) {
            self.testerResultEl.appendChild(self.renderTestGroup(group, index));
        });

        this.testerResultEl.appendChild(this.renderTestFields(fields));
    };

    ProtectionRulesEditor.prototype.collectIdleReasons = function (data) {
        var reasons = [];
        var bypass = data.bypass || {};
        var context = data.context || {};
        if (bypass.excludedIp) {
            reasons.push(this.labels.reasonExcludedIp || 'excluded IP');
        }
        if (bypass.excludedUserAgent) {
            reasons.push(this.labels.reasonExcludedUa || 'excluded user agent');
        }
        if (bypass.verifyEndpoint) {
            reasons.push(this.labels.reasonVerifyEndpoint || 'verify endpoint');
        }
        if (!context.routeProtectionEnabled) {
            reasons.push(this.labels.reasonDisabled || 'route protection disabled');
        }
        if (!context.providerConfigured) {
            reasons.push(this.labels.reasonNotConfigured || 'provider not configured');
        }
        return reasons;
    };

    ProtectionRulesEditor.prototype.renderTestGroup = function (group, index) {
        var groupEl = createElement('div', {className: 'hryvinskyi-fx-tester-group'});
        var head = createElement('div', {className: 'hryvinskyi-fx-tester-group-head'});
        head.appendChild(createElement('span', {
            text: (this.labels.testerGroup || 'Group') + ' ' + (index + 1)
        }));
        head.appendChild(createElement('span', {
            className: 'hryvinskyi-fx-tester-badge ' + (group.matched ? 'is-hit' : 'is-miss'),
            text: group.matched
                ? (this.labels.testerMatched || 'matched')
                : (this.labels.testerNotMatched || 'not matched')
        }));
        groupEl.appendChild(head);

        var self = this;
        (group.conditions || []).forEach(function (condition) {
            var row = createElement('div', {className: 'hryvinskyi-fx-tester-row'});
            row.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-tester-badge ' + (condition.matched ? 'is-hit' : 'is-miss'),
                text: condition.matched ? '✓' : '✗'
            }));
            row.appendChild(createElement('code', {
                className: 'hryvinskyi-fx-mono',
                text: condition.field + ' ' + condition.operator + ' ' + JSON.stringify(String(condition.value))
            }));
            row.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-tester-actual hryvinskyi-fx-mono',
                text: (self.labels.testerActual || 'actual') + ': ' + self.formatFieldValue(condition.fieldValue)
            }));
            groupEl.appendChild(row);
        });

        return groupEl;
    };

    ProtectionRulesEditor.prototype.renderTestFields = function (fields) {
        var details = createElement('details', {className: 'hryvinskyi-fx-tester-fields'});
        details.appendChild(createElement('summary', {
            text: this.labels.testerFieldsTitle || 'Resolved field values'
        }));
        var list = createElement('div', {className: 'hryvinskyi-fx-tester-fields-list'});
        var self = this;
        Object.keys(fields).forEach(function (code) {
            var row = createElement('div', {className: 'hryvinskyi-fx-tester-fields-row'});
            row.appendChild(createElement('span', {className: 'hryvinskyi-fx-mono', text: code}));
            row.appendChild(createElement('span', {
                className: 'hryvinskyi-fx-mono',
                text: self.formatFieldValue(fields[code])
            }));
            list.appendChild(row);
        });
        details.appendChild(list);
        return details;
    };

    ProtectionRulesEditor.prototype.formatFieldValue = function (value) {
        if (value === null || value === undefined) {
            return '—';
        }
        if (typeof value === 'string') {
            return JSON.stringify(value);
        }
        return String(value);
    };

    return function (config, element) {
        var root = element || (config && config.nodeType ? config : null);
        if (!root) {
            return;
        }
        new ProtectionRulesEditor(root);
    };
});
