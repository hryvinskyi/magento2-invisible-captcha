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
        this.fields.forEach(function (field) {
            this.fieldTypeByCode[field.value] = field.type;
            this.fieldsByCode[field.value] = field;
            this.fieldsByLabel[field.label] = field;
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
        var valueInput = createElement('input', {
            className: 'hryvinskyi-fx-input hryvinskyi-fx-mono',
            attrs: {
                type: 'text',
                name: this.inputName(rowKey, 'value'),
                value: condition.value || '',
                placeholder: this.labels.valuePlaceholder || 'Value'
            }
        });
        valueInput.addEventListener('input', function () {
            condition.value = valueInput.value;
            self.refreshPreview();
        });

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
        valCell.appendChild(valueInput);

        row.appendChild(fieldCell);
        row.appendChild(opCell);
        row.appendChild(valCell);
        row.appendChild(removeBtn);
        row.appendChild(combinatorInput);

        fieldSelect.addEventListener('change', function () {
            condition.field = fieldSelect.value;
            self.repopulateOperator(operatorSelect, condition);
            self.refreshPreview();
        });
        operatorSelect.addEventListener('change', function () {
            condition.operator = operatorSelect.value;
            self.refreshPreview();
        });

        return row;
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
            var value = row.querySelector('input[name$="[value]"]');
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

    return function (config, element) {
        var root = element || (config && config.nodeType ? config : null);
        if (!root) {
            return;
        }
        new ProtectionRulesEditor(root);
    };
});
