const { Extension, Mark, Node, mergeAttributes } = window.FilamentRichEditor.tiptap.core
const { NodeSelection, Plugin, TextSelection } = window.FilamentRichEditor.tiptap.pmState
const { Decoration, DecorationSet } = window.FilamentRichEditor.tiptap.pmView
const floatingToolbarNodeNames = new Set(['table'])

const TwigVariable = Node.create({
    name: 'twigVariable',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,

    addAttributes() {
        return {
            expression: {
                default: '',
                parseHTML: element => element.getAttribute('data-twig-expression') ?? '',
                renderHTML: attributes => ({
                    'data-twig-expression': attributes.expression,
                }),
            },
            filters: {
                default: [],
                parseHTML: element => parseFilters(element.getAttribute('data-twig-filters')),
                renderHTML: attributes => ({
                    'data-twig-filters': JSON.stringify(attributes.filters ?? []),
                }),
            },
        }
    },

    parseHTML() {
        return [{ tag: 'span[data-twig-variable]' }]
    },

    renderHTML({ node, HTMLAttributes }) {
        const filterSummary = formatFilterSummary(node.attrs.filters)

        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                'data-twig-variable': '',
                ...(filterSummary
                    ? { 'data-twig-filter-summary': filterSummary }
                    : {}),
                contenteditable: 'false',
                title: formatVariable(node.attrs),
            }),
            formatVariable(node.attrs),
        ]
    },

    addCommands() {
        return {
            insertTwigVariable:
                attributes =>
                ({ commands }) => commands.insertContent({
                    type: this.name,
                    attrs: normalizeVariableAttributes(attributes),
                }),
            updateTwigVariable:
                attributes =>
                ({ commands }) => commands.updateAttributes(
                    this.name,
                    normalizeVariableAttributes(attributes),
                ),
            updateTwigVariableAt:
                (position, attributes) =>
                ({ state, dispatch }) => {
                    const node = state.doc.nodeAt(position)

                    if (node?.type.name !== this.name) {
                        return false
                    }

                    if (dispatch) {
                        dispatch(state.tr.setNodeMarkup(
                            position,
                            undefined,
                            normalizeVariableAttributes(attributes),
                        ))
                    }

                    return true
                },
            deleteTwigVariable:
                () =>
                ({ state, dispatch }) => deleteActiveNode(state, dispatch, this.name),
        }
    },
})

