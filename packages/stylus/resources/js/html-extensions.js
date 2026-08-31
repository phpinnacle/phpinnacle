import {Extension, mergeAttributes, Node} from '@tiptap/core'

const Div = Node.create({
  name: 'div',
  content: 'block+',
  group: 'block',

  parseHTML() {
    return [{ tag: 'div' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['div', mergeAttributes(HTMLAttributes), 0]
  },

  addAttributes() {
    return {
      class: { default: null },
      style: { default: null },
      id: { default: null },
    }
  },
})

const Section = Node.create({
  name: 'section',
  content: 'block+',
  group: 'block',

  parseHTML() {
    return [{ tag: 'section' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['section', mergeAttributes(HTMLAttributes), 0]
  },

  addAttributes() {
    return {
      class: { default: null },
      id: { default: null },
    }
  },
})

const Article = Node.create({
  name: 'article',
  content: 'block+',
  group: 'block',

  parseHTML() {
    return [{ tag: 'article' }]
  },

  renderHTML({ HTMLAttributes }) {
    return ['article', mergeAttributes(HTMLAttributes), 0]
  },

  addAttributes() {
    return {
      class: { default: null },
      id: { default: null },
    }
  },
})

export default Extension.create({
  name: 'htmlExtensions',

  addExtensions() {
    return [Div, Section, Article]
  },
})
