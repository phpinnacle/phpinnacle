import { Extension, Mark, Node, mergeAttributes } from '@tiptap/core'

const conditionAttribute = {
    default: null,
    parseHTML: element => element.getAttribute('data-condition'),
    renderHTML: attributes => attributes.condition
        ? { 'data-condition': attributes.condition }
        : {},
}

const ConditionBlock = Node.create({
    name: 'conditionBlock',
    priority: 110,
    group: 'block',
    content: 'block+',
    defining: true,

    addAttributes() {
        return {
            condition: conditionAttribute,
        }
    },

    parseHTML() {
        return [{ tag: 'div[data-condition]', priority: 100 }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes), 0]
    },

    addCommands() {
        return {
            setBlockCondition:
                condition =>
                ({ commands }) => {
                    if (typeof condition !== 'string' || condition.trim() === '') return false

                    if (this.editor.isActive(this.name)) {
                        return commands.updateAttributes(this.name, { condition })
                    }

                    return commands.wrapIn(this.name, { condition })
                },
            unsetBlockCondition:
                () =>
                ({ commands }) => commands.lift(this.name),
        }
    },
})

const ConditionInline = Mark.create({
    name: 'conditionInline',
    inclusive: false,

    addAttributes() {
        return {
            condition: conditionAttribute,
        }
    },

    parseHTML() {
        return [{ tag: 'span[data-condition]' }]
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes), 0]
    },

    addCommands() {
        return {
            setInlineCondition:
                condition =>
                ({ chain, state }) => {
                    if (typeof condition !== 'string' || condition.trim() === '') return false

                    const command = chain()

                    if (state.selection.empty && this.editor.isActive(this.name)) {
                        command.extendMarkRange(this.name)
                    }

                    return command.setMark(this.name, { condition }).run()
                },
            unsetInlineCondition:
                () =>
                ({ chain, state }) => {
                    const command = chain()

                    if (state.selection.empty) {
                        command.extendMarkRange(this.name)
                    }

                    return command.unsetMark(this.name).run()
                },
        }
    },
})

export default Extension.create({
    name: 'conditionKit',

    addExtensions() {
        return [ConditionBlock, ConditionInline]
    },
})