const TwigInlineIf = Mark.create({
    name: 'twigInlineIf',
    excludes: '',
    inclusive: false,

    addAttributes() {
        return {
            condition: {
                default: '',
                parseHTML: element => element.getAttribute('data-twig-condition') ?? '',
                renderHTML: attributes => ({
                    'data-twig-condition': attributes.condition,
                }),
            },
            conditionAst: {
                default: null,
                parseHTML: element => parseConditionAst(element.getAttribute('data-twig-condition-ast')),
                renderHTML: attributes => attributes.conditionAst
                    ? { 'data-twig-condition-ast': JSON.stringify(attributes.conditionAst) }
                    : {},
            },
            conditionId: {
                default: null,
                parseHTML: element => element.getAttribute('data-twig-inline-condition-id') || null,
                renderHTML: attributes => attributes.conditionId
                    ? { 'data-twig-inline-condition-id': attributes.conditionId }
                    : {},
            },
            branch: {
                default: null,
                parseHTML: element => element.getAttribute('data-twig-inline-branch') || null,
                renderHTML: attributes => attributes.branch
                    ? { 'data-twig-inline-branch': attributes.branch }
                    : {},
            },
        }
    },

    parseHTML() {
        return [{ tag: 'span[data-twig-inline-if]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { 'data-twig-inline-if': 'true' }), 0]
    },

    addCommands() {
        return {
            insertTwigInlineIf:
                attributes =>
                ({ chain, state }) => {
                    if (!canApplyTwigInlineIf(state)) {
                        return false
                    }

                    return chain()
                        .setMark(this.name, normalizeConditionAttributes({
                            ...attributes,
                            conditionId: attributes.conditionId ?? createInlineConditionId(),
                            branch: 'then',
                        }))
                        .run()
                },
            updateTwigInlineIf:
                attributes =>
                ({ state, dispatch }) => updateInlineCondition(state, dispatch, this.type, attributes),
            unwrapTwigInlineIf:
                conditionId =>
                ({ state, dispatch }) => unwrapInlineCondition(state, dispatch, conditionId),
            deleteTwigInlineIf:
                conditionId =>
                ({ state, dispatch }) => deleteInlineCondition(state, dispatch, conditionId),
        }
    },
})

const TwigApply = Node.create({
    name: 'twigApply',
    group: 'block',
    content: 'block+',
    defining: true,
    isolating: true,

    addAttributes() {
        return {
            filters: {
                default: [],
                parseHTML: element => parseFilters(element.getAttribute('data-twig-apply-filters')),
                renderHTML: attributes => ({
                    'data-twig-apply-filters': JSON.stringify(attributes.filters ?? []),
                    'data-twig-apply-summary': formatApplySummary(attributes.filters ?? []),
                }),
            },
        }
    },

    parseHTML() {
        return [{ tag: 'section[data-twig-apply]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['section', mergeAttributes(HTMLAttributes, { 'data-twig-apply': '' }), 0]
    },

    addCommands() {
        return {
            insertTwigApply:
                attributes =>
                ({ commands }) => commands.wrapIn(this.name, {
                    filters: attributes.filters ?? [],
                }),
            updateTwigApply:
                attributes =>
                ({ commands }) => commands.updateAttributes(this.name, {
                    filters: attributes.filters ?? [],
                }),
            unwrapTwigApply:
                () =>
                ({ state, dispatch }) => unwrapActiveNode(state, dispatch, this.name),
            deleteTwigApply:
                () =>
                ({ state, dispatch }) => deleteActiveNode(state, dispatch, this.name),
        }
    },
})

const TwigThen = Node.create({
    name: 'twigThen',
    content: 'block+',
    defining: true,

    parseHTML() {
        return [{ tag: 'div[data-twig-branch="then"]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { 'data-twig-branch': 'then' }), 0]
    },
})

const TwigElse = Node.create({
    name: 'twigElse',
    content: 'block+',
    defining: true,

    parseHTML() {
        return [{ tag: 'div[data-twig-branch="else"]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { 'data-twig-branch': 'else' }), 0]
    },
})

const TwigIf = Node.create({
    name: 'twigIf',
    group: 'block',
    content: 'twigThen twigElse?',
    defining: true,
    isolating: true,

    addAttributes() {
        return {
            condition: {
                default: '',
                parseHTML: element => element.getAttribute('data-twig-condition') ?? '',
                renderHTML: attributes => ({
                    'data-twig-condition': attributes.condition,
                }),
            },
            conditionAst: {
                default: null,
                parseHTML: element => parseConditionAst(element.getAttribute('data-twig-condition-ast')),
                renderHTML: attributes => attributes.conditionAst
                    ? { 'data-twig-condition-ast': JSON.stringify(attributes.conditionAst) }
                    : {},
            },
        }
    },

    parseHTML() {
        return [{ tag: 'section[data-twig-if]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['section', mergeAttributes(HTMLAttributes, { 'data-twig-if': '' }), 0]
    },

    addCommands() {
        return {
            insertTwigIf:
                attributes =>
                ({ commands, dispatch, state }) => {
                    const nodeAttributes = {
                        condition: attributes.condition,
                        conditionAst: attributes.conditionAst ?? null,
                    }

                    if (state.selection.empty) {
                        return commands.insertContent({
                            type: this.name,
                            attrs: nodeAttributes,
                            content: [
                                {
                                    type: 'twigThen',
                                    content: [{ type: 'paragraph' }],
                                },
                                ...(attributes.hasElse
                                    ? [{
                                        type: 'twigElse',
                                        content: [{ type: 'paragraph' }],
                                    }]
                                    : []),
                            ],
                        })
                    }

                    const range = state.selection.$from.blockRange(state.selection.$to)
                    const thenType = state.schema.nodes.twigThen

                    if (
                        !range
                        || !thenType
                        || !range.parent.canReplaceWith(range.startIndex, range.endIndex, this.type)
                    ) {
                        return false
                    }

                    let contentMatch = thenType.contentMatch

                    for (let index = range.startIndex; contentMatch && index < range.endIndex; index += 1) {
                        contentMatch = contentMatch.matchType(range.parent.child(index).type)
                    }

                    if (!contentMatch?.validEnd) {
                        return false
                    }

                    if (dispatch) {
                        const transaction = state.tr.wrap(range, [
                            { type: this.type, attrs: nodeAttributes },
                            { type: thenType },
                        ])

                        if (attributes.hasElse) {
                            const paragraph = state.schema.nodes.paragraph?.createAndFill()
                            const elseType = state.schema.nodes.twigElse
                            const conditionNode = transaction.doc.nodeAt(range.start)

                            if (!paragraph || !elseType || conditionNode?.type !== this.type) {
                                return false
                            }

                            transaction.insert(
                                range.start + conditionNode.nodeSize - 1,
                                elseType.create(null, paragraph),
                            )
                        }

                        dispatch(transaction.scrollIntoView())
                    }

                    return true
                },
            updateTwigIf:
                attributes =>
                ({ commands }) => commands.updateAttributes(this.name, {
                    condition: attributes.condition,
                    conditionAst: attributes.conditionAst ?? null,
                }),
            addTwigElse:
                () =>
                ({ state, dispatch }) => {
                    const selected = findActiveNode(state, this.name)

                    if (selected === null || selected.node.childCount === 2) {
                        return false
                    }

                    const paragraph = state.schema.nodes.paragraph?.createAndFill()
                    const elseType = state.schema.nodes.twigElse

                    if (!paragraph || !elseType) {
                        return false
                    }

                    if (dispatch) {
                        const elseBranch = elseType.create(null, paragraph)
                        const insertAt = selected.position + selected.node.nodeSize - 1

                        dispatch(state.tr.insert(insertAt, elseBranch))
                    }

                    return true
                },
            removeTwigElse:
                () =>
                ({ state, dispatch }) => {
                    const selected = findActiveNode(state, this.name)

                    if (selected === null || selected.node.childCount !== 2) {
                        return false
                    }

                    if (dispatch) {
                        const elseBranch = selected.node.child(1)
                        const elsePosition = selected.position + 1 + selected.node.child(0).nodeSize

                        dispatch(state.tr.delete(elsePosition, elsePosition + elseBranch.nodeSize))
                    }

                    return true
                },
            unwrapTwigIf:
                () =>
                ({ state, dispatch }) => unwrapActiveNode(state, dispatch, this.name, true),
            deleteTwigIf:
                () =>
                ({ state, dispatch }) => deleteActiveNode(state, dispatch, this.name),
        }
    },
})

const TwigFor = Node.create({
    name: 'twigFor',
    group: 'block',
    content: 'twigForBody twigForElse?',
    defining: true,
    isolating: true,

    addAttributes() {
        return {
            item: dataAttribute('data-twig-item', ''),
            key: dataAttribute('data-twig-key', null),
            iterable: dataAttribute('data-twig-iterable', ''),
            transforms: {
                default: [],
                parseHTML: element => parseTransforms(element.getAttribute('data-twig-transforms')),
                renderHTML: attributes => {
                    const transforms = attributes.transforms ?? []

                    return {
                        'data-twig-transforms': JSON.stringify(transforms),
                        'data-twig-transform-summary': formatTransformSummary(transforms),
                    }
                },
            },
        }
    },

    parseHTML() {
        return [{ tag: 'section[data-twig-for]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['section', mergeAttributes(HTMLAttributes, { 'data-twig-for': '' }), 0]
    },

    addCommands() {
        return {
            insertTwigFor:
                attributes =>
                ({ commands }) => commands.insertContent({
                    type: this.name,
                    attrs: normalizeLoopAttributes(attributes),
                    content: [
                        {
                            type: 'twigForBody',
                            content: [{ type: 'paragraph' }],
                        },
                        ...(attributes.hasElse
                            ? [{
                                type: 'twigForElse',
                                content: [{ type: 'paragraph' }],
                            }]
                            : []),
                    ],
                }),
            updateTwigFor:
                attributes =>
                ({ commands }) => commands.updateAttributes(
                    this.name,
                    normalizeLoopAttributes(attributes),
                ),
            updateTwigForAt:
                (position, attributes) =>
                ({ state, dispatch }) => {
                    const node = state.doc.nodeAt(position)

                    if (node?.type.name !== this.name) {
                        return false
                    }

                    const hasElse = node.lastChild?.type.name === 'twigForElse'
                    const shouldHaveElse = attributes.hasElse === true
                    const paragraph = shouldHaveElse && ! hasElse
                        ? state.schema.nodes.paragraph?.createAndFill()
                        : null
                    const elseType = state.schema.nodes.twigForElse

                    if (shouldHaveElse && ! hasElse && (! paragraph || ! elseType)) {
                        return false
                    }

                    if (dispatch) {
                        const transaction = state.tr.setNodeMarkup(
                            position,
                            undefined,
                            normalizeLoopAttributes(attributes),
                        )

                        if (shouldHaveElse && ! hasElse) {
                            transaction.insert(
                                position + node.nodeSize - 1,
                                elseType.create(null, paragraph),
                            )
                        } else if (! shouldHaveElse && hasElse) {
                            const elseBranch = node.lastChild
                            const elsePosition = position + 1 + node.child(0).nodeSize

                            transaction.delete(elsePosition, elsePosition + elseBranch.nodeSize)
                        }

                        dispatch(transaction)
                    }

                    return true
                },
            addTwigForElse:
                () =>
                ({ state, dispatch }) => {
                    const selected = findActiveNode(state, this.name)

                    if (selected === null || selected.node.childCount === 2) {
                        return false
                    }

                    const paragraph = state.schema.nodes.paragraph?.createAndFill()
                    const elseType = state.schema.nodes.twigForElse

                    if (!paragraph || !elseType) {
                        return false
                    }

                    if (dispatch) {
                        const elseBranch = elseType.create(null, paragraph)
                        const insertAt = selected.position + selected.node.nodeSize - 1

                        dispatch(state.tr.insert(insertAt, elseBranch))
                    }

                    return true
                },
            removeTwigForElse:
                () =>
                ({ state, dispatch }) => {
                    const selected = findActiveNode(state, this.name)

                    if (selected === null || selected.node.childCount !== 2) {
                        return false
                    }

                    if (dispatch) {
                        const elseBranch = selected.node.child(1)
                        const elsePosition = selected.position + 1 + selected.node.child(0).nodeSize

                        dispatch(state.tr.delete(elsePosition, elsePosition + elseBranch.nodeSize))
                    }

                    return true
                },
            unwrapTwigFor:
                () =>
                ({ state, dispatch }) => unwrapTwigForNode(state, dispatch, this.name),
            deleteTwigFor:
                () =>
                ({ state, dispatch }) => deleteActiveNode(state, dispatch, this.name),
        }
    },
})

const TwigForBody = Node.create({
    name: 'twigForBody',
    content: 'block+',
    defining: true,

    parseHTML() {
        return [{ tag: 'div[data-twig-for-branch="body"]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { 'data-twig-for-branch': 'body' }), 0]
    },
})

const TwigForElse = Node.create({
    name: 'twigForElse',
    content: 'block+',
    defining: true,

    parseHTML() {
        return [{ tag: 'div[data-twig-for-branch="else"]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { 'data-twig-for-branch': 'else' }), 0]
    },
})

function findActiveNode(state, typeName) {
    if (state.selection.node?.type.name === typeName) {
        return {
            node: state.selection.node,
            position: state.selection.from,
        }
    }

    for (let depth = state.selection.$from.depth; depth > 0; depth -= 1) {
        const node = state.selection.$from.node(depth)

        if (node.type.name === typeName) {
            return {
                node,
                position: state.selection.$from.before(depth),
            }
        }
    }

    return null
}

function deleteActiveNode(state, dispatch, typeName) {
    const selected = findActiveNode(state, typeName)

    if (selected === null) {
        return false
    }

    if (dispatch) {
        dispatch(state.tr.delete(selected.position, selected.position + selected.node.nodeSize))
    }

    return true
}

function unwrapActiveNode(state, dispatch, typeName, firstChildOnly = false) {
    const selected = findActiveNode(state, typeName)

    if (selected === null) {
        return false
    }

    if (dispatch) {
        const content = firstChildOnly
            ? selected.node.firstChild.content
            : selected.node.content

        dispatch(state.tr.replaceWith(
            selected.position,
            selected.position + selected.node.nodeSize,
            content,
        ))
    }

    return true
}

function unwrapTwigForNode(state, dispatch, typeName) {
    const selected = findActiveNode(state, typeName)

    if (selected === null) {
        return false
    }

    if (dispatch) {
        const content = selected.node.firstChild?.type.name === 'twigForBody'
            ? selected.node.firstChild.content
            : selected.node.content

        dispatch(state.tr.replaceWith(
            selected.position,
            selected.position + selected.node.nodeSize,
            content,
        ))
    }

    return true
}

function deleteNodeAtPosition(editor, position, typeName) {
    const node = editor?.state.doc.nodeAt(position)

    if (node?.type.name !== typeName) {
        return false
    }

    editor.view.dispatch(editor.state.tr.delete(position, position + node.nodeSize))

    return true
}

function updateTwigApplyAtPosition(editor, position, filters) {
    const node = editor?.state.doc.nodeAt(position)

    if (node?.type.name !== 'twigApply') {
        return false
    }

    editor.view.dispatch(editor.state.tr.setNodeMarkup(position, undefined, {
        ...node.attrs,
        filters,
    }))

    return true
}

function unwrapTwigApplyAtPosition(editor, position) {
    const node = editor?.state.doc.nodeAt(position)

    if (node?.type.name !== 'twigApply') {
        return false
    }

    editor.view.dispatch(editor.state.tr.replaceWith(
        position,
        position + node.nodeSize,
        node.content,
    ))

    return true
}

function unwrapTwigForAtPosition(editor, position) {
    const node = editor?.state.doc.nodeAt(position)

    if (node?.type.name !== 'twigFor') {
        return false
    }

    const content = node.firstChild?.type.name === 'twigForBody'
        ? node.firstChild.content
        : node.content

    editor.view.dispatch(editor.state.tr.replaceWith(
        position,
        position + node.nodeSize,
        content,
    ))

    return true
}

function getTableLoopNodesAtPosition(editor, target, position, id) {
    const node = editor?.state.doc.nodeAt(position)

    if (target === 'row') {
        return node?.type.name === 'tableRow' && hasTableLoopAttributes(node.attrs)
            ? [{ node, position }]
            : []
    }

    if (
        !['tableCell', 'tableHeader'].includes(node?.type.name)
        || !id
        || node.attrs.twigLoopId !== id
    ) {
        return []
    }

    const row = getTableRow(editor, editor.state.doc.resolve(position + 1))

    return row
        ? getRowCells(row).filter(cell => cell.node.attrs.twigLoopId === id)
        : []
}

function updateTableLoopAtPosition(editor, target, position, id, attributes) {
    const nodes = getTableLoopNodesAtPosition(editor, target, position, id)

    if (!nodes.length) {
        return false
    }

    const transaction = editor.state.tr

    nodes.forEach(entry => transaction.setNodeMarkup(entry.position, undefined, {
        ...entry.node.attrs,
        ...tableLoopNodeAttributes(attributes, target === 'cell' ? id : undefined),
    }))
    editor.view.dispatch(transaction)

    return true
}

function unwrapTableLoopAtPosition(editor, target, position, id) {
    const nodes = getTableLoopNodesAtPosition(editor, target, position, id)

    if (!nodes.length) {
        return false
    }

    const transaction = editor.state.tr

    nodes.forEach(entry => transaction.setNodeMarkup(entry.position, undefined, {
        ...entry.node.attrs,
        ...clearedTableLoopNodeAttributes(target === 'cell'),
    }))
    editor.view.dispatch(transaction)

    return true
}

function getTableConditionRowsAtPosition(editor, position, id = null) {
    if (!editor || !Number.isInteger(position)) {
        return null
    }

    const node = editor.state.doc.nodeAt(position)

    if (node?.type.name !== 'tableRow' || !hasTableConditionAttributes(node.attrs)) {
        return null
    }

    const resolvedPosition = editor.state.doc.resolve(position + 1)
    const table = findAncestorAtPosition(resolvedPosition, ['table'])

    if (!table) {
        return null
    }

    const tableRows = getTableRows(table)
    const rowIndex = tableRows.findIndex(row => row.position === position)
    const conditionId = id ?? node.attrs.twigConditionId

    if (rowIndex === -1 || !conditionId || node.attrs.twigConditionId !== conditionId) {
        return null
    }

    let start = rowIndex
    let end = rowIndex

    while (start > 0 && tableRows[start - 1].node.attrs.twigConditionId === conditionId) {
        start -= 1
    }

    while (end + 1 < tableRows.length && tableRows[end + 1].node.attrs.twigConditionId === conditionId) {
        end += 1
    }

    return {
        table,
        tableRows,
        rows: tableRows.slice(start, end + 1),
    }
}

function updateTableConditionAtPosition(editor, position, id, attributes) {
    const range = getTableConditionRowsAtPosition(editor, position, id)

    if (!range) {
        return false
    }

    const transaction = editor.state.tr

    range.rows.forEach(row => transaction.setNodeMarkup(row.position, undefined, {
        ...row.node.attrs,
        ...tableConditionNodeAttributes(attributes, id),
    }))
    editor.view.dispatch(transaction)

    return true
}

function unwrapTableConditionAtPosition(editor, position, id) {
    const range = getTableConditionRowsAtPosition(editor, position, id)

    if (!range) {
        return false
    }

    const transaction = editor.state.tr

    range.rows.forEach(row => transaction.setNodeMarkup(row.position, undefined, {
        ...row.node.attrs,
        ...clearedTableConditionNodeAttributes(),
    }))
    editor.view.dispatch(transaction)

    return true
}

function deleteTableConditionAtPosition(editor, position, id) {
    const range = getTableConditionRowsAtPosition(editor, position, id)

    if (!range) {
        return false
    }

    const firstRow = range.rows[0]
    const lastRow = range.rows[range.rows.length - 1]
    const deletesWholeTable = range.rows.length === range.tableRows.length
    const from = deletesWholeTable ? range.table.position : firstRow.position
    const to = deletesWholeTable
        ? range.table.position + range.table.node.nodeSize
        : lastRow.position + lastRow.node.nodeSize

    editor.view.dispatch(editor.state.tr.delete(from, to))

    return true
}

function unwrapTwigIfAtPosition(editor, position) {
    const node = editor?.state.doc.nodeAt(position)

    if (node?.type.name !== 'twigIf') {
        return false
    }

    editor.view.dispatch(editor.state.tr.replaceWith(
        position,
        position + node.nodeSize,
        node.firstChild.content,
    ))

    return true
}

function updateTwigIfAtPosition(editor, position, attributes) {
    const node = editor?.state.doc.nodeAt(position)

    if (node?.type.name !== 'twigIf') {
        return false
    }

    const transaction = editor.state.tr.setNodeMarkup(position, undefined, {
        ...node.attrs,
        condition: attributes.condition,
        conditionAst: attributes.conditionAst,
    })
    const hasElse = node.lastChild?.type.name === 'twigElse'

    if (attributes.hasElse && !hasElse) {
        const paragraph = editor.state.schema.nodes.paragraph?.createAndFill()
        const elseType = editor.state.schema.nodes.twigElse

        if (!paragraph || !elseType) {
            return false
        }

        transaction.insert(position + node.nodeSize - 1, elseType.create(null, paragraph))
    } else if (!attributes.hasElse && hasElse) {
        const elsePosition = position + 1 + node.firstChild.nodeSize

        transaction.delete(elsePosition, elsePosition + node.lastChild.nodeSize)
    }

    editor.view.dispatch(transaction)

    return true
}

function runInlineConditionCommandAtPosition(editor, position, command, attributes = null, conditionId = null) {
    const group = editor
        ? findInlineConditionRange(editor.state, position, conditionId ?? attributes?.id)
        : null

    if (!group) {
        return false
    }

    const chain = editor.chain().focus().setTextSelection(group.from)

    return attributes === null
        ? chain[command](group.conditionId).run()
        : chain[command](attributes).run()
}

function dataAttribute(name, defaultValue) {
    return {
        default: defaultValue,
        parseHTML: element => element.getAttribute(name) ?? defaultValue,
        renderHTML: attributes => {
            const key = name.replace('data-twig-', '')
            const value = attributes[key]

            return value ? { [name]: value } : {}
        },
    }
}

function normalizeVariableAttributes(attributes) {
    return {
        expression: attributes.expression,
        filters: attributes.filters ?? [],
    }
}

function normalizeConditionAttributes(attributes) {
    return {
        condition: attributes.condition,
        conditionAst: attributes.conditionAst ?? null,
        conditionId: attributes.conditionId,
        branch: attributes.branch,
    }
}

function inlineConditionBranch(mark) {
    return mark.attrs.branch
}

function findInlineConditionRange(state, position, requestedConditionId = null) {
    if (!state || position < 0 || position > state.doc.content.size) {
        return null
    }

    const resolvedPosition = state.doc.resolve(position)
    const parent = resolvedPosition.parent

    if (!parent.inlineContent) {
        return null
    }

    const children = []
    const parentStart = resolvedPosition.start()

    parent.forEach((node, offset) => {
        children.push({
            from: parentStart + offset,
            node,
            to: parentStart + offset + node.nodeSize,
        })
    })

    const findConditionMark = child => {
        const marks = child.node.marks.filter(mark => mark.type.name === 'twigInlineIf')

        return requestedConditionId
            ? marks.find(mark => mark.attrs.conditionId === requestedConditionId) ?? null
            : marks.at(-1) ?? null
    }
    let targetIndex = children.findIndex(child => child.from === position && findConditionMark(child))

    if (targetIndex < 0) {
        targetIndex = children.findIndex(child => (
            findConditionMark(child)
            && child.from < position
            && position <= child.to
        ))
    }

    if (targetIndex < 0) {
        return null
    }

    const targetMark = findConditionMark(children[targetIndex])
    const conditionId = targetMark.attrs.conditionId

    if (!conditionId || !['then', 'else'].includes(inlineConditionBranch(targetMark))) {
        return null
    }

    const belongsToGroup = child => child.node.marks.some(mark => (
        mark.type.name === 'twigInlineIf'
        && mark.attrs.conditionId === conditionId
    ))
    let startIndex = targetIndex
    let endIndex = targetIndex

    while (startIndex > 0 && belongsToGroup(children[startIndex - 1])) {
        startIndex -= 1
    }

    while (endIndex + 1 < children.length && belongsToGroup(children[endIndex + 1])) {
        endIndex += 1
    }

    const entries = children.slice(startIndex, endIndex + 1).map(child => ({
        ...child,
        mark: child.node.marks.find(mark => (
            mark.type.name === 'twigInlineIf'
            && mark.attrs.conditionId === conditionId
        )),
    }))
    const thenEntry = entries.find(entry => inlineConditionBranch(entry.mark) === 'then')

    return {
        conditionId,
        entries,
        from: entries[0].from,
        hasElse: entries.some(entry => inlineConditionBranch(entry.mark) === 'else'),
        mark: thenEntry?.mark ?? targetMark,
        to: entries.at(-1).to,
    }
}

function updateInlineCondition(state, dispatch, markType, attributes) {
    const group = findInlineConditionRange(state, state.selection.from, attributes.id)

    if (!group) {
        return false
    }

    if (!dispatch) {
        return true
    }

    const conditionId = group.conditionId
    const conditionAttributes = normalizeConditionAttributes({
        ...attributes,
        conditionId,
    })
    const transaction = state.tr

    group.entries.forEach(entry => {
        replaceInlineConditionMark(transaction, entry, markType.create({
            ...conditionAttributes,
            branch: inlineConditionBranch(entry.mark),
        }))
    })

    const shouldHaveElse = attributes.hasElse ?? group.hasElse

    if (shouldHaveElse && !group.hasElse) {
        const elseMark = markType.create({
            ...conditionAttributes,
            branch: 'else',
        })
        const finalEntry = group.entries.at(-1)
        const conditionMarks = finalEntry.node.marks.filter(mark => mark.type.name === 'twigInlineIf')
        const conditionIndex = conditionMarks.findIndex(mark => mark.attrs.conditionId === conditionId)
        const placeholderMarks = [...conditionMarks.slice(0, conditionIndex), elseMark]

        transaction.insert(
            group.to,
            state.schema.text('\u200B', placeholderMarks),
        )
        transaction.setSelection(TextSelection.create(transaction.doc, group.to, group.to + 1))
        transaction.setStoredMarks(placeholderMarks)
    } else if (!shouldHaveElse && group.hasElse) {
        const elseStart = group.entries.find(entry => inlineConditionBranch(entry.mark) === 'else').from

        transaction.delete(elseStart, group.to)
    }

    dispatch(transaction)

    return true
}

function replaceInlineConditionMark(transaction, entry, replacement = null) {
    const marks = entry.node.marks.flatMap(mark => {
        if (mark !== entry.mark) {
            return [mark]
        }

        return replacement ? [replacement] : []
    })

    transaction.replaceWith(entry.from, entry.to, entry.node.mark(marks))
}

function unwrapInlineCondition(state, dispatch, conditionId = null) {
    const group = findInlineConditionRange(state, state.selection.from, conditionId)

    if (!group) {
        return false
    }

    if (dispatch) {
        const elseEntry = group.entries.find(entry => inlineConditionBranch(entry.mark) === 'else')
        const transaction = state.tr

        group.entries
            .filter(entry => inlineConditionBranch(entry.mark) === 'then')
            .forEach(entry => replaceInlineConditionMark(transaction, entry))

        if (elseEntry) {
            transaction.delete(elseEntry.from, group.to)
        }

        dispatch(transaction)
    }

    return true
}

function deleteInlineCondition(state, dispatch, conditionId = null) {
    const group = findInlineConditionRange(state, state.selection.from, conditionId)

    if (!group) {
        return false
    }

    if (dispatch) {
        dispatch(state.tr.delete(group.from, group.to))
    }

    return true
}

function replaceInlineElsePlaceholder(view, from, to, text) {
    if (to - from !== 1 || view.state.doc.textBetween(from, to) !== '\u200B') {
        return false
    }

    const node = view.state.doc.nodeAt(from)
    const mark = node?.marks.find(candidate => (
        candidate.type.name === 'twigInlineIf'
        && inlineConditionBranch(candidate) === 'else'
    ))

    if (!mark) {
        return false
    }

    view.dispatch(view.state.tr.replaceWith(
        from,
        to,
        view.state.schema.text(text, node.marks),
    ))

    return true
}

function refreshInlineConditionPlaceholders(editor) {
    editor.view.dom
        .querySelectorAll('span[data-twig-inline-if][data-twig-inline-branch="else"]')
        .forEach(element => element.toggleAttribute(
            'data-twig-inline-placeholder',
            element.textContent === '\u200B',
        ))
}

function normalizeLoopAttributes(attributes) {
    return {
        item: attributes.item,
        key: attributes.key || null,
        iterable: attributes.iterable,
        transforms: attributes.transforms ?? [],
    }
}

function normalizeTableLoopAttributes(attributes) {
    return {
        item: attributes.twigLoopItem,
        key: attributes.twigLoopKey || null,
        iterable: attributes.twigLoopIterable,
        transforms: attributes.twigLoopTransforms ?? [],
    }
}

function tableLoopAttribute(name, htmlName) {
    return {
        default: null,
        parseHTML: element => element.getAttribute(htmlName) || null,
        renderHTML: attributes => attributes[name]
            ? { [htmlName]: attributes[name] }
            : {},
    }
}

function tableLoopAttributes(includeId = false) {
    return {
        twigLoopItem: tableLoopAttribute('twigLoopItem', 'data-twig-loop-item'),
        twigLoopKey: tableLoopAttribute('twigLoopKey', 'data-twig-loop-key'),
        twigLoopIterable: tableLoopAttribute('twigLoopIterable', 'data-twig-loop-iterable'),
        twigLoopTransforms: {
            default: [],
            parseHTML: element => parseTransforms(element.getAttribute('data-twig-loop-transforms')),
            renderHTML: attributes => {
                const transforms = attributes.twigLoopTransforms ?? []

                return {
                    'data-twig-loop-transforms': JSON.stringify(transforms),
                    'data-twig-loop-transform-summary': formatTransformSummary(transforms),
                }
            },
        },
        ...(includeId
            ? { twigLoopId: tableLoopAttribute('twigLoopId', 'data-twig-loop-id') }
            : {}),
    }
}

function tableConditionAttributes() {
    return {
        twigCondition: tableLoopAttribute('twigCondition', 'data-twig-row-condition'),
        twigConditionId: tableLoopAttribute('twigConditionId', 'data-twig-row-condition-id'),
        twigConditionAst: {
            default: null,
            parseHTML: element => parseConditionAst(element.getAttribute('data-twig-row-condition-ast')),
            renderHTML: attributes => attributes.twigConditionAst
                ? { 'data-twig-row-condition-ast': JSON.stringify(attributes.twigConditionAst) }
                : {},
        },
    }
}

function tableLoopNodeAttributes(attributes, id = undefined) {
    return {
        twigLoopItem: attributes.item,
        twigLoopKey: attributes.key || null,
        twigLoopIterable: attributes.iterable,
        twigLoopTransforms: attributes.transforms ?? [],
        ...(id === undefined ? {} : { twigLoopId: id }),
    }
}

function clearedTableLoopNodeAttributes(includeId = false) {
    return {
        twigLoopItem: null,
        twigLoopKey: null,
        twigLoopIterable: null,
        twigLoopTransforms: [],
        ...(includeId ? { twigLoopId: null } : {}),
    }
}

function hasTableLoopAttributes(attributes) {
    return Boolean(attributes?.twigLoopItem && attributes?.twigLoopIterable)
}

function normalizeTableConditionAttributes(attributes) {
    return {
        condition: attributes.twigCondition,
        conditionAst: attributes.twigConditionAst ?? null,
    }
}

function tableConditionNodeAttributes(attributes, id) {
    return {
        twigCondition: attributes.condition,
        twigConditionAst: attributes.conditionAst ?? null,
        twigConditionId: id,
    }
}

function clearedTableConditionNodeAttributes() {
    return {
        twigCondition: null,
        twigConditionAst: null,
        twigConditionId: null,
    }
}

function hasTableConditionAttributes(attributes) {
    return Boolean(attributes?.twigCondition && attributes?.twigConditionId)
}

function findAncestorAtPosition(resolvedPosition, typeNames) {
    for (let depth = resolvedPosition.depth; depth > 0; depth -= 1) {
        const node = resolvedPosition.node(depth)

        if (typeNames.includes(node.type.name)) {
            return {
                node,
                depth,
                position: resolvedPosition.before(depth),
            }
        }
    }

    return null
}

function getSelectionPosition(editor) {
    const { selection } = editor.state

    return selection.$anchorCell
        ? editor.state.doc.resolve(selection.$anchorCell.pos + 1)
        : selection.$from
}

function synchronizeFloatingToolbar(editor) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')

    if (!shell) {
        return
    }

    const rowConditionRuleCount = getTableRowConditionRuleCount(editor)
    const rowConditionTrigger = shell.querySelector('[data-stylus-twig-panel-trigger="row-condition"]')

    if (rowConditionRuleCount > 0) {
        rowConditionTrigger?.setAttribute(
            'data-twig-row-condition-rule-count',
            String(rowConditionRuleCount),
        )
    } else {
        rowConditionTrigger?.removeAttribute('data-twig-row-condition-rule-count')
    }

    const selectedNodeName = editor.state.selection.node?.type.name

    if (floatingToolbarNodeNames.has(selectedNodeName)) {
        shell.dataset.twigFloatingToolbar = selectedNodeName

        return
    }

    if (editor.isActive('twigInlineIf')) {
        shell.dataset.twigFloatingToolbar = 'twigInlineIf'

        return
    }

    const position = getSelectionPosition(editor)

    for (let depth = position.depth; depth > 0; depth -= 1) {
        const nodeName = position.node(depth).type.name

        if (floatingToolbarNodeNames.has(nodeName)) {
            shell.dataset.twigFloatingToolbar = nodeName

            return
        }
    }

    delete shell.dataset.twigFloatingToolbar
}

function getTableRow(editor, resolvedPosition = getSelectionPosition(editor)) {
    return findAncestorAtPosition(resolvedPosition, ['tableRow'])
}

function getTableRows(table) {
    const rows = []

    table.node.forEach((node, offset, index) => {
        if (node.type.name === 'tableRow') {
            rows.push({
                node,
                depth: table.depth + 1,
                position: table.position + 1 + offset,
                index,
            })
        }
    })

    return rows
}

function getSelectedTableRowRange(editor, expandConditionGroup = false) {
    if (!editor) {
        return null
    }

    const { selection, doc } = editor.state
    let anchorPosition
    let headPosition

    if (selection.$anchorCell) {
        if (typeof selection.isRowSelection !== 'function' || !selection.isRowSelection()) {
            return null
        }

        anchorPosition = doc.resolve(selection.$anchorCell.pos + 1)
        headPosition = doc.resolve(selection.$headCell.pos + 1)
    } else {
        if (!selection.empty) {
            return null
        }

        anchorPosition = selection.$from
        headPosition = selection.$from
    }

    const anchorRow = getTableRow(editor, anchorPosition)
    const headRow = getTableRow(editor, headPosition)
    const anchorTable = findAncestorAtPosition(anchorPosition, ['table'])
    const headTable = findAncestorAtPosition(headPosition, ['table'])

    if (!anchorRow || !headRow || !anchorTable || anchorTable.position !== headTable?.position) {
        return null
    }

    const tableRows = getTableRows(anchorTable)
    let start = tableRows.findIndex(row => row.position === anchorRow.position)
    let end = tableRows.findIndex(row => row.position === headRow.position)

    if (start === -1 || end === -1) {
        return null
    }

    const first = Math.min(start, end)
    const last = Math.max(start, end)
    start = first
    end = last

    if (expandConditionGroup) {
        const id = tableRows[start].node.attrs.twigConditionId

        if (id) {
            while (start > 0 && tableRows[start - 1].node.attrs.twigConditionId === id) {
                start -= 1
            }

            while (end + 1 < tableRows.length && tableRows[end + 1].node.attrs.twigConditionId === id) {
                end += 1
            }
        }
    }

    return {
        table: anchorTable,
        tableRows,
        rows: tableRows.slice(start, end + 1),
    }
}

function getTableCell(editor, resolvedPosition = getSelectionPosition(editor)) {
    return findAncestorAtPosition(resolvedPosition, ['tableCell', 'tableHeader'])
}

function getRowCells(row) {
    const cells = []
    let offset = 0

    row.node.forEach((node, childOffset) => {
        cells.push({
            node,
            depth: row.depth + 1,
            position: row.position + 1 + childOffset,
            index: offset,
        })
        offset += 1
    })

    return cells
}

function getCellAtSelectionPosition(editor, resolvedPosition) {
    const cell = getTableCell(editor, resolvedPosition)
    const row = getTableRow(editor, resolvedPosition)

    if (!cell || !row) {
        return null
    }

    const cells = getRowCells(row)
    const index = cells.findIndex(candidate => candidate.position === cell.position)

    return index === -1 ? null : { cell, row, cells, index }
}

function getSelectedCellRange(editor) {
    if (!editor) {
        return null
    }

    const { selection, doc } = editor.state
    const anchorPosition = selection.$anchorCell
        ? doc.resolve(selection.$anchorCell.pos + 1)
        : selection.$from
    const headPosition = selection.$headCell
        ? doc.resolve(selection.$headCell.pos + 1)
        : anchorPosition
    const anchor = getCellAtSelectionPosition(editor, anchorPosition)
    const head = getCellAtSelectionPosition(editor, headPosition)

    if (!anchor || !head || anchor.row.position !== head.row.position) {
        return null
    }

    let start = Math.min(anchor.index, head.index)
    let end = Math.max(anchor.index, head.index)
    let expanded = true

    while (expanded) {
        expanded = false
        const loopIds = new Set(
            anchor.cells
                .slice(start, end + 1)
                .map(cell => cell.node.attrs.twigLoopId)
                .filter(Boolean),
        )

        anchor.cells.forEach((cell, index) => {
            if (!loopIds.has(cell.node.attrs.twigLoopId)) {
                return
            }

            if (index < start) {
                start = index
                expanded = true
            }

            if (index > end) {
                end = index
                expanded = true
            }
        })
    }

    return {
        row: anchor.row,
        cells: anchor.cells.slice(start, end + 1),
    }
}

function tableContainsMergedCells(editor) {
    const position = getSelectionPosition(editor)
    const table = findAncestorAtPosition(position, ['table'])
    let merged = false

    table?.node.descendants(node => {
        if (
            ['tableCell', 'tableHeader'].includes(node.type.name)
            && ((node.attrs.colspan ?? 1) !== 1 || (node.attrs.rowspan ?? 1) !== 1)
        ) {
            merged = true
        }

        return !merged
    })

    return merged
}

function canConfigureTwigTableRowLoop(editor) {
    const row = editor ? getTableRow(editor) : null
    const conditionRange = row && hasTableConditionAttributes(row.node.attrs)
        ? getTableConditionRowsAtPosition(editor, row.position, row.node.attrs.twigConditionId)
        : null

    return Boolean(
        row
        && !hasTableLoopAttributes(row.node.attrs)
        && (!conditionRange || conditionRange.rows.length === 1)
        && !tableContainsMergedCells(editor),
    )
}

function canApplyTwigInlineIf(state) {
    const { selection } = state

    if (
        selection.empty
        || !selection.$from.sameParent(selection.$to)
        || !selection.$from.parent.inlineContent
    ) {
        return false
    }

    let conditionStack = null
    let hasInlineContent = false
    let uniformConditionStack = true

    state.doc.nodesBetween(selection.from, selection.to, node => {
        if (!node.isInline) {
            return
        }

        const currentStack = JSON.stringify(
            node.marks
                .filter(mark => mark.type.name === 'twigInlineIf')
                .map(mark => mark.attrs),
        )

        conditionStack ??= currentStack
        hasInlineContent = true
        uniformConditionStack &&= conditionStack === currentStack
    })

    return hasInlineContent && uniformConditionStack
}

function canInsertTwigInlineIf(editor) {
    if (!editor) {
        return false
    }

    return canApplyTwigInlineIf(editor.state)
}

function canInsertTwigTableRowIf(editor) {
    const range = getSelectedTableRowRange(editor)

    return Boolean(
        range
        && range.rows.length > 0
        && (range.rows.length === 1 || range.rows.every(row => !hasTableLoopAttributes(row.node.attrs)))
        && range.rows.every(row => !hasTableConditionAttributes(row.node.attrs)),
    )
}

function hasTwigTableRowLoop(editor) {
    return hasTableLoopAttributes(getTableRow(editor)?.node.attrs)
}

function canConfigureTwigTableCellLoop(editor) {
    const range = editor ? getSelectedCellRange(editor) : null

    return Boolean(
        range
        && !tableContainsMergedCells(editor)
        && range.cells.every(cell => ['tableCell', 'tableHeader'].includes(cell.node.type.name))
        && range.cells.every(cell => !hasTableLoopAttributes(cell.node.attrs)),
    )
}

function hasTwigTableCellLoop(editor) {
    const range = editor ? getSelectedCellRange(editor) : null

    return Boolean(range?.cells.some(cell => hasTableLoopAttributes(cell.node.attrs)))
}

function createTableLoopId() {
    return window.crypto?.randomUUID?.()
        ?? `twig-loop-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function createTableConditionId() {
    return window.crypto?.randomUUID?.()
        ?? `twig-condition-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function createInlineConditionId() {
    return window.crypto?.randomUUID?.()
        ?? `twig-inline-condition-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function collectLoopStack(editor, beforeDepth = Number.POSITIVE_INFINITY) {
    if (!editor) {
        return []
    }

    return collectLoopStackAtPosition(getSelectionPosition(editor), beforeDepth)
}

function collectLoopStackAtPosition(position, beforeDepth = Number.POSITIVE_INFINITY) {
    const stack = []

    for (let depth = 1; depth <= position.depth && depth < beforeDepth; depth += 1) {
        const node = position.node(depth)

        if (node.type.name === 'twigFor') {
            stack.push(normalizeLoopAttributes(node.attrs))
        } else if (
            ['tableRow', 'tableCell', 'tableHeader'].includes(node.type.name)
            && hasTableLoopAttributes(node.attrs)
        ) {
            stack.push(normalizeTableLoopAttributes(node.attrs))
        }
    }

    return stack
}

function getTableLoopArguments(editor, target) {
    const range = target === 'cell' ? getSelectedCellRange(editor) : null
    const selected = target === 'row'
        ? getTableRow(editor)
        : range?.cells.find(cell => hasTableLoopAttributes(cell.node.attrs))
            ?? getTableCell(editor)
    const attributes = selected?.node.attrs ?? {}
    const existing = hasTableLoopAttributes(attributes)

    if (selected && existing) {
        return {
            ...getTableLoopSettingsPanelState(editor, selected.position, selected.node, target),
            existing: true,
        }
    }

    return {
        ...normalizeTableLoopAttributes(attributes),
        editorSelection: editor?.state.selection.toJSON() ?? null,
        existing: false,
        loopStack: selected ? collectLoopStack(editor, selected.depth) : [],
        position: selected?.position ?? null,
        target,
    }
}

function hasTwigTableRowCondition(editor) {
    return hasTableConditionAttributes(getTableRow(editor)?.node.attrs)
}

function getTableRowConditionRuleCount(editor) {
    const row = editor ? getTableRow(editor) : null

    if (!row || !hasTableConditionAttributes(row.node.attrs)) {
        return 0
    }

    const clauses = row.node.attrs.twigConditionAst?.children

    return Array.isArray(clauses) ? clauses.length : 1
}

function openTwigTableRowCondition(editor) {
    const row = editor ? getTableRow(editor) : null

    return row && hasTableConditionAttributes(row.node.attrs)
        ? openTableConditionSettingsPanel(editor, row.position, row.node)
        : false
}

function parseFilters(value) {
    if (value === null) {
        return []
    }

    const filters = JSON.parse(value)

    if (!Array.isArray(filters)) {
        throw new TypeError('Twig variable filters must be an array.')
    }

    return filters
}

function parseTransforms(value) {
    if (value === null) {
        return []
    }

    const transforms = JSON.parse(value)

    if (!Array.isArray(transforms)) {
        throw new TypeError('Twig collection transforms must be an array.')
    }

    return transforms
}

function parseConditionAst(value) {
    if (!value) {
        return null
    }

    const conditionAst = JSON.parse(value)

    return conditionAst && typeof conditionAst === 'object' && !Array.isArray(conditionAst)
        ? conditionAst
        : null
}

function formatVariable(attributes) {
    const filters = (attributes.filters ?? []).map(filter => {
        const argumentsList = filter.arguments?.length
            ? `(${filter.arguments.join(', ')})`
            : ''

        return `|${filter.name}${argumentsList}`
    }).join('')

    return `{{ ${attributes.expression}${filters} }}`
}

function formatFilterSummary(filters = []) {
    if (filters.length === 0) {
        return null
    }

    const remainingCount = filters.length - 1

    return `|${filters[0].name}${remainingCount > 0 ? ` +${remainingCount}` : ''}`
}

function formatTransformSummary(transforms = []) {
    return transforms.map(transform => `|${transform.name}`).join('')
}

function formatApplySummary(filters = []) {
    return filters.map(filter => {
        const argumentsList = filter.arguments?.length
            ? `(${filter.arguments.join(', ')})`
            : ''

        return `${filter.name}${argumentsList}`
    }).join('|')
}

function getLoopStack(editor) {
    return collectLoopStack(editor)
}

function flattenVariable(variable, name = variable.name, group = variable.group) {
    const rebased = { ...variable, name, group }
    const variables = [rebased]

    for (const property of variable.properties) {
        variables.push(...flattenVariable(
            property,
            `${name}.${property.name}`,
            property.group ?? group,
        ))
    }

    return variables
}

function replaceBinding(scope, name, variables) {
    for (const variableName of [...scope.keys()]) {
        if (variableName === name || variableName.startsWith(`${name}.`)) {
            scope.delete(variableName)
        }
    }

    for (const variable of variables) {
        scope.set(variable.name, variable)
    }
}

function makeLoopVariable(labels, parentLoop = null) {
    const properties = [
        { name: 'index', type: 'integer', label: labels.index },
        { name: 'index0', type: 'integer', label: labels.index0 },
        { name: 'revindex', type: 'integer', label: labels.revindex },
        { name: 'revindex0', type: 'integer', label: labels.revindex0 },
        { name: 'first', type: 'boolean', label: labels.first },
        { name: 'last', type: 'boolean', label: labels.last },
        { name: 'length', type: 'integer', label: labels.length },
    ].map(property => ({
        ...property,
        group: null,
        properties: [],
        item: null,
        keyType: 'mixed',
        sample: null,
    }))

    if (parentLoop) {
        properties.push({
            name: 'parent',
            type: 'context',
            label: labels.parent,
            group: null,
            properties: [parentLoop],
            item: null,
            keyType: 'mixed',
            sample: null,
        })
    }

    return {
        name: 'loop',
        type: 'loop',
        label: labels.loop,
        group: null,
        item: null,
        keyType: 'mixed',
        sample: null,
        properties,
    }
}

function resolveVariableGroups(definitions, loopStack, scopeLabel, loopLabels) {
    const scope = new Map()

    for (const definition of definitions) {
        for (const variable of flattenVariable(definition)) {
            scope.set(variable.name, variable)
        }
    }

    for (const loop of loopStack) {
        const iterable = scope.get(loop.iterable)
        const parentLoop = scope.get('loop') ?? null
        const group = scopeLabel.replace('{variable}', loop.item)
        const item = iterable?.item ?? {
            name: loop.item,
            type: 'mixed',
            label: loop.item,
            group: null,
            properties: [],
            item: null,
            keyType: 'mixed',
            sample: null,
        }

        replaceBinding(scope, loop.item, flattenVariable(item, loop.item, group))

        if (loop.key) {
            replaceBinding(scope, loop.key, [{
                name: loop.key,
                type: iterable?.keyType ?? 'mixed',
                label: loop.key,
                group,
                properties: [],
                item: null,
                keyType: 'mixed',
                sample: null,
            }])
        }

        replaceBinding(
            scope,
            'loop',
            flattenVariable(makeLoopVariable(loopLabels, parentLoop), 'loop', group),
        )
    }

    const groups = new Map()

    for (const variable of scope.values()) {
        const group = variable.group ?? ''

        if (!groups.has(group)) {
            groups.set(group, [])
        }

        groups.get(group).push(variable)
    }

    return [...groups].map(([label, variables]) => ({ label, variables }))
}

function synchronizeVariableScope(editor, refreshChips = false) {
    const editorElement = editor?.view?.dom
    const shell = editorElement?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null

    if (!data) {
        return
    }

    const groups = resolveVariableGroups(
        data.twigVariableDefinitions,
        getLoopStack(editor),
        data.twigScopeLabel,
        data.twigLoopLabels,
    )

    data.twigVariableGroups = groups

    if (refreshChips) {
        refreshVariableChips(
            editor,
            data.twigVariableDefinitions,
            data.twigScopeLabel,
            data.twigLoopLabels,
            data.twigShowSampleValues,
        )
    }
}

function refreshEditorVariableChips(editor) {
    const editorElement = editor?.view?.dom
    const shell = editorElement?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null

    if (!data) {
        return
    }

    refreshVariableChips(
        editor,
        data.twigVariableDefinitions,
        data.twigScopeLabel,
        data.twigLoopLabels,
        data.twigShowSampleValues,
    )
}

function getVariableFilterPanelState(editor, position, node, data) {
    const loopStack = collectLoopStackAtPosition(editor.state.doc.resolve(position))
    const variable = resolveVariableGroups(
        data.twigVariableDefinitions,
        loopStack,
        data.twigScopeLabel,
        data.twigLoopLabels,
    )
        .flatMap(group => group.variables)
        .find(candidate => candidate.name === node.attrs.expression)

    if (!variable) {
        return null
    }

    return {
        editorSelection: { type: 'node', anchor: position },
        expression: node.attrs.expression,
        filters: node.attrs.filters ?? [],
        label: variable.label ?? variable.name,
        loopStack,
        type: variable.type,
    }
}

function openVariableFilterPanel(editor, position, node) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const panelState = data && node.type.name === 'twigVariable'
        ? getVariableFilterPanelState(editor, position, node, data)
        : null

    if (!panelState) {
        return false
    }

    editor.view.dispatch(editor.state.tr.setSelection(NodeSelection.create(editor.state.doc, position)))
    editor.view.focus()
    data.twigOpenVariableFilterPanel(panelState)

    return true
}

function synchronizeVariableFilterPanel(editor) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const position = data?.twigVariableFilterPosition
    const node = Number.isInteger(position) ? editor?.state?.doc?.nodeAt(position) : null

    if (
        !data?.twigVariableFilterPanelOpen
        || data.twigFilterPanelTarget !== 'variable'
        || node?.type.name !== 'twigVariable'
    ) {
        return
    }

    const panelState = getVariableFilterPanelState(editor, position, node, data)

    if (panelState) {
        data.twigOpenVariableFilterPanel(panelState)
    }
}

function getLoopSettingsPanelState(editor, position, node) {
    return {
        editorSelection: { type: 'node', anchor: position },
        hasElse: node.lastChild?.type.name === 'twigForElse',
        id: null,
        item: node.attrs.item,
        key: node.attrs.key,
        iterable: node.attrs.iterable,
        loopStack: collectLoopStackAtPosition(editor.state.doc.resolve(position)),
        position,
        target: 'block',
        transforms: node.attrs.transforms ?? [],
    }
}

function getTableLoopSettingsPanelState(editor, position, node, target) {
    const resolvedPosition = editor.state.doc.resolve(position + 1)
    const ancestor = target === 'row'
        ? getTableRow(editor, resolvedPosition)
        : getTableCell(editor, resolvedPosition)

    if (!ancestor || !hasTableLoopAttributes(node.attrs)) {
        return null
    }

    const id = target === 'cell' ? node.attrs.twigLoopId : null
    const cells = target === 'cell'
        ? getRowCells(getTableRow(editor, resolvedPosition))
            .filter(cell => cell.node.attrs.twigLoopId === id)
        : []
    const editorSelection = target === 'cell' && cells.length
        ? {
            type: 'cell',
            anchor: cells[0].position,
            head: cells[cells.length - 1].position,
        }
        : TextSelection.near(editor.state.doc.resolve(position + 1)).toJSON()

    return {
        ...normalizeTableLoopAttributes(node.attrs),
        editorSelection,
        hasElse: false,
        id,
        loopStack: collectLoopStackAtPosition(resolvedPosition, ancestor.depth),
        position: ancestor.position,
        target,
    }
}

function openLoopSettingsPanel(editor, position, node, event) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const element = editor?.view?.nodeDOM(position)

    if (!data || node.type.name !== 'twigFor' || event.target !== element) {
        return false
    }

    editor.view.dispatch(editor.state.tr.setSelection(NodeSelection.create(editor.state.doc, position)))
    editor.view.focus()
    data.twigOpenLoopSettingsPanel(getLoopSettingsPanelState(editor, position, node))

    return true
}

function openTableLoopSettingsPanel(editor, position, node, target, selectionPosition = position + 1) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const panelState = data
        ? getTableLoopSettingsPanelState(editor, position, node, target)
        : null

    if (!panelState) {
        return false
    }

    editor.view.dispatch(editor.state.tr.setSelection(
        TextSelection.near(editor.state.doc.resolve(selectionPosition)),
    ))
    editor.view.focus()
    data.twigOpenLoopSettingsPanel(panelState)

    return true
}

function synchronizeLoopSettingsPanel(editor) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null

    if (
        data?.twigVariableFilterPanelOpen
        && data.twigFilterPanelTarget === 'loop'
        && data.twigLoopInsertionMode
    ) {
        const loop = getTableLoopArguments(editor, data.twigLoopTarget)

        if (loop.existing) {
            data.twigOpenLoopSettingsPanel(loop)
        }

        return
    }

    const position = data?.twigLoopPosition
    const node = Number.isInteger(position) ? editor?.state?.doc?.nodeAt(position) : null

    if (!data?.twigVariableFilterPanelOpen || data.twigFilterPanelTarget !== 'loop') {
        return
    }

    if (data.twigLoopTarget === 'block') {
        if (node?.type.name !== 'twigFor') {
            data.twigVariableFilterPanelOpen = false

            return
        }

        data.twigOpenLoopSettingsPanel(getLoopSettingsPanelState(editor, position, node))

        return
    }

    const expectedNodeNames = data.twigLoopTarget === 'row'
        ? ['tableRow']
        : ['tableCell', 'tableHeader']

    if (
        !expectedNodeNames.includes(node?.type.name)
        || !hasTableLoopAttributes(node?.attrs)
        || (data.twigLoopTarget === 'cell' && node.attrs.twigLoopId !== data.twigLoopId)
    ) {
        data.twigVariableFilterPanelOpen = false

        return
    }

    const panelState = getTableLoopSettingsPanelState(
        editor,
        position,
        node,
        data.twigLoopTarget,
    )

    if (panelState) {
        data.twigOpenLoopSettingsPanel(panelState)
    }
}

function getApplySettingsPanelState(position, node) {
    return {
        editorSelection: { type: 'node', anchor: position },
        filters: node.attrs.filters ?? [],
    }
}

function openApplySettingsPanel(editor, position, node, event) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const element = editor?.view?.nodeDOM(position)

    if (!data || node.type.name !== 'twigApply' || event.target !== element) {
        return false
    }

    editor.view.dispatch(editor.state.tr.setSelection(NodeSelection.create(editor.state.doc, position)))
    editor.view.focus()
    data.twigOpenApplySettingsPanel(getApplySettingsPanelState(position, node))

    return true
}

function synchronizeApplySettingsPanel(editor) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null

    if (
        data?.twigVariableFilterPanelOpen
        && data.twigFilterPanelTarget === 'apply-insert'
    ) {
        const selected = findActiveNode(editor.state, 'twigApply')

        if (selected) {
            data.twigOpenApplySettingsPanel(
                getApplySettingsPanelState(selected.position, selected.node),
            )
        }

        return
    }

    const position = data?.twigApplyPosition
    const node = Number.isInteger(position) ? editor?.state?.doc?.nodeAt(position) : null

    if (!data?.twigVariableFilterPanelOpen || data.twigFilterPanelTarget !== 'apply') {
        return
    }

    if (node?.type.name !== 'twigApply') {
        data.twigVariableFilterPanelOpen = false

        return
    }

    data.twigOpenApplySettingsPanel(getApplySettingsPanelState(position, node))
}

function getConditionSettingsPanelState(editor, position, attributes, inline, hasElse = false) {
    return {
        condition: attributes.condition,
        conditionAst: attributes.conditionAst,
        editorSelection: inline
            ? { type: 'text', anchor: position, head: position }
            : { type: 'node', anchor: position },
        hasElse,
        id: inline ? (attributes.conditionId ?? null) : null,
        inline,
        target: inline ? 'inline' : 'block',
        loopStack: collectLoopStackAtPosition(editor.state.doc.resolve(position)),
        position,
    }
}

function getTableConditionSettingsPanelState(editor, position, node) {
    const range = getTableConditionRowsAtPosition(editor, position, node.attrs.twigConditionId)

    if (!range) {
        return null
    }

    return {
        ...normalizeTableConditionAttributes(node.attrs),
        editorSelection: TextSelection.near(editor.state.doc.resolve(position + 1)).toJSON(),
        hasElse: false,
        id: node.attrs.twigConditionId,
        inline: false,
        loopStack: collectLoopStackAtPosition(editor.state.doc.resolve(position + 1)),
        position,
        target: 'row',
    }
}

function openBlockConditionSettingsPanel(editor, position, node, event) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const element = editor?.view?.nodeDOM(position)

    if (!data || node.type.name !== 'twigIf' || event.target !== element) {
        return false
    }

    editor.view.dispatch(editor.state.tr.setSelection(NodeSelection.create(editor.state.doc, position)))
    editor.view.focus()
    data.twigOpenConditionSettingsPanel(getConditionSettingsPanelState(
        editor,
        position,
        node.attrs,
        false,
        node.lastChild?.type.name === 'twigElse',
    ))

    return true
}

function openInlineConditionSettingsPanel(editor, position, event) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const conditionId = event?.target instanceof Element
        ? event.target.closest('span[data-twig-inline-if]')?.dataset.twigInlineConditionId
        : null
    const group = editor
        ? findInlineConditionRange(editor.state, position, conditionId)
        : null

    if (!data || !group) {
        return false
    }

    data.twigOpenConditionSettingsPanel(getConditionSettingsPanelState(
        editor,
        group.from,
        group.mark.attrs,
        true,
        group.hasElse,
    ))

    const emptyElseEntry = group.entries.find(entry => (
        inlineConditionBranch(entry.mark) === 'else'
        && entry.node.text === '\u200B'
        && entry.from <= position
        && position <= entry.to
    ))

    if (!emptyElseEntry) {
        return false
    }

    editor.view.dispatch(editor.state.tr.setSelection(TextSelection.create(
        editor.state.doc,
        emptyElseEntry.from,
        emptyElseEntry.to,
    )))
    editor.view.focus()

    return true
}

function openTableConditionSettingsPanel(editor, position, node, selectionPosition = position + 1) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const panelState = data
        ? getTableConditionSettingsPanelState(editor, position, node)
        : null

    if (!panelState) {
        return false
    }

    editor.view.dispatch(editor.state.tr.setSelection(
        TextSelection.near(editor.state.doc.resolve(selectionPosition)),
    ))
    editor.view.focus()
    data.twigOpenConditionSettingsPanel(panelState)

    return true
}

function synchronizeConditionSettingsPanel(editor) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null
    const position = data?.twigConditionPosition

    if (!data?.twigConditionPanelOpen || !Number.isInteger(position)) {
        return
    }

    if (data.twigConditionTarget === 'inline') {
        const group = findInlineConditionRange(editor.state, position, data.twigConditionId)

        if (!group) {
            data.twigConditionPanelOpen = false

            return
        }

        data.twigOpenConditionSettingsPanel(getConditionSettingsPanelState(
            editor,
            group.from,
            group.mark.attrs,
            true,
            group.hasElse,
        ))

        return
    }

    if (data.twigConditionTarget === 'row') {
        const node = editor.state.doc.nodeAt(position)
        const panelState = node?.type.name === 'tableRow'
            ? getTableConditionSettingsPanelState(editor, position, node)
            : null

        if (!panelState || panelState.id !== data.twigConditionId) {
            data.twigConditionPanelOpen = false

            return
        }

        data.twigOpenConditionSettingsPanel(panelState)

        return
    }

    const node = editor.state.doc.nodeAt(position)

    if (node?.type.name !== 'twigIf') {
        data.twigConditionPanelOpen = false

        return
    }

    data.twigOpenConditionSettingsPanel(getConditionSettingsPanelState(
        editor,
        position,
        node.attrs,
        false,
        node.lastChild?.type.name === 'twigElse',
    ))
}

function refreshVariableChips(editor, definitions, scopeLabel, loopLabels, showSampleValues) {
    editor.state.doc.descendants((node, position) => {
        if (node.type.name !== 'twigVariable') {
            return
        }

        const groups = resolveVariableGroups(
            definitions,
            collectLoopStackAtPosition(editor.state.doc.resolve(position)),
            scopeLabel,
            loopLabels,
        )
        const variable = groups
            .flatMap(group => group.variables)
            .find(candidate => candidate.name === node.attrs.expression)
        const element = editor.view.nodeDOM(position)

        if (!variable || !(element instanceof HTMLElement)) {
            return
        }

        const twigExpression = formatVariable(node.attrs)
        const label = showSampleValues && variable.sample !== null
            ? variable.sample
            : variable.label ?? variable.name

        element.textContent = label
        element.setAttribute('title', twigExpression)
        element.setAttribute('aria-label', `${label}: ${twigExpression}`)

        const filterSummary = formatFilterSummary(node.attrs.filters)

        if (filterSummary) {
            element.setAttribute('data-twig-filter-summary', filterSummary)
        } else {
            element.removeAttribute('data-twig-filter-summary')
        }
    })
}

function getEditorFromRoot(root) {
    const editorElement = root.querySelector('[x-data^="richEditorFormComponent"]')

    return editorElement
        ? window.Alpine?.$data(editorElement)?.getEditor?.()
        : null
}

function getOutlineDepth(document, position) {
    const resolvedPosition = document.resolve(position)
    let depth = 0

    for (let index = 1; index <= resolvedPosition.depth; index += 1) {
        const node = resolvedPosition.node(index)

        if (
            ['twigIf', 'twigFor'].includes(node.type.name)
            || (
                ['tableRow', 'tableCell', 'tableHeader'].includes(node.type.name)
                && hasTableLoopAttributes(node.attrs)
            )
        ) {
            depth += 1
        }
    }

    return depth
}

function formatLoopLabel(attributes) {
    const variables = attributes.key
        ? `${attributes.key}, ${attributes.item}`
        : attributes.item

    return `${variables} in ${attributes.iterable}${formatTransformSummary(attributes.transforms)}`
}

function createTableRowStructureChips(conditionSummary, loopSummary) {
    const container = window.document.createElement('span')

    container.className = 'fi-stylus-twig-row-structure-chips fi-not-prose'
    container.contentEditable = 'false'
    container.setAttribute('aria-hidden', 'true')

    const addChip = (type, label, summary) => {
        if (!summary) {
            return
        }

        const chip = window.document.createElement('span')

        chip.className = `fi-stylus-twig-row-structure-chip fi-stylus-twig-row-structure-chip--${type}`
        chip.dataset.twigRowStructureLabel = label
        chip.dataset.twigRowStructureSummary = summary
        container.append(chip)
    }

    addChip('loop', 'FOR', loopSummary)
    addChip('condition', 'IF', conditionSummary)

    return container
}

function createTableRowStructureDecorations(documentNode) {
    const decorations = []
    const conditionIds = new Set()

    documentNode.descendants((node, position) => {
        if (node.type.name !== 'tableRow' || !node.firstChild) {
            return
        }

        const hasLoop = hasTableLoopAttributes(node.attrs)
        const conditionId = node.attrs.twigConditionId
        const hasCondition = hasTableConditionAttributes(node.attrs)
            && (!conditionId || !conditionIds.has(conditionId))

        if (hasTableConditionAttributes(node.attrs) && conditionId) {
            conditionIds.add(conditionId)
        }

        if (!hasLoop && !hasCondition) {
            return
        }

        const structure = hasLoop && hasCondition
            ? 'condition-loop'
            : hasCondition ? 'condition' : 'loop'
        const conditionSummary = hasCondition ? `IF · ${node.attrs.twigCondition}` : null
        const loopSummary = hasLoop
            ? `FOR · ${formatLoopLabel(normalizeTableLoopAttributes(node.attrs)).replace(' in ', ' IN ')}`
            : null
        const start = position + 1

        decorations.push(Decoration.node(start, start + node.firstChild.nodeSize, {
            'data-twig-row-structure': structure,
        }))
        decorations.push(Decoration.widget(
            start + 1,
            () => createTableRowStructureChips(conditionSummary, loopSummary),
            { side: -1 },
        ))
    })

    return DecorationSet.create(documentNode, decorations)
}

function collectOutlineItems(editor) {
    const items = []
    const cellLoopIds = new Set()
    const inlineConditionIds = new Set()
    const rowConditionIds = new Set()

    editor.state.doc.descendants((node, position) => {
        const depth = getOutlineDepth(editor.state.doc, position)
        const inlineConditions = node.isInline
            ? node.marks.filter(mark => mark.type.name === 'twigInlineIf')
            : []

        inlineConditions.forEach((inlineCondition, conditionDepth) => {
            const conditionId = inlineCondition.attrs.conditionId

            if (conditionId && !inlineConditionIds.has(conditionId)) {
                items.push({
                    key: `inline-condition-${conditionId}`,
                    type: 'condition',
                    label: inlineCondition.attrs.condition,
                    position,
                    depth: depth + conditionDepth,
                })
            }

            if (conditionId) {
                inlineConditionIds.add(conditionId)
            }
        })

        if (
            node.type.name === 'tableRow'
            && hasTableConditionAttributes(node.attrs)
            && !rowConditionIds.has(node.attrs.twigConditionId)
        ) {
            rowConditionIds.add(node.attrs.twigConditionId)
            items.push({
                key: `row-condition-${position}`,
                type: 'rowCondition',
                label: node.attrs.twigCondition,
                position,
                depth,
            })
        }

        if (node.type.name === 'twigIf') {
            items.push({
                key: `condition-${position}`,
                type: 'condition',
                label: node.attrs.condition,
                position,
                depth,
            })
        } else if (node.type.name === 'twigFor') {
            items.push({
                key: `loop-${position}`,
                type: 'loop',
                label: formatLoopLabel(normalizeLoopAttributes(node.attrs)),
                position,
                depth,
            })
        } else if (node.type.name === 'tableRow' && hasTableLoopAttributes(node.attrs)) {
            items.push({
                key: `row-loop-${position}`,
                type: 'rowLoop',
                label: formatLoopLabel(normalizeTableLoopAttributes(node.attrs)),
                position,
                depth,
            })
        } else if (
            ['tableCell', 'tableHeader'].includes(node.type.name)
            && hasTableLoopAttributes(node.attrs)
            && !cellLoopIds.has(node.attrs.twigLoopId)
        ) {
            cellLoopIds.add(node.attrs.twigLoopId)
            items.push({
                key: `cell-loop-${position}`,
                type: 'cellLoop',
                label: formatLoopLabel(normalizeTableLoopAttributes(node.attrs)),
                position,
                depth,
            })
        } else if (node.type.name === 'twigVariable') {
            items.push({
                key: `variable-${position}`,
                type: 'variable',
                label: formatVariable(node.attrs),
                position,
                depth,
            })
        }
    })

    return items
}

function synchronizeOutline(editor) {
    const shell = editor?.view?.dom?.closest('.fi-stylus-twig-editor-shell')
    const data = shell ? window.Alpine?.$data(shell) : null

    if (data) {
        data.twigOutlineItems = collectOutlineItems(editor)
    }
}

window.PHPinnacleStylusTwigEditor = {
    ...window.PHPinnacleStylusTwigEditor,
    getLoopStack,
    canInsertTwigInlineIf,
    canInsertTwigTableRowIf,
    canConfigureTwigTableRowLoop,
    hasTwigTableRowLoop,
    hasTwigTableRowCondition,
    canConfigureTwigTableCellLoop,
    hasTwigTableCellLoop,
    getTableRowLoopArguments: editor => getTableLoopArguments(editor, 'row'),
    getTableCellLoopArguments: editor => getTableLoopArguments(editor, 'cell'),
    getTableRowConditionRuleCount,
    openTwigTableRowCondition,
    focusOutlineItem(root, item) {
        const editor = getEditorFromRoot(root)
        const node = editor?.state.doc.nodeAt(item.position)

        if (!editor || !node) {
            return false
        }

        const selection = ['twigVariable', 'twigIf', 'twigFor'].includes(node.type.name)
            ? NodeSelection.create(editor.state.doc, item.position)
            : TextSelection.near(editor.state.doc.resolve(item.position + 1))

        editor.view.dispatch(editor.state.tr.setSelection(selection).scrollIntoView())
        editor.view.focus()

        if (item.type === 'rowLoop') {
            openTableLoopSettingsPanel(editor, item.position, node, 'row')
        } else if (item.type === 'cellLoop') {
            openTableLoopSettingsPanel(editor, item.position, node, 'cell')
        } else if (item.type === 'rowCondition') {
            openTableConditionSettingsPanel(editor, item.position, node)
        }

        return true
    },
    refreshVariableChips(root) {
        const editor = getEditorFromRoot(root)

        if (!editor) {
            return false
        }

        refreshEditorVariableChips(editor)

        return true
    },
    updateVariableFilters(root, position, expression, filters) {
        const editor = getEditorFromRoot(root)

        if (!editor) {
            return false
        }

        return editor.commands.updateTwigVariableAt(position, { expression, filters })
    },
    updateApplyFilters(root, position, filters) {
        return updateTwigApplyAtPosition(getEditorFromRoot(root), position, filters)
    },
    updateLoopSettings(root, position, settings) {
        const editor = getEditorFromRoot(root)

        if (!editor) {
            return false
        }

        return editor.commands.updateTwigForAt(position, settings)
    },
    updateTableLoopSettings(root, target, position, id, settings) {
        return updateTableLoopAtPosition(
            getEditorFromRoot(root),
            target,
            position,
            id,
            settings,
        )
    },
    updateCondition(root, position, settings) {
        const editor = getEditorFromRoot(root)

        if (settings.target === 'row') {
            return updateTableConditionAtPosition(
                editor,
                position,
                settings.id,
                settings,
            )
        }

        if (settings.inline) {
            return runInlineConditionCommandAtPosition(
                editor,
                position,
                'updateTwigInlineIf',
                settings,
            )
        }

        return updateTwigIfAtPosition(editor, position, settings)
    },
    deleteVariable(root, position) {
        return deleteNodeAtPosition(getEditorFromRoot(root), position, 'twigVariable')
    },
    keepApplyContent(root, position) {
        return unwrapTwigApplyAtPosition(getEditorFromRoot(root), position)
    },
    deleteApply(root, position) {
        return deleteNodeAtPosition(getEditorFromRoot(root), position, 'twigApply')
    },
    keepLoopContent(root, position) {
        return unwrapTwigForAtPosition(getEditorFromRoot(root), position)
    },
    keepTableLoopContent(root, target, position, id) {
        return unwrapTableLoopAtPosition(getEditorFromRoot(root), target, position, id)
    },
    deleteLoop(root, position) {
        return deleteNodeAtPosition(getEditorFromRoot(root), position, 'twigFor')
    },
    keepConditionContent(root, position, inline, target = null, id = null) {
        const editor = getEditorFromRoot(root)

        if (target === 'row') {
            return unwrapTableConditionAtPosition(editor, position, id)
        }

        return inline
            ? runInlineConditionCommandAtPosition(editor, position, 'unwrapTwigInlineIf', null, id)
            : unwrapTwigIfAtPosition(editor, position)
    },
    deleteCondition(root, position, inline, target = null, id = null) {
        const editor = getEditorFromRoot(root)

        if (target === 'row') {
            return deleteTableConditionAtPosition(editor, position, id)
        }

        return inline
            ? runInlineConditionCommandAtPosition(editor, position, 'deleteTwigInlineIf', null, id)
            : deleteNodeAtPosition(editor, position, 'twigIf')
    },
    insertVariable(root, variable) {
        const editor = getEditorFromRoot(root)

        if (!editor) {
            return false
        }

        if (variable.item) {
            return editor
                .chain()
                .focus()
                .insertTwigFor({
                    item: 'item',
                    key: null,
                    iterable: variable.name,
                })
                .run()
        }

        return editor
            .chain()
            .focus()
            .insertTwigVariable({ expression: variable.name, filters: [] })
            .run()
    },
    insertSnippet(root, snippet) {
        const editor = getEditorFromRoot(root)

        if (!editor) {
            return false
        }

        return editor
            .chain()
            .focus()
            .insertTwigSnippet({ content: snippet.content })
            .run()
    },
}

export default Extension.create({
    name: 'twigTemplateKit',

    addGlobalAttributes() {
        return [
            {
                types: ['tableRow'],
                attributes: {
                    ...tableLoopAttributes(),
                    ...tableConditionAttributes(),
                },
            },
            {
                types: ['tableCell', 'tableHeader'],
                attributes: tableLoopAttributes(true),
            },
        ]
    },

    addCommands() {
        return {
            insertTwigSnippet:
                attributes =>
                ({ commands }) => commands.insertContent(attributes.content),
            setTwigTableRowLoop:
                attributes =>
                ({ state, dispatch }) => {
                    const row = getTableRow(this.editor)
                    const conditionRange = row && hasTableConditionAttributes(row.node.attrs)
                        ? getTableConditionRowsAtPosition(
                            this.editor,
                            row.position,
                            row.node.attrs.twigConditionId,
                        )
                        : null

                    if (
                        !row
                        || tableContainsMergedCells(this.editor)
                        || (conditionRange && conditionRange.rows.length > 1)
                    ) {
                        return false
                    }

                    if (dispatch) {
                        dispatch(state.tr.setNodeMarkup(row.position, undefined, {
                            ...row.node.attrs,
                            ...tableLoopNodeAttributes(attributes),
                        }))
                    }

                    return true
                },
            removeTwigTableRowLoop:
                () =>
                ({ state, dispatch }) => {
                    const row = getTableRow(this.editor)

                    if (!row || !hasTableLoopAttributes(row.node.attrs)) {
                        return false
                    }

                    if (dispatch) {
                        dispatch(state.tr.setNodeMarkup(row.position, undefined, {
                            ...row.node.attrs,
                            ...clearedTableLoopNodeAttributes(),
                        }))
                    }

                    return true
                },
            setTwigTableCellLoop:
                attributes =>
                ({ state, dispatch }) => {
                    const range = getSelectedCellRange(this.editor)

                    if (!range || tableContainsMergedCells(this.editor)) {
                        return false
                    }

                    if (dispatch) {
                        const existingId = range.cells
                            .map(cell => cell.node.attrs.twigLoopId)
                            .find(Boolean)
                        const loopId = existingId ?? createTableLoopId()
                        const transaction = state.tr

                        range.cells.forEach(cell => transaction.setNodeMarkup(
                            cell.position,
                            undefined,
                            {
                                ...cell.node.attrs,
                                ...tableLoopNodeAttributes(attributes, loopId),
                            },
                        ))

                        dispatch(transaction)
                    }

                    return true
                },
            removeTwigTableCellLoop:
                () =>
                ({ state, dispatch }) => {
                    const range = getSelectedCellRange(this.editor)

                    if (!range || !range.cells.some(cell => hasTableLoopAttributes(cell.node.attrs))) {
                        return false
                    }

                    if (dispatch) {
                        const transaction = state.tr

                        range.cells.forEach(cell => transaction.setNodeMarkup(
                            cell.position,
                            undefined,
                            {
                                ...cell.node.attrs,
                                ...clearedTableLoopNodeAttributes(true),
                            },
                        ))

                        dispatch(transaction)
                    }

                    return true
                },
            setTwigTableRowCondition:
                attributes =>
                ({ state, dispatch }) => {
                    const selectedRow = getTableRow(this.editor)
                    const existingRange = selectedRow && hasTableConditionAttributes(selectedRow.node.attrs)
                        ? getTableConditionRowsAtPosition(
                            this.editor,
                            selectedRow.position,
                            selectedRow.node.attrs.twigConditionId,
                        )
                        : null
                    const range = existingRange ?? getSelectedTableRowRange(this.editor)

                    if (
                        !range
                        || (!existingRange && range.rows.some(row => hasTableConditionAttributes(row.node.attrs)))
                        || (
                            !existingRange
                            && range.rows.length > 1
                            && range.rows.some(row => hasTableLoopAttributes(row.node.attrs))
                        )
                    ) {
                        return false
                    }

                    if (dispatch) {
                        const conditionId = existingRange
                            ? selectedRow.node.attrs.twigConditionId
                            : createTableConditionId()
                        const transaction = state.tr

                        range.rows.forEach(row => transaction.setNodeMarkup(
                            row.position,
                            undefined,
                            {
                                ...row.node.attrs,
                                ...tableConditionNodeAttributes(attributes, conditionId),
                            },
                        ))

                        dispatch(transaction)
                    }

                    return true
                },
        }
    },

    addProseMirrorPlugins() {
        const editor = this.editor
        const settingsPanel = new Plugin({
            props: {
                handleClick: (_, position, event) => openInlineConditionSettingsPanel(editor, position, event),
                handleTextInput: (view, from, to, text) => replaceInlineElsePlaceholder(
                    view,
                    from,
                    to,
                    text,
                ),
                handleClickOn: (_, clickPosition, node, position, event, direct) => {
                    if (event.button !== 0) {
                        return false
                    }

                    if (direct && node.type.name === 'twigVariable') {
                        return openVariableFilterPanel(editor, position, node)
                    }

                    if (
                        ['tableCell', 'tableHeader'].includes(node.type.name)
                        && hasTableLoopAttributes(node.attrs)
                    ) {
                        return openTableLoopSettingsPanel(editor, position, node, 'cell', clickPosition)
                    }

                    if (node.type.name === 'twigApply') {
                        return openApplySettingsPanel(editor, position, node, event)
                    }

                    if (node.type.name === 'twigIf') {
                        return openBlockConditionSettingsPanel(editor, position, node, event)
                    }

                    return node.type.name === 'twigFor'
                        ? openLoopSettingsPanel(editor, position, node, event)
                        : false
                },
            },
        })
        const tableRowStructureDecorations = new Plugin({
            state: {
                init: (_, state) => createTableRowStructureDecorations(state.doc),
                apply: (transaction, decorations) => transaction.docChanged
                    ? createTableRowStructureDecorations(transaction.doc)
                    : decorations.map(transaction.mapping, transaction.doc),
            },
            props: {
                decorations: state => tableRowStructureDecorations.getState(state),
            },
        })

        return [settingsPanel, tableRowStructureDecorations]
    },

    onCreate() {
        queueMicrotask(() => {
            refreshEditorVariableChips(this.editor)
            refreshInlineConditionPlaceholders(this.editor)
            synchronizeOutline(this.editor)
            synchronizeFloatingToolbar(this.editor)
        })
    },

    onFocus() {
        synchronizeVariableScope(this.editor)
    },

    onSelectionUpdate() {
        if (this.editor.isFocused) {
            synchronizeVariableScope(this.editor)
        }

        synchronizeFloatingToolbar(this.editor)
    },

    onUpdate() {
        refreshInlineConditionPlaceholders(this.editor)

        if (this.editor.isFocused) {
            synchronizeVariableScope(this.editor, true)
        } else {
            refreshEditorVariableChips(this.editor)
        }

        synchronizeOutline(this.editor)
        synchronizeFloatingToolbar(this.editor)
        synchronizeVariableFilterPanel(this.editor)
        synchronizeLoopSettingsPanel(this.editor)
        synchronizeApplySettingsPanel(this.editor)
        synchronizeConditionSettingsPanel(this.editor)
    },

    addExtensions() {
        return [
            TwigVariable,
            TwigInlineIf,
            TwigApply,
            TwigThen,
            TwigElse,
            TwigIf,
            TwigForBody,
            TwigForElse,
            TwigFor,
        ]
    },
})
