export const schemaPresentation = {
    jsonSchemaColorStyles(color) {
        if (color === undefined) return {
            '--json-schema-field-color': '',
            '--json-schema-field-color-dark': '',
        }
        const literal = color.startsWith('#') || /^rgba?\(/i.test(color)
        return {
            '--json-schema-field-color': literal ? color : `var(--${color}-600)`,
            '--json-schema-field-color-dark': literal ? color : `var(--${color}-400)`,
        }
    },
    jsonSchemaConstraintValue(value) {
        if (value.kind === 'pattern') return '/' + value.value + '/'
        if (value.kind === 'multiple') return '× ' + value.value
        return value.value
    },
    jsonSchemaConstraintTooltip(constraint, value, labels) {
        return restrictionScope(constraint, labels) + constraintDescription(value, labels)
    },
    jsonSchemaConstraintsPopover(source, labels) {
        return constraintsPopover(source.constraints, labels)
    },
    jsonSchemaEnumPopover(source, labels) {
        return restrictionPopover(labels.enum, source.enums, enumeration => '<div class="eidos-json-schema-popover__values">'
            + enumeration.values.map(value => '<code class="eidos-json-schema-popover__enum-value">' + escapeHtml(value) + '</code>').join('')
            + '</div>', labels)
    },
    jsonSchemaConstraintsTooltip(source, labels) {
        return source.constraints.length ? labels.limits + '\n' + source.constraints.map(constraint => restrictionScope(constraint, labels)
            + constraint.values.map(value => constraintDescription(value, labels)).join('\n')).join('\n\n') : ''
    },
    jsonSchemaEnumTooltip(source, labels) {
        return source.enums.length ? labels.enum + '\n' + source.enums.map(enumeration => restrictionScope(enumeration, labels) + enumeration.values.join(', ')).join('\n\n') : ''
    },
    jsonSchemaArrayTooltip(source, labels) {
        return source.fieldLabel + '\n' + [source, ...source.arrayItems].map(level =>
            (level.scope?.length ? scopeLabel(level.scope, labels) + ': ' : '') + level.type + (level.nullable ? ' | null' : '')
        ).join('\n')
    },
    jsonSchemaArrayPopover(source, labels, descriptions = true) {
        return '<div class="eidos-json-schema-popover"><div class="eidos-json-schema-popover__title">' + escapeHtml(source.fieldLabel) + '</div>'
            + [source, ...source.arrayItems].map(level => '<div class="eidos-json-schema-popover__group">'
                + '<div class="eidos-json-schema-popover__scope"><span>' + escapeHtml(level.scope?.length ? scopeLabel(level.scope, labels) : source.fieldName)
                + '</span><span class="eidos-json-schema-popover__type">' + escapeHtml(level.type + (level.nullable ? ' | null' : '')) + '</span></div>'
                + (level.scope?.length && level.titles.length ? '<div>' + escapeHtml(level.titles.join(' / ')) + '</div>' : '')
                + (descriptions && level.description ? '<div class="eidos-json-schema-popover__description">' + escapeHtml(level.description) + '</div>' : '')
                + (level.constraints.length ? constraintsPopover(level.constraints, labels, '') : '')
                + (level.enums.length ? this.jsonSchemaEnumPopover(level, labels) : '')
                + '</div>').join('') + '</div>'
    },
}

function constraintsPopover(constraints, labels, title = labels.limits) {
    return restrictionPopover(title, constraints, constraint => '<dl class="eidos-json-schema-popover__limits">'
        + constraint.values.map(value => '<dt class="eidos-json-schema-popover__label">' + escapeHtml(labels.constraints[value.kind]) + '</dt>'
            + '<dd class="eidos-json-schema-popover__limit-value' + (value.kind === 'unique' ? ' eidos-json-schema-popover__limit-value--unique' : '') + '">'
            + escapeHtml(value.kind === 'unique' ? '✓' : value.value) + '</dd>').join('')
        + '</dl>', labels)
}

function restrictionPopover(title, restrictions, content, labels) {
    return '<div class="eidos-json-schema-popover">' + (title ? '<div class="eidos-json-schema-popover__title">' + escapeHtml(title) + '</div>' : '')
        + restrictions.map(restriction => '<div class="eidos-json-schema-popover__group">'
            + (restriction.scope.length ? '<div class="eidos-json-schema-popover__scope"><span>'
                + escapeHtml(scopeLabel(restriction.scope, labels))
                + '</span><span class="eidos-json-schema-popover__type">' + escapeHtml(restriction.type) + '</span></div>' : '')
            + content(restriction) + '</div>').join('') + '</div>'
}

function escapeHtml(value) {
    return value.replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character])
}

function constraintDescription(value, labels) {
    return labels.constraints[value.kind] + (value.value === '' ? '' : ': ' + value.value)
}

function restrictionScope(restriction, labels) {
    return restriction.scope.length
        ? scopeLabel(restriction.scope, labels) + ' (' + restriction.type + ')\n'
        : ''
}

function scopeLabel(scope, labels) {
    return scope.map(part => typeof part === 'number' ? labels.alternative + ' ' + part : part === 'items' ? labels.items : part).join(' → ')
}
