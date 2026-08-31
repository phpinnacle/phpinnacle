<div
    x-data="{
        twigVariablePanelOpen: false,
        twigVariableFilterPanelOpen: false,
        twigConditionPanelOpen: false,
        twigOutlinePanelOpen: false,
        twigSnippetPanelOpen: false,
        twigTemplateStructureVisible: false,
        twigConditionBranchMode: 'both',
        twigOutlineItems: [],
        twigSnippets: @js($snippets),
        twigSnippetQuery: '',
        twigSnippetAvailableLabel: @js(__('phpinnacle-stylus::forms.twig_editor.panel.snippet_available')),
        twigSnippetMissingLabel: @js(__('phpinnacle-stylus::forms.twig_editor.panel.snippet_missing', ['variables' => '{variables}'])),
        twigVariableDefinitions: @js($variableDefinitions),
        twigFilterDefinitions: @js($filterDefinitions),
        twigConditionDefinitions: @js($conditionDefinitions),
        twigConditionOperandLabels: @js([
            'subject' => __('phpinnacle-stylus::forms.twig_editor.fields.variable'),
            'left' => __('phpinnacle-stylus::forms.twig_editor.fields.left_value'),
            'right' => __('phpinnacle-stylus::forms.twig_editor.fields.right_value'),
        ]),
        twigConditionOperandTypeLabels: @js([
            'variable' => __('phpinnacle-stylus::forms.twig_editor.condition.variable'),
            'string' => __('phpinnacle-stylus::forms.twig_editor.condition.string'),
            'number' => __('phpinnacle-stylus::forms.twig_editor.condition.number'),
            'boolean' => __('phpinnacle-stylus::forms.twig_editor.condition.boolean'),
        ]),
        twigVariableGroups: @js($variableGroups),
        twigFilterPanelTarget: 'variable',
        twigVariableFilterExpression: '',
        twigVariableFilterLabel: '',
        twigVariableFilterType: '',
        twigVariableFilterPosition: null,
        twigVariableFilterEditorSelection: null,
        twigVariableFilters: [],
        twigVariableFilterLoopStack: [],
        twigDraggedVariableFilterIndex: null,
        twigApplyPosition: null,
        twigApplyEditorSelection: null,
        twigConditionExpression: '',
        twigConditionAst: {
            type: 'group',
            operator: 'and',
            negated: false,
            children: [],
        },
        twigConditionPosition: null,
        twigConditionEditorSelection: null,
        twigConditionLoopStack: [],
        twigConditionInline: false,
        twigConditionTarget: 'block',
        twigConditionId: null,
        twigConditionHasElse: false,
        twigDraggedConditionIndex: null,
        twigConditionView: 'overview',
        twigConditionActiveIndex: null,
        twigConditionOperandKey: null,
        twigDraggedConditionFilterIndex: null,
        twigLoopItem: '',
        twigLoopKey: '',
        twigLoopIterable: '',
        twigLoopPosition: null,
        twigLoopEditorSelection: null,
        twigLoopStack: [],
        twigLoopHasElse: false,
        twigLoopTarget: 'block',
        twigLoopId: null,
        twigLoopInsertionMode: false,
        twigVariableQuery: '',
        twigFavoriteVariables: [],
        twigRecentVariables: [],
        twigExpandedVariableGroups: [],
        twigShowSampleValues: false,
        twigPanelWidth: 256,
        twigPanelMaxHeight: 320,
        twigPanelResize: null,
        twigVariablePreferenceKey: @js($preferenceKey),
        twigScopeLabel: @js(__('phpinnacle-stylus::forms.twig_editor.panel.scope', ['variable' => '{variable}'])),
        twigFavoriteGroupLabel: @js(__('phpinnacle-stylus::forms.twig_editor.panel.favorites')),
        twigRecentGroupLabel: @js(__('phpinnacle-stylus::forms.twig_editor.panel.recent')),
        twigOutlineLabels: @js([
            'condition' => __('phpinnacle-stylus::forms.twig_editor.outline.condition'),
            'rowCondition' => __('phpinnacle-stylus::forms.twig_editor.outline.row_condition'),
            'loop' => __('phpinnacle-stylus::forms.twig_editor.outline.loop'),
            'rowLoop' => __('phpinnacle-stylus::forms.twig_editor.outline.row_loop'),
            'cellLoop' => __('phpinnacle-stylus::forms.twig_editor.outline.cell_loop'),
            'variable' => __('phpinnacle-stylus::forms.twig_editor.outline.variable'),
        ]),
        twigLoopLabels: @js([
            'loop' => __('phpinnacle-stylus::forms.twig_editor.panel.loop'),
            'index' => __('phpinnacle-stylus::forms.twig_editor.panel.index'),
            'index0' => __('phpinnacle-stylus::forms.twig_editor.panel.index0'),
            'revindex' => __('phpinnacle-stylus::forms.twig_editor.panel.revindex'),
            'revindex0' => __('phpinnacle-stylus::forms.twig_editor.panel.revindex0'),
            'first' => __('phpinnacle-stylus::forms.twig_editor.panel.first'),
            'last' => __('phpinnacle-stylus::forms.twig_editor.panel.last'),
            'length' => __('phpinnacle-stylus::forms.twig_editor.panel.length'),
            'parent' => __('phpinnacle-stylus::forms.twig_editor.panel.parent'),
        ]),
        twigLoopPanelLabels: @js([
            'block' => __('phpinnacle-stylus::forms.twig_editor.panel.loop_settings'),
            'row' => __('phpinnacle-stylus::forms.twig_editor.modals.row_loop'),
            'cell' => __('phpinnacle-stylus::forms.twig_editor.modals.cell_loop'),
        ]),
        twigMeasurePanelHeight() {
            const editorContent = $root.querySelector('.fi-fo-rich-editor-content')
            const availableHeight = editorContent?.getBoundingClientRect().height
                ?? window.innerHeight - 160

            this.twigPanelMaxHeight = Math.max(160, Math.round(availableHeight))
        },
        twigClosePanels() {
            this.twigVariablePanelOpen = false
            this.twigVariableFilterPanelOpen = false
            this.twigConditionPanelOpen = false
            this.twigOutlinePanelOpen = false
            this.twigSnippetPanelOpen = false
        },
        twigClosePanelsOnOutsideClick(event) {
            const target = event.target

            if (
                target.closest('.fi-modal, [role=dialog], [data-stylus-twig-panel-trigger]')
                || target.matches('[data-twig-variable], [data-twig-inline-if], [data-twig-if], [data-twig-for], [data-twig-apply]')
                || target.closest('[data-twig-loop-iterable], [data-twig-loop-id], [data-twig-row-condition]')
            ) {
                return
            }

            this.twigClosePanels()
        },
        twigTogglePanel(panel) {
            const property = {
                variables: 'twigVariablePanelOpen',
                snippets: 'twigSnippetPanelOpen',
                outline: 'twigOutlinePanelOpen',
            }[panel]
            const shouldOpen = property ? ! this[property] : false

            this.twigClosePanels()

            if (shouldOpen) {
                this.twigMeasurePanelHeight()
                this[property] = true
            }
        },
        twigLoadVariablePreferences() {
            try {
                const preferences = JSON.parse(localStorage.getItem(this.twigVariablePreferenceKey) ?? '{}')

                this.twigFavoriteVariables = Array.isArray(preferences.favorites)
                    ? [...new Set(preferences.favorites.filter((name) => typeof name === 'string'))]
                    : []
                this.twigRecentVariables = Array.isArray(preferences.recent)
                    ? [...new Set(preferences.recent.filter((name) => typeof name === 'string'))].slice(0, 5)
                    : []
                this.twigPanelWidth = Number.isFinite(preferences.panelWidth)
                    ? Math.min(Math.max(Math.round(preferences.panelWidth), 256), 720)
                    : 256
                this.twigTemplateStructureVisible = preferences.templateStructure === true
                this.twigConditionBranchMode = ['both', 'if', 'else'].includes(preferences.conditionBranchMode)
                    ? preferences.conditionBranchMode
                    : 'both'
            } catch {
                this.twigFavoriteVariables = []
                this.twigRecentVariables = []
                this.twigPanelWidth = 256
                this.twigTemplateStructureVisible = false
                this.twigConditionBranchMode = 'both'
            }
        },
        twigStoreVariablePreferences() {
            try {
                localStorage.setItem(this.twigVariablePreferenceKey, JSON.stringify({
                    favorites: this.twigFavoriteVariables,
                    recent: this.twigRecentVariables,
                    panelWidth: this.twigPanelWidth,
                    templateStructure: this.twigTemplateStructureVisible,
                    conditionBranchMode: this.twigConditionBranchMode,
                }))
            } catch {
                // Browser storage is optional; the palette still works without it.
            }
        },
        twigSetConditionBranchMode(mode) {
            this.twigConditionBranchMode = mode
            this.twigStoreVariablePreferences()
        },
        twigClampPanelWidth(width, panel) {
            const availableWidth = panel?.parentElement?.getBoundingClientRect().width ?? window.innerWidth
            const maximumWidth = Math.max(256, Math.min(720, availableWidth - 320))

            return Math.round(Math.min(Math.max(width, 256), maximumWidth))
        },
        twigStartPanelResize(event) {
            if (event.pointerType === 'mouse' && event.button !== 0) {
                return
            }

            const panel = event.currentTarget.closest('.fi-stylus-twig-inline-panels')

            if (! panel) {
                return
            }

            this.twigPanelResize = {
                direction: getComputedStyle(panel).direction === 'rtl' ? 1 : -1,
                startWidth: panel.getBoundingClientRect().width,
                startX: event.clientX,
            }
            event.currentTarget.setPointerCapture?.(event.pointerId)
        },
        twigContinuePanelResize(event) {
            if (! this.twigPanelResize) {
                return
            }

            const panel = event.currentTarget.closest('.fi-stylus-twig-inline-panels')
            const distance = (event.clientX - this.twigPanelResize.startX) * this.twigPanelResize.direction

            this.twigPanelWidth = this.twigClampPanelWidth(this.twigPanelResize.startWidth + distance, panel)
        },
        twigFinishPanelResize(event) {
            if (! this.twigPanelResize) {
                return
            }

            if (event.currentTarget.hasPointerCapture?.(event.pointerId)) {
                event.currentTarget.releasePointerCapture(event.pointerId)
            }

            this.twigPanelResize = null
            this.twigStoreVariablePreferences()
        },
        twigAdjustPanelWidth(event) {
            const panel = event.currentTarget.closest('.fi-stylus-twig-inline-panels')
            const direction = getComputedStyle(panel).direction === 'rtl' ? 1 : -1
            const distance = (event.key === 'ArrowLeft' ? -32 : 32) * direction

            this.twigPanelWidth = this.twigClampPanelWidth(this.twigPanelWidth + distance, panel)
            this.twigStoreVariablePreferences()
        },
        twigRememberVariable(variable) {
            this.twigRecentVariables = [
                variable.name,
                ...this.twigRecentVariables.filter((name) => name !== variable.name),
            ].slice(0, 5)
            this.twigStoreVariablePreferences()
        },
        twigClearRecentVariables() {
            this.twigRecentVariables = []
            this.twigStoreVariablePreferences()
        },
        twigToggleFavorite(variable) {
            this.twigFavoriteVariables = this.twigFavoriteVariables.includes(variable.name)
                ? this.twigFavoriteVariables.filter((name) => name !== variable.name)
                : [...this.twigFavoriteVariables, variable.name]
            this.twigStoreVariablePreferences()
        },
        twigIsFavorite(variable) {
            return this.twigFavoriteVariables.includes(variable.name)
        },
        twigIsVariableGroup(variable) {
            return variable.item === null && variable.properties.length > 0
        },
        twigToggleVariableGroup(variable) {
            this.twigExpandedVariableGroups = this.twigExpandedVariableGroups.includes(variable.name)
                ? this.twigExpandedVariableGroups.filter((name) => name !== variable.name)
                : [...this.twigExpandedVariableGroups, variable.name]
        },
        twigFindVariables(names) {
            const variables = new Map(
                this.twigVariableGroups
                    .flatMap((group) => group.variables)
                    .filter((variable) => ! this.twigIsVariableGroup(variable))
                    .map((variable) => [variable.name, variable]),
            )

            return names.flatMap((name) => variables.has(name) ? [variables.get(name)] : [])
        },
        twigVariableMatches(variable, group, query) {
            return [variable.label, variable.description, variable.name, variable.type, group]
                .some((value) => String(value ?? '').toLocaleLowerCase().includes(query))
        },
        twigVariableRows(variables, group, query) {
            const parentNames = new Map()
            const childrenByParent = new Map()

            for (const variable of variables) {
                const parent = variables.find((candidate) =>
                    this.twigIsVariableGroup(candidate)
                    && candidate.properties.some((property) => `${candidate.name}.${property.name}` === variable.name),
                )

                if (! parent) {
                    continue
                }

                parentNames.set(variable.name, parent.name)
                childrenByParent.set(parent.name, [...(childrenByParent.get(parent.name) ?? []), variable.name])
            }

            const depthFor = (name) => {
                const parentName = parentNames.get(name)

                return parentName ? depthFor(parentName) + 1 : 0
            }
            const includedNames = new Set()

            if (query) {
                const includeDescendants = (name) => {
                    for (const childName of childrenByParent.get(name) ?? []) {
                        includedNames.add(childName)
                        includeDescendants(childName)
                    }
                }

                for (const variable of variables) {
                    if (! this.twigVariableMatches(variable, group, query)) {
                        continue
                    }

                    includedNames.add(variable.name)

                    let parentName = parentNames.get(variable.name)

                    while (parentName) {
                        includedNames.add(parentName)
                        parentName = parentNames.get(parentName)
                    }

                    if (this.twigIsVariableGroup(variable)) {
                        includeDescendants(variable.name)
                    }
                }
            }

            return variables
                .filter((variable) => ! this.twigIsVariableGroup(variable) || childrenByParent.has(variable.name))
                .filter((variable) => {
                    if (query) {
                        return includedNames.has(variable.name)
                    }

                    let parentName = parentNames.get(variable.name)

                    while (parentName) {
                        if (! this.twigExpandedVariableGroups.includes(parentName)) {
                            return false
                        }

                        parentName = parentNames.get(parentName)
                    }

                    return true
                })
                .map((variable) => ({
                    ...variable,
                    depth: depthFor(variable.name),
                    childCount: (childrenByParent.get(variable.name) ?? []).length,
                    isGroup: this.twigIsVariableGroup(variable),
                    expanded: query
                        ? (childrenByParent.get(variable.name) ?? []).some((name) => includedNames.has(name))
                        : this.twigExpandedVariableGroups.includes(variable.name),
                }))
        },
        twigDisplayedVariableGroups() {
            const query = this.twigVariableQuery.trim().toLocaleLowerCase()

            const groups = query ? this.twigVariableGroups : [
                {
                    key: 'favorites',
                    label: this.twigFavoriteGroupLabel,
                    variables: this.twigFindVariables(this.twigFavoriteVariables),
                },
                {
                    key: 'recent',
                    label: this.twigRecentGroupLabel,
                    variables: this.twigFindVariables(this.twigRecentVariables),
                },
                ...this.twigVariableGroups.map((group, index) => ({
                    key: `group-${index}-${group.label}`,
                    ...group,
                })),
            ]

            return groups
                .map((group, index) => ({
                    key: group.key ?? `group-${index}-${group.label}`,
                    label: group.label,
                    variables: this.twigVariableRows(group.variables, group.label, query),
                }))
                .filter((group) => group.variables.length)
        },
        twigOpenVariableFilterPanel(variable) {
            this.twigMeasurePanelHeight()
            this.twigFilterPanelTarget = 'variable'
            this.twigVariableFilterExpression = variable.expression
            this.twigVariableFilterLabel = variable.label
            this.twigVariableFilterType = variable.type
            this.twigVariableFilterPosition = variable.editorSelection.anchor
            this.twigVariableFilterEditorSelection = variable.editorSelection
            this.twigVariableFilters = variable.filters.map((filter) => ({
                ...filter,
                arguments: Array.isArray(filter.arguments) ? [...filter.arguments] : [],
                configuration: filter.configuration && typeof filter.configuration === 'object'
                    ? { ...filter.configuration }
                    : {},
            }))
            this.twigVariableFilterLoopStack = variable.loopStack
            this.twigVariableFilterPanelOpen = true
            this.twigConditionPanelOpen = false
            this.twigVariablePanelOpen = false
            this.twigOutlinePanelOpen = false
            this.twigSnippetPanelOpen = false
        },
        twigOpenLoopSettingsPanel(loop) {
            this.twigMeasurePanelHeight()
            this.twigFilterPanelTarget = 'loop'
            this.twigLoopInsertionMode = false
            this.twigLoopItem = loop.item
            this.twigLoopKey = loop.key ?? ''
            this.twigLoopIterable = loop.iterable
            this.twigLoopPosition = loop.position ?? loop.editorSelection.anchor
            this.twigLoopEditorSelection = loop.editorSelection
            this.twigLoopStack = loop.loopStack
            this.twigLoopHasElse = loop.hasElse
            this.twigLoopTarget = loop.target ?? 'block'
            this.twigLoopId = loop.id ?? null
            this.twigVariableFilterType = 'collection'
            this.twigVariableFilters = loop.transforms.map((filter) => ({
                ...filter,
                arguments: Array.isArray(filter.arguments) ? [...filter.arguments] : [],
                configuration: filter.configuration && typeof filter.configuration === 'object'
                    ? { ...filter.configuration }
                    : {},
            }))
            this.twigVariableFilterPanelOpen = true
            this.twigConditionPanelOpen = false
            this.twigVariablePanelOpen = false
            this.twigOutlinePanelOpen = false
            this.twigSnippetPanelOpen = false
        },
        twigOpenTableLoopPanel(loop) {
            if (! loop) {
                return
            }

            if (loop.existing) {
                this.twigOpenLoopSettingsPanel(loop)

                return
            }

            this.twigClosePanels()
            this.twigMeasurePanelHeight()
            this.twigFilterPanelTarget = 'loop'
            this.twigLoopInsertionMode = true
            this.twigLoopItem = ''
            this.twigLoopKey = ''
            this.twigLoopIterable = ''
            this.twigLoopPosition = loop.position
            this.twigLoopEditorSelection = loop.editorSelection
            this.twigLoopStack = loop.loopStack
            this.twigLoopHasElse = false
            this.twigLoopTarget = loop.target
            this.twigLoopId = null
            this.twigVariableFilterType = 'collection'
            this.twigVariableFilters = []
            this.twigVariableFilterPanelOpen = true
        },
        twigOpenApplyInsertPanel(editorSelection) {
            if (! editorSelection) {
                return
            }

            this.twigClosePanels()
            this.twigMeasurePanelHeight()
            this.twigFilterPanelTarget = 'apply-insert'
            this.twigApplyPosition = null
            this.twigApplyEditorSelection = editorSelection
            this.twigVariableFilters = []
            this.twigVariableFilterPanelOpen = true
        },
        twigOpenApplySettingsPanel(apply) {
            this.twigMeasurePanelHeight()
            this.twigFilterPanelTarget = 'apply'
            this.twigApplyPosition = apply.editorSelection.anchor
            this.twigApplyEditorSelection = apply.editorSelection
            this.twigVariableFilters = apply.filters.map((filter) => ({
                ...filter,
                arguments: Array.isArray(filter.arguments) ? [...filter.arguments] : [],
                configuration: filter.configuration && typeof filter.configuration === 'object'
                    ? { ...filter.configuration }
                    : {},
            }))
            this.twigVariableFilterPanelOpen = true
            this.twigConditionPanelOpen = false
            this.twigVariablePanelOpen = false
            this.twigOutlinePanelOpen = false
            this.twigSnippetPanelOpen = false
        },
        twigOpenConditionSettingsPanel(condition) {
            this.twigMeasurePanelHeight()
            const preserveDetail = this.twigConditionPanelOpen
                && this.twigConditionPosition === condition.position
                && this.twigConditionTarget === (condition.target ?? (condition.inline ? 'inline' : 'block'))
                && this.twigConditionId === (condition.id ?? null)
            const conditionAst = JSON.parse(JSON.stringify(condition.conditionAst))

            this.twigConditionExpression = condition.condition
            this.twigConditionAst = {
                type: 'group',
                operator: conditionAst.operator,
                negated: conditionAst.negated,
                children: conditionAst.children,
            }
            this.twigConditionPosition = condition.position
            this.twigConditionEditorSelection = condition.editorSelection
            this.twigConditionLoopStack = condition.loopStack
            this.twigConditionInline = condition.inline
            this.twigConditionTarget = condition.target ?? (condition.inline ? 'inline' : 'block')
            this.twigConditionId = condition.id ?? null
            this.twigConditionHasElse = condition.hasElse
            if (
                ! preserveDetail
                || ! Number.isInteger(this.twigConditionActiveIndex)
                || ! this.twigConditionAst.children[this.twigConditionActiveIndex]
            ) {
                this.twigConditionView = 'overview'
                this.twigConditionActiveIndex = null
                this.twigConditionOperandKey = null
            }
            if (this.twigConditionView === 'rule') {
                this.twigEnsureConditionOperandFilterTarget()
            }
            this.twigConditionPanelOpen = true
            this.twigVariablePanelOpen = false
            this.twigVariableFilterPanelOpen = false
            this.twigOutlinePanelOpen = false
            this.twigSnippetPanelOpen = false
        },
        twigConditionRuleKey(clause) {
            if (clause.type === 'comparison') {
                return `comparison:${clause.operator}`
            }

            if (clause.type === 'test') {
                return `test:${clause.test}`
            }

            return clause.type
        },
        twigConditionDefinition(clause) {
            const key = this.twigConditionRuleKey(clause)

            return this.twigConditionDefinitions.find((condition) => condition.key === key)
        },
        twigConditionVariable(name) {
            return this.twigVariableGroups
                .flatMap((group) => group.variables)
                .find((variable) => variable.name === name)
        },
        twigConditionVariables(definition, matchingType = null) {
            const variables = new Map()

            for (const variable of this.twigVariableGroups.flatMap((group) => group.variables)) {
                if (matchingType && variable.type !== matchingType) {
                    continue
                }

                if (! matchingType && definition?.types?.length && ! definition.types.includes(variable.type)) {
                    continue
                }

                variables.set(variable.name, variable)
            }

            return [...variables.values()]
        },
        twigConditionDefaultClause(definition) {
            const leftVariable = this.twigConditionVariables(definition)[0]

            if (! leftVariable) {
                return null
            }

            if (definition.type === 'comparison') {
                const rightVariable = this.twigConditionVariables(
                    definition,
                    definition.matchVariableTypes ? leftVariable.type : null,
                )[0]

                if (! rightVariable) {
                    return null
                }

                return {
                    type: 'comparison',
                    operator: definition.name,
                    left: { type: 'variable', name: leftVariable.name, filters: [] },
                    right: { type: 'variable', name: rightVariable.name, filters: [] },
                    negated: false,
                }
            }

            return {
                type: definition.type,
                subject: { type: 'variable', name: leftVariable.name, filters: [] },
                ...(definition.type === 'test' ? { test: definition.name } : {}),
                negated: false,
            }
        },
        twigCanAddConditionRule(definition) {
            return this.twigConditionAst.children.length < 50
                && this.twigConditionDefaultClause(definition) !== null
        },
        twigAddConditionRule(definition) {
            const clause = this.twigConditionDefaultClause(definition)

            if (! clause || this.twigConditionAst.children.length >= 50) {
                return false
            }

            this.twigConditionAst.children.push(clause)
            this.twigPersistCondition()
            this.twigOpenConditionRule(this.twigConditionAst.children.length - 1)

            return true
        },
        twigOpenConditionRule(index) {
            if (! this.twigConditionAst.children[index]) {
                return false
            }

            this.twigConditionActiveIndex = index
            this.twigConditionView = 'rule'
            this.twigConditionOperandKey = null
            this.twigEnsureConditionOperandFilterTarget()

            return true
        },
        twigActiveConditionClause() {
            return Number.isInteger(this.twigConditionActiveIndex)
                ? this.twigConditionAst.children[this.twigConditionActiveIndex] ?? null
                : null
        },
        twigActiveConditionDefinition() {
            const clause = this.twigActiveConditionClause()

            return clause ? this.twigConditionDefinition(clause) : null
        },
        twigConditionOperandKeys() {
            return this.twigActiveConditionClause()?.type === 'comparison'
                ? ['left', 'right']
                : ['subject']
        },
        twigConditionOperand(key) {
            return this.twigActiveConditionClause()?.[key] ?? null
        },
        twigConditionLiteralType(variableType) {
            return {
                text: 'string',
                date: 'string',
                number: 'number',
                integer: 'number',
                boolean: 'boolean',
            }[variableType] ?? null
        },
        twigConditionOperandTypes(key) {
            if (key !== 'right') {
                return ['variable']
            }

            const left = this.twigConditionOperand('left')
            const variable = left?.type === 'variable'
                ? this.twigConditionVariable(left.name)
                : null
            const literalType = this.twigConditionLiteralType(variable?.type)

            return [
                'variable',
                ...(literalType ? [literalType] : []),
            ]
        },
        twigConditionLiteralDefault(type) {
            return type === 'boolean' ? false : (type === 'number' ? '0' : '')
        },
        twigConditionVariableOperandKeys() {
            return this.twigConditionOperandKeys().filter((key) =>
                this.twigConditionOperand(key)?.type === 'variable',
            )
        },
        twigEnsureConditionOperandFilterTarget() {
            const keys = this.twigConditionVariableOperandKeys()

            if (! keys.includes(this.twigConditionOperandKey)) {
                this.twigConditionOperandKey = keys[0] ?? null
            }

            return this.twigConditionOperandKey
        },
        twigConditionOperandVariables(key) {
            const definition = this.twigActiveConditionDefinition()
            const clause = this.twigActiveConditionClause()

            if (! definition || ! clause) {
                return []
            }

            const matchingType = key === 'right'
                && definition.matchVariableTypes
                && clause.left?.type === 'variable'
                ? this.twigConditionVariable(clause.left.name)?.type ?? null
                : null

            return this.twigConditionVariables(definition, matchingType)
        },
        twigSetConditionOperandVariable(key, name) {
            const clause = this.twigActiveConditionClause()
            const variable = this.twigConditionVariable(name)

            if (! clause || ! variable) {
                return false
            }

            clause[key] = { type: 'variable', name, filters: [] }

            if (key === 'left' && clause.type === 'comparison') {
                const definition = this.twigActiveConditionDefinition()
                const right = clause.right
                const rightVariable = right?.type === 'variable'
                    ? this.twigConditionVariable(right.name)
                    : null

                if (definition?.matchVariableTypes && rightVariable?.type !== variable.type) {
                    const matchingVariable = this.twigConditionVariables(definition, variable.type)[0]

                    if (matchingVariable) {
                        clause.right = { type: 'variable', name: matchingVariable.name, filters: [] }
                    }
                }
            }

            this.twigEnsureConditionOperandFilterTarget()

            return this.twigPersistCondition()
        },
        twigSetConditionOperandType(key, type) {
            const clause = this.twigActiveConditionClause()

            if (! clause) {
                return false
            }

            if (! this.twigConditionOperandTypes(key).includes(type)) {
                return false
            }

            if (type === 'variable') {
                const variable = this.twigConditionOperandVariables(key)[0]

                if (! variable) {
                    return false
                }

                clause[key] = { type: 'variable', name: variable.name, filters: [] }
            } else {
                clause[key] = {
                    type: 'literal',
                    valueType: type,
                    value: this.twigConditionLiteralDefault(type),
                }
            }

            this.twigEnsureConditionOperandFilterTarget()

            return this.twigPersistCondition()
        },
        twigSetConditionLiteral(key, value) {
            const operand = this.twigConditionOperand(key)

            if (operand?.type !== 'literal') {
                return false
            }

            if (
                operand.valueType === 'number'
                && ! /^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/.test(String(value))
            ) {
                return false
            }

            if (operand.valueType === 'string' && String(value).length > 500) {
                return false
            }

            operand.value = operand.valueType === 'boolean' ? value === true : value

            return this.twigPersistCondition()
        },
        twigSelectConditionOperandFilters(key) {
            if (this.twigConditionOperand(key)?.type !== 'variable') {
                return false
            }

            this.twigConditionOperandKey = key

            return true
        },
        twigActiveConditionOperand() {
            return this.twigConditionOperand(this.twigConditionOperandKey)
        },
        twigAvailableConditionOperandFilters() {
            const operand = this.twigActiveConditionOperand()
            const variable = operand?.type === 'variable'
                ? this.twigConditionVariable(operand.name)
                : null
            const selectedNames = new Set((operand?.filters ?? []).map((filter) => filter.name))

            if (! variable) {
                return []
            }

            return this.twigFilterDefinitions.filter((filter) =>
                filter.conditionCompatible
                && ! selectedNames.has(filter.name)
                && (filter.types.length === 0 || filter.types.includes(variable.type)),
            )
        },
        twigConditionOperandFilterActionArguments() {
            return {
                ...this.twigConditionActionArguments(),
                conditionIndex: this.twigConditionActiveIndex,
                operandKey: this.twigConditionOperandKey,
            }
        },
        twigConfigureConditionOperandFilter(filter, index, $wire) {
            $wire.mountAction(
                'configureTwigConditionOperandFilter',
                {
                    ...this.twigConditionOperandFilterActionArguments(),
                    filterName: filter.name,
                    filterIndex: index,
                },
                { schemaComponent: @js($editorKey) },
            )
        },
        twigAddConditionOperandFilter(filter, $wire) {
            const operand = this.twigActiveConditionOperand()

            if (operand?.type !== 'variable') {
                return false
            }

            if (filter.configurable) {
                this.twigConfigureConditionOperandFilter(filter, null, $wire)

                return true
            }

            operand.filters = Array.isArray(operand.filters) ? operand.filters : []
            operand.filters.push({ name: filter.name, arguments: [], configuration: {} })

            return this.twigPersistCondition()
        },
        twigRemoveConditionOperandFilter(index) {
            const operand = this.twigActiveConditionOperand()

            if (! Array.isArray(operand?.filters)) {
                return false
            }

            operand.filters.splice(index, 1)

            return this.twigPersistCondition()
        },
        twigMoveConditionOperandFilter(index, offset) {
            const filters = this.twigActiveConditionOperand()?.filters
            const targetIndex = index + offset

            if (! Array.isArray(filters) || targetIndex < 0 || targetIndex >= filters.length) {
                return false
            }

            const [filter] = filters.splice(index, 1)
            filters.splice(targetIndex, 0, filter)

            return this.twigPersistCondition()
        },
        twigStartConditionOperandFilterDrag(index, event) {
            this.twigDraggedConditionFilterIndex = index
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', String(index))
        },
        twigDropConditionOperandFilter(index) {
            const sourceIndex = this.twigDraggedConditionFilterIndex
            const filters = this.twigActiveConditionOperand()?.filters

            this.twigDraggedConditionFilterIndex = null

            if (! Array.isArray(filters) || ! Number.isInteger(sourceIndex) || sourceIndex === index) {
                return false
            }

            const [filter] = filters.splice(sourceIndex, 1)
            filters.splice(index, 0, filter)

            return this.twigPersistCondition()
        },
        twigSerializeConditionOperand(operand) {
            if (operand?.type === 'variable') {
                const filters = Array.isArray(operand.filters)
                    ? operand.filters.map((filter) => `|${filter.name}${this.twigVariableFilterArguments(filter)}`).join('')
                    : ''

                return `${operand.name}${filters}`
            }

            if (operand?.type !== 'literal') {
                return ''
            }

            if (operand.valueType === 'string') {
                return `'${String(operand.value ?? '').replaceAll('\\', '\\\\').replaceAll(String.fromCharCode(39), '\\' + String.fromCharCode(39))}'`
            }

            if (operand.valueType === 'boolean') {
                return operand.value ? 'true' : 'false'
            }

            if (operand.valueType === 'number') {
                return String(operand.value ?? '')
            }

            throw new Error(`Unsupported Twig condition literal type: ${operand.valueType}`)
        },
        twigSerializeConditionRule(clause) {
            const definition = this.twigConditionDefinition(clause)
            let serialized = ''

            if (clause.type === 'comparison') {
                serialized = `${this.twigSerializeConditionOperand(clause.left)} ${definition?.expression ?? ''} ${this.twigSerializeConditionOperand(clause.right)}`
            } else if (clause.type === 'test') {
                serialized = `${this.twigSerializeConditionOperand(clause.subject)} is ${definition?.expression ?? ''}`
            } else {
                serialized = this.twigSerializeConditionOperand(clause.subject)
            }

            return clause.negated === true ? `not (${serialized})` : serialized
        },
        twigSerializeConditionAst() {
            const serialized = this.twigConditionAst.children
                .map((clause) => `(${this.twigSerializeConditionRule(clause)})`)
                .join(` ${this.twigConditionAst.operator} `)

            return this.twigConditionAst.negated ? `not (${serialized})` : serialized
        },
        twigPersistCondition() {
            if (this.twigConditionAst.children.length === 0) {
                return false
            }

            const condition = this.twigSerializeConditionAst()
            const updated = window.PHPinnacleStylusTwigEditor?.updateCondition(
                $root,
                this.twigConditionPosition,
                {
                    condition,
                    conditionAst: this.twigConditionAst,
                    hasElse: this.twigConditionHasElse,
                    id: this.twigConditionId,
                    inline: this.twigConditionInline,
                    target: this.twigConditionTarget,
                },
            ) ?? false

            if (updated) {
                this.twigConditionExpression = condition
            }

            return updated
        },
        twigConditionActionArguments() {
            return {
                conditionAst: this.twigConditionAst,
                editorSelection: this.twigConditionEditorSelection,
                inline: this.twigConditionInline,
                conditionTarget: this.twigConditionTarget,
                loopStack: this.twigConditionLoopStack,
            }
        },
        twigRemoveConditionRule(index) {
            if (this.twigConditionAst.children.length <= 1) {
                return
            }

            this.twigConditionAst.children.splice(index, 1)
            this.twigPersistCondition()

            if (this.twigConditionActiveIndex === index) {
                this.twigConditionView = 'overview'
                this.twigConditionActiveIndex = null
                this.twigConditionOperandKey = null
            }
        },
        twigMoveConditionRule(index, offset) {
            const targetIndex = index + offset

            if (targetIndex < 0 || targetIndex >= this.twigConditionAst.children.length) {
                return
            }

            const [condition] = this.twigConditionAst.children.splice(index, 1)
            this.twigConditionAst.children.splice(targetIndex, 0, condition)
            this.twigPersistCondition()
        },
        twigStartConditionDrag(index, event) {
            this.twigDraggedConditionIndex = index
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', String(index))
        },
        twigDropCondition(index) {
            const sourceIndex = this.twigDraggedConditionIndex

            this.twigDraggedConditionIndex = null

            if (! Number.isInteger(sourceIndex) || sourceIndex === index) {
                return
            }

            const [condition] = this.twigConditionAst.children.splice(sourceIndex, 1)
            this.twigConditionAst.children.splice(index, 0, condition)
            this.twigPersistCondition()
        },
        twigKeepConditionContent() {
            const unwrapped = window.PHPinnacleStylusTwigEditor?.keepConditionContent(
                $root,
                this.twigConditionPosition,
                this.twigConditionInline,
                this.twigConditionTarget,
                this.twigConditionId,
            ) ?? false

            if (unwrapped) {
                this.twigConditionPanelOpen = false
            }

            return unwrapped
        },
        twigDeleteCondition() {
            const deleted = window.PHPinnacleStylusTwigEditor?.deleteCondition(
                $root,
                this.twigConditionPosition,
                this.twigConditionInline,
                this.twigConditionTarget,
                this.twigConditionId,
            ) ?? false

            if (deleted) {
                this.twigConditionPanelOpen = false
            }

            return deleted
        },
        twigVariableFilterDefinition(name) {
            return this.twigFilterDefinitions.find((filter) => filter.name === name)
        },
        twigAvailableVariableFilters() {
            const selectedNames = new Set(this.twigVariableFilters.map((filter) => filter.name))

            return this.twigFilterDefinitions.filter((filter) => {
                if (selectedNames.has(filter.name)) {
                    return false
                }

                if (['apply', 'apply-insert'].includes(this.twigFilterPanelTarget)) {
                    return filter.blockCompatible
                }

                if (this.twigFilterPanelTarget === 'loop') {
                    return filter.collectionCompatible
                }

                return filter.types.length === 0 || filter.types.includes(this.twigVariableFilterType)
            })
        },
        twigAvailableLoopIterables() {
            const iterables = new Map()

            for (const variable of this.twigVariableGroups.flatMap((group) => group.variables)) {
                if (variable.type === 'collection') {
                    iterables.set(variable.name, variable)
                }
            }

            return [...iterables.values()]
        },
        twigVariableFilterArguments(filter) {
            return Array.isArray(filter.arguments) && filter.arguments.length
                ? `(${filter.arguments.join(', ')})`
                : ''
        },
        twigVariableActionArguments() {
            return {
                editorSelection: this.twigVariableFilterEditorSelection,
                expression: this.twigVariableFilterExpression,
                filters: this.twigVariableFilters,
                loopStack: this.twigVariableFilterLoopStack,
            }
        },
        twigLoopActionArguments() {
            return {
                editorSelection: this.twigLoopEditorSelection,
                filters: this.twigVariableFilters,
                item: this.twigLoopItem,
                key: this.twigLoopKey,
                iterable: this.twigLoopIterable,
                loopStack: this.twigLoopStack,
                loopTarget: this.twigLoopTarget,
            }
        },
        twigApplyActionArguments() {
            return {
                editorSelection: this.twigApplyEditorSelection,
                filters: this.twigVariableFilters,
            }
        },
        twigPersistVariableFilters() {
            if (this.twigFilterPanelTarget === 'apply') {
                return window.PHPinnacleStylusTwigEditor?.updateApplyFilters(
                    $root,
                    this.twigApplyPosition,
                    this.twigVariableFilters,
                ) ?? false
            }

            if (this.twigFilterPanelTarget === 'loop') {
                const item = this.twigLoopItem.trim()
                const key = this.twigLoopKey.trim()
                const identifier = /^[A-Za-z_][A-Za-z0-9_]*$/

                if (! identifier.test(item) || (key && ! identifier.test(key))) {
                    return false
                }

                const settings = {
                    item,
                    key: key || null,
                    iterable: this.twigLoopIterable,
                    transforms: this.twigVariableFilters,
                    hasElse: this.twigLoopHasElse,
                }

                return this.twigLoopTarget === 'block'
                    ? window.PHPinnacleStylusTwigEditor?.updateLoopSettings(
                        $root,
                        this.twigLoopPosition,
                        settings,
                    ) ?? false
                    : window.PHPinnacleStylusTwigEditor?.updateTableLoopSettings(
                        $root,
                        this.twigLoopTarget,
                        this.twigLoopPosition,
                        this.twigLoopId,
                        settings,
                    ) ?? false
            }

            window.PHPinnacleStylusTwigEditor?.updateVariableFilters(
                $root,
                this.twigVariableFilterPosition,
                this.twigVariableFilterExpression,
                this.twigVariableFilters,
            )
        },
        twigConfigureVariableFilter(filter, index, $wire) {
            const action = this.twigFilterPanelTarget === 'apply'
                ? 'configureTwigApplyFilter'
                : this.twigFilterPanelTarget === 'loop'
                    ? 'configureTwigLoopFilter'
                    : 'configureTwigVariableFilter'
            const arguments = this.twigFilterPanelTarget === 'apply'
                ? this.twigApplyActionArguments()
                : this.twigFilterPanelTarget === 'loop'
                    ? this.twigLoopActionArguments()
                    : this.twigVariableActionArguments()

            $wire.mountAction(
                action,
                {
                    ...arguments,
                    filterName: filter.name,
                    filterIndex: index,
                },
                { schemaComponent: @js($editorKey) },
            )
        },
        twigAddVariableFilter(filter, $wire) {
            if (this.twigFilterPanelTarget === 'apply-insert') {
                $wire.mountAction(
                    'insertTwigApply',
                    {
                        editorSelection: this.twigApplyEditorSelection,
                        filterName: filter.name,
                    },
                    { schemaComponent: @js($editorKey) },
                )

                return
            }

            if (filter.configurable) {
                this.twigConfigureVariableFilter(filter, null, $wire)

                return
            }

            this.twigVariableFilters.push({
                name: filter.name,
                arguments: [],
                configuration: {},
            })
            this.twigPersistVariableFilters()
        },
        twigInsertTableLoop(variable, $wire) {
            const action = this.twigLoopTarget === 'row'
                ? 'configureTwigTableRowLoop'
                : 'configureTwigTableCellLoop'

            $wire.mountAction(
                action,
                {
                    editorSelection: this.twigLoopEditorSelection,
                    iterable: variable.name,
                    loopStack: this.twigLoopStack,
                },
                { schemaComponent: @js($editorKey) },
            )
        },
        twigRemoveVariableFilter(index) {
            if (this.twigFilterPanelTarget === 'apply' && this.twigVariableFilters.length <= 1) {
                return
            }

            this.twigVariableFilters.splice(index, 1)
            this.twigPersistVariableFilters()
        },
        twigDeletePanelTarget() {
            let deleted = false

            if (this.twigFilterPanelTarget === 'apply') {
                deleted = window.PHPinnacleStylusTwigEditor?.deleteApply($root, this.twigApplyPosition)
            } else if (this.twigFilterPanelTarget === 'loop') {
                deleted = window.PHPinnacleStylusTwigEditor?.deleteLoop($root, this.twigLoopPosition)
            } else {
                deleted = window.PHPinnacleStylusTwigEditor?.deleteVariable($root, this.twigVariableFilterPosition)
            }

            if (deleted) {
                this.twigVariableFilterPanelOpen = false
            }

            return deleted ?? false
        },
        twigKeepLoopContent() {
            const unwrapped = this.twigLoopTarget === 'block'
                ? window.PHPinnacleStylusTwigEditor?.keepLoopContent($root, this.twigLoopPosition)
                : window.PHPinnacleStylusTwigEditor?.keepTableLoopContent(
                    $root,
                    this.twigLoopTarget,
                    this.twigLoopPosition,
                    this.twigLoopId,
                )

            if (unwrapped) {
                this.twigVariableFilterPanelOpen = false
            }

            return unwrapped ?? false
        },
        twigKeepApplyContent() {
            const unwrapped = window.PHPinnacleStylusTwigEditor?.keepApplyContent($root, this.twigApplyPosition)

            if (unwrapped) {
                this.twigVariableFilterPanelOpen = false
            }

            return unwrapped ?? false
        },
        twigMoveVariableFilter(index, offset) {
            const targetIndex = index + offset

            if (targetIndex < 0 || targetIndex >= this.twigVariableFilters.length) {
                return
            }

            const [filter] = this.twigVariableFilters.splice(index, 1)
            this.twigVariableFilters.splice(targetIndex, 0, filter)
            this.twigPersistVariableFilters()
        },
        twigStartVariableFilterDrag(index, event) {
            this.twigDraggedVariableFilterIndex = index
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', String(index))
        },
        twigDropVariableFilter(index) {
            const sourceIndex = this.twigDraggedVariableFilterIndex

            this.twigDraggedVariableFilterIndex = null

            if (! Number.isInteger(sourceIndex) || sourceIndex === index) {
                return
            }

            const [filter] = this.twigVariableFilters.splice(sourceIndex, 1)
            this.twigVariableFilters.splice(index, 0, filter)
            this.twigPersistVariableFilters()
        },
        twigSnippetMissingVariables(snippet) {
            const availableVariables = new Set(
                this.twigVariableGroups.flatMap((group) => group.variables.map((variable) => variable.name)),
            )

            return snippet.requiredVariables.filter((name) => ! availableVariables.has(name))
        },
        twigSnippetStatus(snippet) {
            const missingVariables = this.twigSnippetMissingVariables(snippet)

            return missingVariables.length
                ? this.twigSnippetMissingLabel.replace('{variables}', missingVariables.join(', '))
                : this.twigSnippetAvailableLabel
        },
        twigDisplayedSnippets() {
            const query = this.twigSnippetQuery.trim().toLocaleLowerCase()

            if (! query) {
                return this.twigSnippets
            }

            return this.twigSnippets.filter((snippet) => [
                snippet.label,
                snippet.description,
                snippet.name,
                ...snippet.requiredVariables,
            ].some((value) => String(value).toLocaleLowerCase().includes(query)))
        },
        twigInsertSnippet(snippet) {
            if (this.twigSnippetMissingVariables(snippet).length) {
                return false
            }

            return window.PHPinnacleStylusTwigEditor?.insertSnippet($root, snippet) ?? false
        },
    }"
    x-init="twigLoadVariablePreferences()"
    x-on:resize.window="twigMeasurePanelHeight()"
    x-bind:data-twig-structure-visible="twigTemplateStructureVisible ? 'true' : null"
    x-bind:data-twig-condition-branch-mode="twigConditionBranchMode"
    data-stylus-twig-editor="{{ $panelTarget }}"
    class="fi-stylus-twig-editor-shell"
