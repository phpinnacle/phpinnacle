import {Extension, mergeAttributes, Node} from '@tiptap/core'

function findFirstDtPosFrom(doc, fromPos) {
    let dtPos = null

    doc.nodesBetween(fromPos, Math.min(fromPos + 50, doc.content.size), (node, pos) => {
        if (dtPos !== null) return false
        if (node.type.name === 'descriptionTerm') {
            dtPos = pos + 1
            return false
        }
        return true
    })

    return dtPos
}

const DescriptionTerm = Node.create({
    name: 'descriptionTerm',
    content: 'text*',
    marks: '',
    defining: true,

    parseHTML() {
        return [{ tag: 'dt' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['dt', mergeAttributes(HTMLAttributes), 0]
    },

    addKeyboardShortcuts() {
        const moveToDd = () => {
            const { $from } = this.editor.state.selection

            for (let depth = $from.depth; depth > 0; depth--) {
                if ($from.node(depth).type.name !== 'descriptionTerm') continue
                return this.editor.commands.setTextSelection($from.after(depth) + 1)
            }

            return false
        }

        return {
            Enter: moveToDd,
            Tab: moveToDd,
            Backspace: () => {
                const { $from } = this.editor.state.selection
                if ($from.parentOffset !== 0) return false
                if ($from.parent.type.name !== 'descriptionTerm') return false
                if ($from.parent.content.size !== 0) return false
                return this.editor.commands.removeDescriptionPair()
            },
        }
    },
})

const DescriptionDetails = Node.create({
    name: 'descriptionDetails',
    content: 'block+',
    defining: true,

    parseHTML() {
        return [{ tag: 'dd' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['dd', mergeAttributes(HTMLAttributes), 0]
    },
})

const DescriptionList = Node.create({
    name: 'descriptionList',
    group: 'block',
    content: '(descriptionTerm descriptionDetails)+',
    defining: true,
    isolating: true,

    parseHTML() {
        return [{ tag: 'dl' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['dl', mergeAttributes(HTMLAttributes), 0]
    },
})

export default Extension.create({
    name: 'descriptionListKit',

    addExtensions() {
        return [DescriptionList, DescriptionTerm, DescriptionDetails]
    },

    addCommands() {
        return {
            removeDescriptionPair:
                () =>
                ({ state, dispatch }) => {
                    const { $from } = state.selection

                    let dlDepth = -1
                    for (let depth = $from.depth; depth > 0; depth--) {
                        if ($from.node(depth).type.name === 'descriptionList') {
                            dlDepth = depth
                            break
                        }
                    }

                    if (dlDepth === -1) return false

                    const dlNode = $from.node(dlDepth)

                    if (dlNode.childCount <= 2) {
                        if (dispatch) {
                            const from = $from.before(dlDepth)
                            dispatch(state.tr.delete(from, from + dlNode.nodeSize))
                        }
                        return true
                    }

                    const childIndex = $from.index(dlDepth)
                    const dtIndex = childIndex % 2 === 0 ? childIndex : childIndex - 1

                    let pos = $from.start(dlDepth)
                    for (let i = 0; i < dtIndex; i++) {
                        pos += dlNode.child(i).nodeSize
                    }

                    const from = pos
                    const to = pos + dlNode.child(dtIndex).nodeSize + dlNode.child(dtIndex + 1).nodeSize

                    if (dispatch) dispatch(state.tr.delete(from, to))
                    return true
                },

            addDescriptionPair:
                () =>
                ({ state, chain }) => {
                    const { $from } = state.selection

                    let afterDd = null

                    for (let depth = $from.depth; depth > 0; depth--) {
                        if ($from.node(depth).type.name === 'descriptionDetails') {
                            afterDd = $from.after(depth)
                            break
                        }
                        if ($from.node(depth).type.name === 'descriptionTerm') {
                            const parent = $from.node(depth - 1)
                            const nextIndex = $from.index(depth - 1) + 1
                            if (nextIndex >= parent.childCount) return false
                            afterDd = $from.after(depth) + parent.child(nextIndex).nodeSize
                            break
                        }
                    }

                    if (afterDd === null) return false

                    return chain()
                        .insertContentAt(afterDd, [
                            { type: 'descriptionTerm', content: [] },
                            { type: 'descriptionDetails', content: [{ type: 'paragraph', content: [] }] },
                        ])
                        .command(({ state: newState, commands }) => {
                            const dtPos = findFirstDtPosFrom(newState.doc, afterDd)
                            return dtPos !== null ? commands.setTextSelection(dtPos) : true
                        })
                        .run()
                },

            insertDescriptionList:
                () =>
                ({ chain, state }) => {
                    const { from } = state.selection

                    return chain()
                        .insertContent({
                            type: 'descriptionList',
                            content: [
                                { type: 'descriptionTerm', content: [] },
                                {
                                    type: 'descriptionDetails',
                                    content: [{ type: 'paragraph', content: [] }],
                                },
                            ],
                        })
                        .command(({ state: newState, commands }) => {
                            const dtPos = findFirstDtPosFrom(newState.doc, from)
                            return dtPos !== null ? commands.setTextSelection(dtPos) : true
                        })
                        .run()
                },
        }
    },
})