>
    <div class="fi-stylus-twig-editor-main">
        {{ $editorHtml }}
    </div>

    <template x-teleport="[data-stylus-twig-editor='{{ $panelTarget }}'] .fi-fo-rich-editor-main">
        <aside
            x-show="twigVariableFilterPanelOpen"
            x-cloak
            x-on:click.outside="twigClosePanelsOnOutsideClick($event)"
            class="fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-filter-panel"
            x-bind:style="{
                '--fi-stylus-twig-panel-width': `${twigPanelWidth}px`,
                '--fi-stylus-twig-panel-max-height': `${twigPanelMaxHeight}px`,
            }"
            x-bind:class="{ 'fi-stylus-twig-inline-panels-resizing': twigPanelResize !== null }"
            x-bind:aria-label="['apply', 'apply-insert'].includes(twigFilterPanelTarget)
                ? @js(__('phpinnacle-stylus::forms.twig_editor.fields.block_filters'))
                : twigFilterPanelTarget === 'loop'
                    ? @js(__('phpinnacle-stylus::forms.twig_editor.panel.loop_settings'))
                    : @js(__('phpinnacle-stylus::forms.twig_editor.panel.variable_filters'))"
        >
            <div
                role="separator"
                tabindex="0"
                aria-orientation="vertical"
                aria-valuemin="256"
                aria-valuemax="720"
                x-bind:aria-valuenow="twigPanelWidth"
                x-on:pointerdown.prevent="twigStartPanelResize($event)"
                x-on:pointermove="twigContinuePanelResize($event)"
                x-on:pointerup="twigFinishPanelResize($event)"
                x-on:pointercancel="twigFinishPanelResize($event)"
                x-on:keydown.arrow-left.prevent="twigAdjustPanelWidth($event)"
                x-on:keydown.arrow-right.prevent="twigAdjustPanelWidth($event)"
                class="fi-stylus-twig-panel-resizer"
                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.resize_panel') }}"
            ></div>

            <div class="fi-stylus-twig-variable-panel-header">
                <div x-show="twigFilterPanelTarget === 'variable'" class="fi-stylus-twig-filter-panel-title">
                    <p class="fi-stylus-twig-variable-panel-heading" x-text="twigVariableFilterLabel"></p>
                    <code x-text="twigVariableFilterExpression"></code>
                </div>

                <div x-show="twigFilterPanelTarget === 'loop'" class="fi-stylus-twig-filter-panel-title">
                    <p
                        class="fi-stylus-twig-variable-panel-heading"
                        x-text="twigLoopPanelLabels[twigLoopTarget]"
                    ></p>
                    <code
                        x-show="! twigLoopInsertionMode"
                        x-text="`${twigLoopKey ? `${twigLoopKey}, ` : ''}${twigLoopItem} in ${twigLoopIterable}`"
                    ></code>
                </div>

                <div x-show="['apply', 'apply-insert'].includes(twigFilterPanelTarget)" class="fi-stylus-twig-filter-panel-title">
                    <p class="fi-stylus-twig-variable-panel-heading">
                        {{ __('phpinnacle-stylus::forms.twig_editor.fields.block_filters') }}
                    </p>
                    <code
                        x-show="twigFilterPanelTarget === 'apply'"
                        x-text="twigVariableFilters.map((filter) => `${filter.name}${twigVariableFilterArguments(filter)}`).join('|')"
                    ></code>
                </div>

                <button
                    type="button"
                    x-on:click="twigVariableFilterPanelOpen = false"
                    class="fi-stylus-twig-variable-panel-close"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.close_settings') }}"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" />
                </button>
            </div>

            <div
                x-show="twigFilterPanelTarget !== 'apply-insert' && ! twigLoopInsertionMode"
                class="fi-stylus-twig-panel-actions"
            >
                <button
                    type="button"
                    x-show="twigFilterPanelTarget === 'loop' && twigLoopTarget === 'block'"
                    x-on:click="twigLoopHasElse = ! twigLoopHasElse; twigPersistVariableFilters()"
                    x-bind:aria-pressed="twigLoopHasElse"
                    x-bind:class="{ 'fi-stylus-twig-panel-action-active': twigLoopHasElse }"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-keyword"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.fields.has_empty_state') }}"
                    title="{{ __('phpinnacle-stylus::forms.twig_editor.fields.has_empty_state') }}"
                >
                    <span>ELSE</span>
                </button>

                <button
                    type="button"
                    x-show="twigFilterPanelTarget === 'loop'"
                    x-on:click="twigKeepLoopContent()"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-icon"
                    x-bind:aria-label="twigLoopTarget === 'row'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.remove_row_loop'))
                        : twigLoopTarget === 'cell'
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.remove_cell_loop'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_loop'))"
                    x-bind:title="twigLoopTarget === 'row'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.remove_row_loop'))
                        : twigLoopTarget === 'cell'
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.remove_cell_loop'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_loop'))"
                >
                    <template x-if="twigLoopTarget === 'block'">
                        <x-filament::icon icon="heroicon-m-arrow-up-on-square" />
                    </template>

                    <template x-if="twigLoopTarget !== 'block'">
                        <x-filament::icon icon="heroicon-m-x-mark" />
                    </template>
                </button>

                <button
                    type="button"
                    x-show="twigFilterPanelTarget === 'apply'"
                    x-on:click="twigKeepApplyContent()"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-icon"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.tools.unwrap_apply') }}"
                    title="{{ __('phpinnacle-stylus::forms.twig_editor.tools.unwrap_apply') }}"
                >
                    <x-filament::icon icon="heroicon-m-arrow-up-on-square" />
                </button>

                <button
                    type="button"
                    x-show="twigFilterPanelTarget !== 'loop' || twigLoopTarget === 'block'"
                    x-on:click="twigDeletePanelTarget()"
                    x-bind:aria-label="twigFilterPanelTarget === 'apply'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_apply'))
                        : twigFilterPanelTarget === 'loop'
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_loop'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_variable'))"
                    x-bind:title="twigFilterPanelTarget === 'apply'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_apply'))
                        : twigFilterPanelTarget === 'loop'
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_loop'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_variable'))"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-icon fi-stylus-twig-panel-action-danger"
                >
                    <x-filament::icon icon="heroicon-m-trash" />
                </button>
            </div>

            <div class="fi-stylus-twig-filter-panel-body">
                <section
                    x-show="twigFilterPanelTarget === 'loop' && twigLoopInsertionMode"
                    class="fi-stylus-twig-filter-section"
                >
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.available_collections') }}</h3>
                    </div>

                    <div class="fi-stylus-twig-filter-catalog">
                        <template x-for="variable in twigAvailableLoopIterables()" x-bind:key="variable.name">
                            <button
                                type="button"
                                x-on:click="twigInsertTableLoop(variable, $wire)"
                                class="fi-stylus-twig-filter-option"
                            >
                                <span
                                    class="fi-stylus-twig-filter-icon"
                                    x-bind:data-stylus-twig-color="variable.color"
                                >
                                    <template x-if="variable.iconHtml">
                                        <span x-html="variable.iconHtml"></span>
                                    </template>

                                    <template x-if="! variable.iconHtml">
                                        <x-filament::icon icon="heroicon-m-rectangle-stack" />
                                    </template>
                                </span>

                                <span class="fi-stylus-twig-filter-copy">
                                    <span x-text="variable.label"></span>
                                    <code x-text="variable.name"></code>
                                    <small x-show="variable.description" x-text="variable.description"></small>
                                </span>

                                <x-filament::icon icon="heroicon-m-plus" />
                            </button>
                        </template>

                        <p
                            x-show="twigAvailableLoopIterables().length === 0"
                            x-cloak
                            class="fi-stylus-twig-filter-empty"
                        >
                            {{ __('phpinnacle-stylus::forms.twig_editor.panel.no_available_collections') }}
                        </p>
                    </div>
                </section>

                <section
                    x-show="twigFilterPanelTarget === 'loop' && ! twigLoopInsertionMode"
                    class="fi-stylus-twig-filter-section fi-stylus-twig-loop-settings"
                >
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.loop_configuration') }}</h3>
                    </div>

                    <div class="fi-stylus-twig-loop-fields">
                        <label class="fi-stylus-twig-loop-field">
                            <span>{{ __('phpinnacle-stylus::forms.twig_editor.fields.loop_item') }}</span>
                            <input
                                type="text"
                                required
                                pattern="[A-Za-z_][A-Za-z0-9_]*"
                                x-model="twigLoopItem"
                                x-on:change="twigPersistVariableFilters()"
                                x-on:keydown.enter.prevent="twigPersistVariableFilters(); $event.target.blur()"
                            />
                        </label>

                        <label class="fi-stylus-twig-loop-field">
                            <span>{{ __('phpinnacle-stylus::forms.twig_editor.fields.loop_key') }}</span>
                            <input
                                type="text"
                                pattern="[A-Za-z_][A-Za-z0-9_]*"
                                x-model="twigLoopKey"
                                x-on:change="twigPersistVariableFilters()"
                                x-on:keydown.enter.prevent="twigPersistVariableFilters(); $event.target.blur()"
                            />
                        </label>
                    </div>
                </section>

                <section
                    x-show="twigFilterPanelTarget !== 'apply-insert' && ! twigLoopInsertionMode"
                    class="fi-stylus-twig-filter-section"
                >
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.applied_filters') }}</h3>
                        <span x-text="twigVariableFilters.length"></span>
                    </div>

                    <div class="fi-stylus-twig-filter-list">
                        <template x-for="(filter, index) in twigVariableFilters" x-bind:key="`${filter.name}-${index}`">
                            <article
                                draggable="true"
                                tabindex="0"
                                x-on:dragstart="twigStartVariableFilterDrag(index, $event)"
                                x-on:dragend="twigDraggedVariableFilterIndex = null"
                                x-on:dragover.prevent
                                x-on:drop.prevent="twigDropVariableFilter(index)"
                                x-on:keydown.arrow-up.prevent.self="twigMoveVariableFilter(index, -1)"
                                x-on:keydown.arrow-down.prevent.self="twigMoveVariableFilter(index, 1)"
                                x-bind:class="{ 'fi-stylus-twig-filter-item-dragging': twigDraggedVariableFilterIndex === index }"
                                class="fi-stylus-twig-filter-item"
                                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.reorder_filter') }}"
                            >
                                <span
                                    class="fi-stylus-twig-filter-icon"
                                    x-bind:data-stylus-twig-color="twigVariableFilterDefinition(filter.name)?.color"
                                >
                                    <template x-if="twigVariableFilterDefinition(filter.name)?.iconHtml">
                                        <span x-html="twigVariableFilterDefinition(filter.name).iconHtml"></span>
                                    </template>

                                    <template x-if="! twigVariableFilterDefinition(filter.name)?.iconHtml">
                                        <x-filament::icon icon="heroicon-m-funnel" />
                                    </template>
                                </span>

                                <span class="fi-stylus-twig-filter-copy">
                                    <span x-text="twigVariableFilterDefinition(filter.name)?.label ?? filter.name"></span>
                                    <code x-text="`|${filter.name}${twigVariableFilterArguments(filter)}`"></code>
                                </span>

                                <span class="fi-stylus-twig-filter-actions">
                                    <button
                                        type="button"
                                        x-show="twigVariableFilterDefinition(filter.name)?.configurable"
                                        x-on:click="twigConfigureVariableFilter(filter, index, $wire)"
                                        class="fi-stylus-twig-filter-action"
                                        aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.configure_filter') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-cog-6-tooth" />
                                    </button>

                                    <button
                                        type="button"
                                        x-on:click="twigRemoveVariableFilter(index)"
                                        x-bind:disabled="twigFilterPanelTarget === 'apply' && twigVariableFilters.length <= 1"
                                        class="fi-stylus-twig-filter-action fi-stylus-twig-filter-remove"
                                        aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.remove_filter') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-x-mark" />
                                    </button>
                                </span>
                            </article>
                        </template>

                        <p
                            x-show="twigVariableFilters.length === 0"
                            x-cloak
                            class="fi-stylus-twig-filter-empty"
                        >
                            {{ __('phpinnacle-stylus::forms.twig_editor.panel.no_applied_filters') }}
                        </p>
                    </div>
                </section>

                <section x-show="! twigLoopInsertionMode" class="fi-stylus-twig-filter-section">
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.available_filters') }}</h3>
                    </div>

                    <div class="fi-stylus-twig-filter-catalog">
                        <template x-for="filter in twigAvailableVariableFilters()" x-bind:key="filter.name">
                            <button
                                type="button"
                                x-on:click="twigAddVariableFilter(filter, $wire)"
                                class="fi-stylus-twig-filter-option"
                            >
                                <span
                                    class="fi-stylus-twig-filter-icon"
                                    x-bind:data-stylus-twig-color="filter.color"
                                >
                                    <template x-if="filter.iconHtml">
                                        <span x-html="filter.iconHtml"></span>
                                    </template>

                                    <template x-if="! filter.iconHtml">
                                        <x-filament::icon icon="heroicon-m-funnel" />
                                    </template>
                                </span>

                                <span class="fi-stylus-twig-filter-copy">
                                    <span x-text="filter.label"></span>
                                    <small x-show="filter.description" x-text="filter.description"></small>
                                </span>

                                <template x-if="filter.configurable">
                                    <x-filament::icon icon="heroicon-m-plus-circle" />
                                </template>

                                <template x-if="! filter.configurable">
                                    <x-filament::icon icon="heroicon-m-plus" />
                                </template>
                            </button>
                        </template>

                        <p
                            x-show="twigAvailableVariableFilters().length === 0"
                            x-cloak
                            class="fi-stylus-twig-filter-empty"
                        >
                            {{ __('phpinnacle-stylus::forms.twig_editor.panel.no_available_filters') }}
                        </p>
                    </div>
                </section>

            </div>
        </aside>
    </template>

    <template x-teleport="[data-stylus-twig-editor='{{ $panelTarget }}'] .fi-fo-rich-editor-main">
        <aside
            x-show="twigConditionPanelOpen"
            x-cloak
            x-on:click.outside="twigClosePanelsOnOutsideClick($event)"
            class="fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-condition-panel"
            x-bind:style="{
                '--fi-stylus-twig-panel-width': `${twigPanelWidth}px`,
                '--fi-stylus-twig-panel-max-height': `${twigPanelMaxHeight}px`,
            }"
            x-bind:class="{ 'fi-stylus-twig-inline-panels-resizing': twigPanelResize !== null }"
            aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.condition_settings') }}"
        >
            <div
                role="separator"
                tabindex="0"
                aria-orientation="vertical"
                aria-valuemin="256"
                aria-valuemax="720"
                x-bind:aria-valuenow="twigPanelWidth"
                x-on:pointerdown.prevent="twigStartPanelResize($event)"
                x-on:pointermove="twigContinuePanelResize($event)"
                x-on:pointerup="twigFinishPanelResize($event)"
                x-on:pointercancel="twigFinishPanelResize($event)"
                x-on:keydown.arrow-left.prevent="twigAdjustPanelWidth($event)"
                x-on:keydown.arrow-right.prevent="twigAdjustPanelWidth($event)"
                class="fi-stylus-twig-panel-resizer"
                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.resize_panel') }}"
            ></div>

            <div class="fi-stylus-twig-variable-panel-header">
                <div class="fi-stylus-twig-condition-panel-heading">
                    <button
                        type="button"
                        x-show="twigConditionView === 'rule'"
                        x-on:click="twigConditionView = 'overview'; twigConditionActiveIndex = null; twigConditionOperandKey = null"
                        class="fi-stylus-twig-variable-panel-close"
                        aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.back') }}"
                    >
                        <x-filament::icon icon="heroicon-m-arrow-left" />
                    </button>

                    <div class="fi-stylus-twig-filter-panel-title">
                        <p x-show="twigConditionView === 'overview'" class="fi-stylus-twig-variable-panel-heading">
                        {{ __('phpinnacle-stylus::forms.twig_editor.panel.condition_settings') }}
                        </p>
                        <p
                            x-show="twigConditionView === 'rule'"
                            class="fi-stylus-twig-variable-panel-heading"
                            x-text="twigActiveConditionDefinition()?.label ?? ''"
                        ></p>
                        <code
                            x-text="twigConditionView === 'overview'
                                ? twigConditionExpression
                                : twigSerializeConditionRule(twigActiveConditionClause() ?? {})"
                        ></code>
                    </div>
                </div>

                <button
                    type="button"
                    x-on:click="twigConditionPanelOpen = false"
                    class="fi-stylus-twig-variable-panel-close"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.close_settings') }}"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" />
                </button>
            </div>

            <div x-show="twigConditionView === 'overview'" class="fi-stylus-twig-panel-actions">
                <button
                    type="button"
                    x-on:click="twigConditionAst.operator = twigConditionAst.operator === 'and' ? 'or' : 'and'; twigPersistCondition()"
                    x-bind:disabled="twigConditionAst.children.length <= 1"
                    x-bind:aria-label="`${@js(__('phpinnacle-stylus::forms.twig_editor.fields.boolean_operator'))}: ${twigConditionAst.operator === 'and'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.condition.all'))
                        : @js(__('phpinnacle-stylus::forms.twig_editor.condition.any'))}`"
                    x-bind:title="`${@js(__('phpinnacle-stylus::forms.twig_editor.fields.boolean_operator'))}: ${twigConditionAst.operator === 'and'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.condition.all'))
                        : @js(__('phpinnacle-stylus::forms.twig_editor.condition.any'))}`"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-keyword fi-stylus-twig-panel-action-operator"
                >
                    <span x-text="twigConditionAst.operator.toUpperCase()"></span>
                </button>

                <button
                    type="button"
                    x-on:click="twigConditionAst.negated = ! twigConditionAst.negated; twigPersistCondition()"
                    x-bind:aria-pressed="twigConditionAst.negated"
                    x-bind:class="{ 'fi-stylus-twig-panel-action-active': twigConditionAst.negated }"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-keyword"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.fields.negate_group') }}"
                    title="{{ __('phpinnacle-stylus::forms.twig_editor.fields.negate_group') }}"
                >
                    <span>NOT</span>
                </button>

                <button
                    type="button"
                    x-show="twigConditionTarget !== 'row'"
                    x-on:click="twigConditionHasElse = ! twigConditionHasElse; twigPersistCondition()"
                    x-bind:aria-pressed="twigConditionHasElse"
                    x-bind:class="{ 'fi-stylus-twig-panel-action-active': twigConditionHasElse }"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-keyword"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.fields.has_else') }}"
                    title="{{ __('phpinnacle-stylus::forms.twig_editor.fields.has_else') }}"
                >
                    <span>ELSE</span>
                </button>

                <button
                    type="button"
                    x-on:click="twigKeepConditionContent()"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-icon"
                    x-bind:aria-label="twigConditionTarget === 'row'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.keep_condition_rows'))
                        : (twigConditionInline
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_inline_condition'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_condition')))"
                    x-bind:title="twigConditionTarget === 'row'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.keep_condition_rows'))
                        : (twigConditionInline
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_inline_condition'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_condition')))"
                >
                    <x-filament::icon icon="heroicon-m-arrow-up-on-square" />
                </button>

                <button
                    type="button"
                    x-on:click="twigDeleteCondition()"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-icon fi-stylus-twig-panel-action-danger"
                    x-bind:aria-label="twigConditionTarget === 'row'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_condition_rows'))
                        : (twigConditionInline
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_inline_condition'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_condition')))"
                    x-bind:title="twigConditionTarget === 'row'
                        ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_condition_rows'))
                        : (twigConditionInline
                            ? @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_inline_condition'))
                            : @js(__('phpinnacle-stylus::forms.twig_editor.tools.delete_condition')))"
                >
                    <x-filament::icon icon="heroicon-m-trash" />
                </button>
            </div>

            <div x-show="twigConditionView === 'rule'" class="fi-stylus-twig-panel-actions">
                <button
                    type="button"
                    x-on:click="twigActiveConditionClause().negated = ! twigActiveConditionClause().negated; twigPersistCondition()"
                    x-bind:aria-pressed="twigActiveConditionClause()?.negated ?? false"
                    x-bind:class="{ 'fi-stylus-twig-panel-action-active': twigActiveConditionClause()?.negated }"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-keyword"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.fields.negate_clause') }}"
                    title="{{ __('phpinnacle-stylus::forms.twig_editor.fields.negate_clause') }}"
                >
                    <span>NOT</span>
                </button>

                <button
                    type="button"
                    x-on:click="twigRemoveConditionRule(twigConditionActiveIndex)"
                    x-bind:disabled="twigConditionAst.children.length <= 1"
                    class="fi-stylus-twig-panel-action fi-stylus-twig-panel-action-icon fi-stylus-twig-panel-action-danger"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.remove_condition') }}"
                    title="{{ __('phpinnacle-stylus::forms.twig_editor.panel.remove_condition') }}"
                >
                    <x-filament::icon icon="heroicon-m-trash" />
                </button>
            </div>

            <div x-show="twigConditionView === 'overview'" class="fi-stylus-twig-filter-panel-body">
                <section class="fi-stylus-twig-filter-section">
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.condition_rules') }}</h3>
                        <span x-text="twigConditionAst.children.length"></span>
                    </div>

                    <div class="fi-stylus-twig-filter-list">
                        <template x-for="(clause, index) in twigConditionAst.children" x-bind:key="`${twigConditionRuleKey(clause)}-${index}`">
                            <article
                                draggable="true"
                                tabindex="0"
                                x-on:click="twigOpenConditionRule(index)"
                                x-on:dragstart="twigStartConditionDrag(index, $event)"
                                x-on:dragend="twigDraggedConditionIndex = null"
                                x-on:dragover.prevent
                                x-on:drop.prevent="twigDropCondition(index)"
                                x-on:keydown.arrow-up.prevent.self="twigMoveConditionRule(index, -1)"
                                x-on:keydown.arrow-down.prevent.self="twigMoveConditionRule(index, 1)"
                                x-bind:class="{ 'fi-stylus-twig-filter-item-dragging': twigDraggedConditionIndex === index }"
                                class="fi-stylus-twig-filter-item fi-stylus-twig-condition-rule"
                                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.reorder_condition') }}"
                            >
                                <span
                                    class="fi-stylus-twig-filter-icon"
                                    x-bind:data-stylus-twig-color="twigConditionDefinition(clause)?.color"
                                >
                                    <template x-if="twigConditionDefinition(clause)?.iconHtml">
                                        <span x-html="twigConditionDefinition(clause).iconHtml"></span>
                                    </template>

                                    <template x-if="! twigConditionDefinition(clause)?.iconHtml">
                                        <x-filament::icon icon="heroicon-m-arrows-right-left" />
                                    </template>
                                </span>

                                <span class="fi-stylus-twig-filter-copy">
                                    <span x-text="twigConditionDefinition(clause)?.label ?? twigConditionRuleKey(clause)"></span>
                                    <code x-text="twigSerializeConditionRule(clause)"></code>
                                </span>

                                <span class="fi-stylus-twig-filter-actions">
                                    <label
                                        class="fi-stylus-twig-condition-rule-negate"
                                        title="{{ __('phpinnacle-stylus::forms.twig_editor.fields.negate_clause') }}"
                                        x-on:mousedown.stop
                                        x-on:dragstart.stop.prevent
                                        x-on:click.stop
                                    >
                                        <input
                                            type="checkbox"
                                            x-model="clause.negated"
                                            x-on:change="twigPersistCondition()"
                                            aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.fields.negate_clause') }}"
                                        />
                                        <span aria-hidden="true">{{ __('phpinnacle-stylus::forms.twig_editor.panel.negate_condition') }}</span>
                                    </label>

                                    <button
                                        type="button"
                                        x-on:click.stop="twigOpenConditionRule(index)"
                                        class="fi-stylus-twig-filter-action"
                                        aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.configure_condition') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-chevron-right" />
                                    </button>

                                    <button
                                        type="button"
                                        x-on:click.stop="twigRemoveConditionRule(index)"
                                        x-bind:disabled="twigConditionAst.children.length <= 1"
                                        class="fi-stylus-twig-filter-action fi-stylus-twig-filter-remove"
                                        aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.remove_condition') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-x-mark" />
                                    </button>
                                </span>
                            </article>
                        </template>

                        <p
                            x-show="twigConditionAst.children.length === 0"
                            x-cloak
                            class="fi-stylus-twig-filter-empty"
                        >
                            {{ __('phpinnacle-stylus::forms.twig_editor.panel.no_condition_rules') }}
                        </p>
                    </div>
                </section>

                <section class="fi-stylus-twig-filter-section">
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.available_conditions') }}</h3>
                    </div>

                    <div class="fi-stylus-twig-filter-catalog">
                        <template x-for="condition in twigConditionDefinitions" x-bind:key="condition.key">
                            <button
                                type="button"
                                x-on:click="twigAddConditionRule(condition)"
                                x-bind:disabled="! twigCanAddConditionRule(condition)"
                                class="fi-stylus-twig-filter-option"
                            >
                                <span
                                    class="fi-stylus-twig-filter-icon"
                                    x-bind:data-stylus-twig-color="condition.color"
                                >
                                    <template x-if="condition.iconHtml">
                                        <span x-html="condition.iconHtml"></span>
                                    </template>

                                    <template x-if="! condition.iconHtml">
                                        <x-filament::icon icon="heroicon-m-arrows-right-left" />
                                    </template>
                                </span>

                                <span class="fi-stylus-twig-filter-copy">
                                    <span x-text="condition.label"></span>
                                    <small x-show="condition.description" x-text="condition.description"></small>
                                </span>

                                <x-filament::icon icon="heroicon-m-plus" />
                            </button>
                        </template>
                    </div>
                </section>
            </div>

            <div x-show="twigConditionView === 'rule'" class="fi-stylus-twig-filter-panel-body">
                <template x-if="twigActiveConditionClause()">
                    <div class="fi-stylus-twig-condition-detail">
                        <p
                            x-show="twigActiveConditionDefinition()?.description"
                            class="fi-stylus-twig-condition-detail-description"
                            x-text="twigActiveConditionDefinition()?.description"
                        ></p>

                        <div class="fi-stylus-twig-condition-operands">
                            <template x-for="key in twigConditionOperandKeys()" x-bind:key="key">
                                <section class="fi-stylus-twig-condition-operand">
                                    <div class="fi-stylus-twig-condition-operand-heading">
                                        <span x-text="twigConditionOperandLabels[key]"></span>

                                        <button
                                            type="button"
                                            x-show="twigConditionOperand(key)?.type === 'variable'"
                                            x-on:click="twigSelectConditionOperandFilters(key)"
                                            x-bind:aria-pressed="twigConditionOperandKey === key"
                                            x-bind:class="{ 'fi-stylus-twig-condition-filter-link-active': twigConditionOperandKey === key }"
                                            class="fi-stylus-twig-condition-filter-link"
                                        >
                                            <x-filament::icon icon="heroicon-m-funnel" />
                                            <span>{{ __('phpinnacle-stylus::forms.twig_editor.fields.filters') }}</span>
                                            <span
                                                class="fi-stylus-twig-condition-filter-count"
                                                x-text="twigConditionOperand(key)?.filters?.length ?? 0"
                                            ></span>
                                        </button>
                                    </div>

                                    <label
                                        x-show="key === 'right' && twigActiveConditionClause()?.type === 'comparison'"
                                        class="fi-stylus-twig-condition-field"
                                    >
                                        <span>{{ __('phpinnacle-stylus::forms.twig_editor.fields.operand_type') }}</span>
                                        <select
                                            x-bind:value="twigConditionOperand(key)?.type === 'variable'
                                                ? 'variable'
                                                : twigConditionOperand(key)?.valueType"
                                            x-on:change="twigSetConditionOperandType(key, $event.target.value)"
                                        >
                                            <template x-for="type in twigConditionOperandTypes(key)" x-bind:key="type">
                                                <option x-bind:value="type" x-text="twigConditionOperandTypeLabels[type]"></option>
                                            </template>
                                        </select>
                                    </label>

                                    <label
                                        x-show="twigConditionOperand(key)?.type === 'variable'"
                                        class="fi-stylus-twig-condition-field"
                                    >
                                        <span>{{ __('phpinnacle-stylus::forms.twig_editor.fields.variable') }}</span>
                                        <select
                                            x-bind:value="twigConditionOperand(key)?.name"
                                            x-on:change="twigSetConditionOperandVariable(key, $event.target.value)"
                                        >
                                            <template x-for="variable in twigConditionOperandVariables(key)" x-bind:key="variable.name">
                                                <option
                                                    x-bind:value="variable.name"
                                                    x-text="`${variable.label} · ${variable.name}`"
                                                ></option>
                                            </template>
                                        </select>
                                    </label>

                                    <label
                                        x-show="twigConditionOperand(key)?.type === 'literal' && ['string', 'number'].includes(twigConditionOperand(key)?.valueType)"
                                        class="fi-stylus-twig-condition-field"
                                    >
                                        <span>{{ __('phpinnacle-stylus::forms.twig_editor.fields.value') }}</span>
                                        <input
                                            x-bind:type="twigConditionOperand(key)?.valueType === 'number' ? 'number' : 'text'"
                                            x-bind:step="twigConditionOperand(key)?.valueType === 'number' ? 'any' : null"
                                            x-bind:maxlength="twigConditionOperand(key)?.valueType === 'string' ? 500 : null"
                                            x-bind:value="twigConditionOperand(key)?.value ?? ''"
                                            x-on:change="twigSetConditionLiteral(key, $event.target.value)"
                                        />
                                    </label>

                                    <label
                                        x-show="twigConditionOperand(key)?.type === 'literal' && twigConditionOperand(key)?.valueType === 'boolean'"
                                        class="fi-stylus-twig-condition-field"
                                    >
                                        <span>{{ __('phpinnacle-stylus::forms.twig_editor.fields.value') }}</span>
                                        <select
                                            x-bind:value="twigConditionOperand(key)?.value === true ? 'true' : 'false'"
                                            x-on:change="twigSetConditionLiteral(key, $event.target.value === 'true')"
                                        >
                                            <option value="true">{{ __('phpinnacle-stylus::forms.twig_editor.condition.true') }}</option>
                                            <option value="false">{{ __('phpinnacle-stylus::forms.twig_editor.condition.false') }}</option>
                                        </select>
                                    </label>
                                </section>
                            </template>
                        </div>

                        <div
                            x-show="twigActiveConditionOperand()?.type === 'variable'"
                            class="fi-stylus-twig-condition-filter-sections"
                        >
                            <section class="fi-stylus-twig-filter-section">
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.applied_filters') }}</h3>
                        <span x-text="twigActiveConditionOperand()?.filters?.length ?? 0"></span>
                    </div>

                    <div class="fi-stylus-twig-filter-list">
                        <template
                            x-for="(filter, index) in (twigActiveConditionOperand()?.filters ?? [])"
                            x-bind:key="`${filter.name}-${index}`"
                        >
                            <article
                                draggable="true"
                                tabindex="0"
                                x-on:dragstart="twigStartConditionOperandFilterDrag(index, $event)"
                                x-on:dragend="twigDraggedConditionFilterIndex = null"
                                x-on:dragover.prevent
                                x-on:drop.prevent="twigDropConditionOperandFilter(index)"
                                x-on:keydown.arrow-up.prevent.self="twigMoveConditionOperandFilter(index, -1)"
                                x-on:keydown.arrow-down.prevent.self="twigMoveConditionOperandFilter(index, 1)"
                                x-bind:class="{ 'fi-stylus-twig-filter-item-dragging': twigDraggedConditionFilterIndex === index }"
                                class="fi-stylus-twig-filter-item"
                                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.reorder_filter') }}"
                            >
                                <span
                                    class="fi-stylus-twig-filter-icon"
                                    x-bind:data-stylus-twig-color="twigVariableFilterDefinition(filter.name)?.color"
                                >
                                    <template x-if="twigVariableFilterDefinition(filter.name)?.iconHtml">
                                        <span x-html="twigVariableFilterDefinition(filter.name).iconHtml"></span>
                                    </template>

                                    <template x-if="! twigVariableFilterDefinition(filter.name)?.iconHtml">
                                        <x-filament::icon icon="heroicon-m-funnel" />
                                    </template>
                                </span>

                                <span class="fi-stylus-twig-filter-copy">
                                    <span x-text="twigVariableFilterDefinition(filter.name)?.label ?? filter.name"></span>
                                    <code x-text="`|${filter.name}${twigVariableFilterArguments(filter)}`"></code>
                                </span>

                                <span class="fi-stylus-twig-filter-actions">
                                    <button
                                        type="button"
                                        x-show="twigVariableFilterDefinition(filter.name)?.configurable"
                                        x-on:click="twigConfigureConditionOperandFilter(filter, index, $wire)"
                                        class="fi-stylus-twig-filter-action"
                                        aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.configure_filter') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-cog-6-tooth" />
                                    </button>

                                    <button
                                        type="button"
                                        x-on:click="twigRemoveConditionOperandFilter(index)"
                                        class="fi-stylus-twig-filter-action fi-stylus-twig-filter-remove"
                                        aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.remove_filter') }}"
                                    >
                                        <x-filament::icon icon="heroicon-m-x-mark" />
                                    </button>
                                </span>
                            </article>
                        </template>

                        <p
                            x-show="(twigActiveConditionOperand()?.filters?.length ?? 0) === 0"
                            x-cloak
                            class="fi-stylus-twig-filter-empty"
                        >{{ __('phpinnacle-stylus::forms.twig_editor.panel.no_applied_filters') }}</p>
                    </div>
                            </section>

                            <section class="fi-stylus-twig-filter-section">
                    <div class="fi-stylus-twig-filter-section-heading">
                        <h3>{{ __('phpinnacle-stylus::forms.twig_editor.panel.available_filters') }}</h3>
                    </div>

                    <div class="fi-stylus-twig-filter-catalog">
                        <template x-for="filter in twigAvailableConditionOperandFilters()" x-bind:key="filter.name">
                            <button
                                type="button"
                                x-on:click="twigAddConditionOperandFilter(filter, $wire)"
                                class="fi-stylus-twig-filter-option"
                            >
                                <span
                                    class="fi-stylus-twig-filter-icon"
                                    x-bind:data-stylus-twig-color="filter.color"
                                >
                                    <template x-if="filter.iconHtml">
                                        <span x-html="filter.iconHtml"></span>
                                    </template>

                                    <template x-if="! filter.iconHtml">
                                        <x-filament::icon icon="heroicon-m-funnel" />
                                    </template>
                                </span>

                                <span class="fi-stylus-twig-filter-copy">
                                    <span x-text="filter.label"></span>
                                    <small x-show="filter.description" x-text="filter.description"></small>
                                </span>

                                <template x-if="filter.configurable">
                                    <x-filament::icon icon="heroicon-m-plus-circle" />
                                </template>

                                <template x-if="! filter.configurable">
                                    <x-filament::icon icon="heroicon-m-plus" />
                                </template>
                            </button>
                        </template>

                        <p
                            x-show="twigAvailableConditionOperandFilters().length === 0"
                            x-cloak
                            class="fi-stylus-twig-filter-empty"
                        >{{ __('phpinnacle-stylus::forms.twig_editor.panel.no_available_filters') }}</p>
                    </div>
                            </section>
                        </div>
                    </div>
                </template>
            </div>
        </aside>
    </template>

    <template x-teleport="[data-stylus-twig-editor='{{ $panelTarget }}'] .fi-fo-rich-editor-main">
        <aside
            x-show="twigVariablePanelOpen"
            x-cloak
            x-on:click.outside="twigClosePanelsOnOutsideClick($event)"
            class="fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-variable-panel"
            x-bind:style="{
                '--fi-stylus-twig-panel-width': `${twigPanelWidth}px`,
                '--fi-stylus-twig-panel-max-height': `${twigPanelMaxHeight}px`,
            }"
            x-bind:class="{ 'fi-stylus-twig-inline-panels-resizing': twigPanelResize !== null }"
            aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.variables') }}"
        >
        <div
            role="separator"
            tabindex="0"
            aria-orientation="vertical"
            aria-valuemin="256"
            aria-valuemax="720"
            x-bind:aria-valuenow="twigPanelWidth"
            x-on:pointerdown.prevent="twigStartPanelResize($event)"
            x-on:pointermove="twigContinuePanelResize($event)"
            x-on:pointerup="twigFinishPanelResize($event)"
            x-on:pointercancel="twigFinishPanelResize($event)"
            x-on:keydown.arrow-left.prevent="twigAdjustPanelWidth($event)"
            x-on:keydown.arrow-right.prevent="twigAdjustPanelWidth($event)"
            class="fi-stylus-twig-panel-resizer"
            aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.resize_panel') }}"
        ></div>

        <div class="fi-stylus-twig-variable-panel-header">
            <div>
                <p class="fi-stylus-twig-variable-panel-heading">
                    {{ __('phpinnacle-stylus::forms.twig_editor.panel.variables') }}
                </p>

                <p class="fi-stylus-twig-variable-panel-description">
                    {{ __('phpinnacle-stylus::forms.twig_editor.panel.description') }}
                </p>
            </div>

            <button
                type="button"
                x-on:click="twigVariablePanelOpen = false"
                class="fi-stylus-twig-variable-panel-close"
                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.close') }}"
            >
                <x-filament::icon icon="heroicon-m-x-mark" />
            </button>
        </div>

        <button
            type="button"
            x-show="twigVariableGroups.some((group) => group.variables.some((variable) => variable.sample !== null))"
            x-on:click="twigShowSampleValues = ! twigShowSampleValues; $nextTick(() => window.PHPinnacleStylusTwigEditor?.refreshVariableChips($root))"
            x-bind:aria-pressed="twigShowSampleValues"
            x-bind:class="{ 'fi-stylus-twig-variable-samples-active': twigShowSampleValues }"
            class="fi-stylus-twig-variable-samples"
        >
            <x-filament::icon icon="heroicon-m-eye" />
            <span>{{ __('phpinnacle-stylus::forms.twig_editor.panel.sample_values') }}</span>
        </button>

        <div class="fi-stylus-twig-variable-search">
            <x-filament::icon icon="heroicon-m-magnifying-glass" />

            <input
                type="search"
                x-model.debounce.150ms="twigVariableQuery"
                placeholder="{{ __('phpinnacle-stylus::forms.twig_editor.panel.search') }}"
                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.search') }}"
            />
        </div>

        <div class="fi-stylus-twig-variable-groups">
            <template x-for="group in twigDisplayedVariableGroups()" x-bind:key="group.key">
                <section class="fi-stylus-twig-variable-group">
                    <template x-if="group.label">
                        <div class="fi-stylus-twig-variable-group-header">
                            <h3 class="fi-stylus-twig-variable-group-heading" x-text="group.label"></h3>

                            <button
                                type="button"
                                x-show="group.key === 'recent'"
                                x-on:click="twigClearRecentVariables()"
                                class="fi-stylus-twig-variable-group-clear"
                            >
                                {{ __('phpinnacle-stylus::forms.twig_editor.panel.clear_recent') }}
                            </button>
                        </div>
                    </template>

                    <div class="fi-stylus-twig-variable-list">
                        <template x-for="variable in group.variables" x-bind:key="variable.name">
                            <div
                                x-bind:style="`--twig-variable-depth: ${variable.depth}`"
                                x-bind:class="{ 'fi-stylus-twig-variable-row-nested': variable.depth > 0 }"
                                class="fi-stylus-twig-variable-row"
                            >
                                <template x-if="variable.isGroup">
                                    <button
                                        type="button"
                                        x-on:click="twigToggleVariableGroup(variable)"
                                        x-bind:aria-expanded="variable.expanded"
                                        class="fi-stylus-twig-variable-group-button"
                                    >
                                        <span
                                            x-bind:class="{ 'fi-stylus-twig-variable-group-chevron-open': variable.expanded }"
                                            class="fi-stylus-twig-variable-group-chevron"
                                            aria-hidden="true"
                                        >
                                            <x-filament::icon icon="heroicon-m-chevron-right" />
                                        </span>

                                        <span class="fi-stylus-twig-variable-group-copy">
                                            <span class="fi-stylus-twig-variable-title">
                                                <span
                                                    x-show="variable.iconHtml"
                                                    x-html="variable.iconHtml"
                                                    class="fi-stylus-twig-variable-icon"
                                                ></span>

                                                <span class="fi-stylus-twig-variable-label" x-text="variable.label"></span>
                                            </span>

                                            <span
                                                x-show="variable.description"
                                                class="fi-stylus-twig-variable-description"
                                                x-text="variable.description"
                                            ></span>

                                            <span class="fi-stylus-twig-variable-meta">
                                                <code x-text="variable.name"></code>
                                                <span x-text="variable.type"></span>
                                            </span>
                                        </span>

                                        <span class="fi-stylus-twig-variable-group-count" x-text="variable.childCount"></span>
                                    </button>
                                </template>

                                <template x-if="! variable.isGroup">
                                    <div class="fi-stylus-twig-variable-actions">
                                        <button
                                            type="button"
                                            x-on:click="twigRememberVariable(variable); window.PHPinnacleStylusTwigEditor?.insertVariable($root, variable)"
                                            class="fi-stylus-twig-variable-button"
                                        >
                                            <span class="fi-stylus-twig-variable-title">
                                                <span
                                                    x-show="variable.iconHtml"
                                                    x-html="variable.iconHtml"
                                                    class="fi-stylus-twig-variable-icon"
                                                ></span>

                                                <span class="fi-stylus-twig-variable-label" x-text="variable.label"></span>
                                            </span>

                                            <span
                                                x-show="variable.description"
                                                class="fi-stylus-twig-variable-description"
                                                x-text="variable.description"
                                            ></span>

                                            <span class="fi-stylus-twig-variable-meta">
                                                <code x-text="variable.name"></code>
                                                <span x-text="variable.type"></span>
                                            </span>
                                        </button>

                                        <button
                                            type="button"
                                            x-on:click="twigToggleFavorite(variable)"
                                            x-bind:class="{ 'fi-stylus-twig-variable-favorite-active': twigIsFavorite(variable) }"
                                            x-bind:aria-pressed="twigIsFavorite(variable)"
                                            x-bind:aria-label="twigIsFavorite(variable)
                                                ? @js(__('phpinnacle-stylus::forms.twig_editor.panel.remove_favorite'))
                                                : @js(__('phpinnacle-stylus::forms.twig_editor.panel.add_favorite'))"
                                            class="fi-stylus-twig-variable-favorite"
                                        >
                                            <x-filament::icon icon="heroicon-m-star" />
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </section>
            </template>

            <p
                x-show="twigDisplayedVariableGroups().length === 0"
                x-cloak
                class="fi-stylus-twig-variable-empty"
            >
                {{ __('phpinnacle-stylus::forms.twig_editor.panel.empty_search') }}
            </p>
        </div>
        </aside>
    </template>

    <template x-teleport="[data-stylus-twig-editor='{{ $panelTarget }}'] .fi-fo-rich-editor-main">
        <aside
            x-show="twigSnippetPanelOpen"
            x-cloak
            x-on:click.outside="twigClosePanelsOnOutsideClick($event)"
            class="fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-snippet-panel"
            x-bind:style="{
                '--fi-stylus-twig-panel-width': `${twigPanelWidth}px`,
                '--fi-stylus-twig-panel-max-height': `${twigPanelMaxHeight}px`,
            }"
            x-bind:class="{ 'fi-stylus-twig-inline-panels-resizing': twigPanelResize !== null }"
            aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.snippets') }}"
        >
        <div
            role="separator"
            tabindex="0"
            aria-orientation="vertical"
            aria-valuemin="256"
            aria-valuemax="720"
            x-bind:aria-valuenow="twigPanelWidth"
            x-on:pointerdown.prevent="twigStartPanelResize($event)"
            x-on:pointermove="twigContinuePanelResize($event)"
            x-on:pointerup="twigFinishPanelResize($event)"
            x-on:pointercancel="twigFinishPanelResize($event)"
            x-on:keydown.arrow-left.prevent="twigAdjustPanelWidth($event)"
            x-on:keydown.arrow-right.prevent="twigAdjustPanelWidth($event)"
            class="fi-stylus-twig-panel-resizer"
            aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.resize_panel') }}"
        ></div>

        <div class="fi-stylus-twig-variable-panel-header">
            <div>
                <p class="fi-stylus-twig-variable-panel-heading">
                    {{ __('phpinnacle-stylus::forms.twig_editor.panel.snippets') }}
                </p>

                <p class="fi-stylus-twig-variable-panel-description">
                    {{ __('phpinnacle-stylus::forms.twig_editor.panel.snippets_description') }}
                </p>
            </div>

            <button
                type="button"
                x-on:click="twigSnippetPanelOpen = false"
                class="fi-stylus-twig-variable-panel-close"
                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.close_snippets') }}"
            >
                <x-filament::icon icon="heroicon-m-x-mark" />
            </button>
        </div>

        <div class="fi-stylus-twig-variable-search">
            <x-filament::icon icon="heroicon-m-magnifying-glass" />

            <input
                type="search"
                x-model.debounce.150ms="twigSnippetQuery"
                placeholder="{{ __('phpinnacle-stylus::forms.twig_editor.panel.search_snippets') }}"
                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.search_snippets') }}"
            />
        </div>

        <div class="fi-stylus-twig-snippet-list">
            <template x-for="snippet in twigDisplayedSnippets()" x-bind:key="snippet.name">
                <button
                    type="button"
                    x-on:click="twigInsertSnippet(snippet)"
                    x-bind:disabled="twigSnippetMissingVariables(snippet).length > 0"
                    x-bind:class="{ 'fi-stylus-twig-snippet-unavailable': twigSnippetMissingVariables(snippet).length > 0 }"
                    class="fi-stylus-twig-snippet-button"
                >
                    <span class="fi-stylus-twig-snippet-icon">
                        <template x-if="snippet.iconHtml">
                            <span x-html="snippet.iconHtml"></span>
                        </template>

                        <template x-if="! snippet.iconHtml">
                            <x-filament::icon icon="heroicon-m-squares-plus" />
                        </template>
                    </span>

                    <span class="fi-stylus-twig-snippet-copy">
                        <span class="fi-stylus-twig-snippet-label" x-text="snippet.label"></span>
                        <code x-text="snippet.name"></code>
                        <span
                            x-show="snippet.description"
                            class="fi-stylus-twig-snippet-description"
                            x-text="snippet.description"
                        ></span>
                        <span class="fi-stylus-twig-snippet-status" x-text="twigSnippetStatus(snippet)"></span>
                    </span>
                </button>
            </template>

            <p
                x-show="twigDisplayedSnippets().length === 0"
                x-cloak
                class="fi-stylus-twig-variable-empty"
            >
                {{ __('phpinnacle-stylus::forms.twig_editor.panel.empty_snippets') }}
            </p>
        </div>
        </aside>
    </template>

    <template x-teleport="[data-stylus-twig-editor='{{ $panelTarget }}'] .fi-fo-rich-editor-main">
        <aside
            x-show="twigOutlinePanelOpen"
            x-cloak
            x-on:click.outside="twigClosePanelsOnOutsideClick($event)"
            class="fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-outline-panel"
            x-bind:style="{
                '--fi-stylus-twig-panel-width': `${twigPanelWidth}px`,
                '--fi-stylus-twig-panel-max-height': `${twigPanelMaxHeight}px`,
            }"
            x-bind:class="{ 'fi-stylus-twig-inline-panels-resizing': twigPanelResize !== null }"
            aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.outline.heading') }}"
        >
            <div
                role="separator"
                tabindex="0"
                aria-orientation="vertical"
                aria-valuemin="256"
                aria-valuemax="720"
                x-bind:aria-valuenow="twigPanelWidth"
                x-on:pointerdown.prevent="twigStartPanelResize($event)"
                x-on:pointermove="twigContinuePanelResize($event)"
                x-on:pointerup="twigFinishPanelResize($event)"
                x-on:pointercancel="twigFinishPanelResize($event)"
                x-on:keydown.arrow-left.prevent="twigAdjustPanelWidth($event)"
                x-on:keydown.arrow-right.prevent="twigAdjustPanelWidth($event)"
                class="fi-stylus-twig-panel-resizer"
                aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.panel.resize_panel') }}"
            ></div>

            <div class="fi-stylus-twig-variable-panel-header">
                <div>
                    <p class="fi-stylus-twig-variable-panel-heading">
                        {{ __('phpinnacle-stylus::forms.twig_editor.outline.heading') }}
                    </p>

                    <p class="fi-stylus-twig-variable-panel-description">
                        {{ __('phpinnacle-stylus::forms.twig_editor.outline.description') }}
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click="twigOutlinePanelOpen = false"
                    class="fi-stylus-twig-variable-panel-close"
                    aria-label="{{ __('phpinnacle-stylus::forms.twig_editor.outline.close') }}"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" />
                </button>
            </div>

            <nav class="fi-stylus-twig-snippet-list fi-stylus-twig-outline-list">
                <template x-for="item in twigOutlineItems" x-bind:key="item.key">
                    <button
                        type="button"
                        x-on:click="window.PHPinnacleStylusTwigEditor?.focusOutlineItem($root, item)"
                        x-bind:style="`--twig-outline-depth: ${item.depth}`"
                        class="fi-stylus-twig-snippet-button fi-stylus-twig-outline-item"
                    >
                        <span class="fi-stylus-twig-snippet-icon">
                            <x-filament::icon icon="heroicon-m-list-bullet" />
                        </span>

                        <span class="fi-stylus-twig-snippet-copy">
                            <span class="fi-stylus-twig-snippet-label" x-text="twigOutlineLabels[item.type]"></span>
                            <code x-text="item.label"></code>
                        </span>
                    </button>
                </template>

                <p
                    x-show="twigOutlineItems.length === 0"
                    x-cloak
                    class="fi-stylus-twig-variable-empty"
                >
                    {{ __('phpinnacle-stylus::forms.twig_editor.outline.empty') }}
                </p>
            </nav>
        </aside>
    </template>
</div>
