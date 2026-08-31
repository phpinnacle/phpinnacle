window.phpinnacleIconPicker = {
    mount(select, wire, componentKey, config) {
        if (!select?.dropdown || select.dropdown.dataset.iconPickerPagination) {
            return
        }

        select.dropdown.dataset.iconPickerPagination = 'true'
        select.optionsLimit = 0

        let hasMore = true
        let isLoading = false
        let requestVersion = 0

        const loadMore = document.createElement('button')
        loadMore.type = 'button'
        loadMore.className = 'phpinnacle-icon-picker-load-more'
        loadMore.hidden = true

        const currentSearch = () => select.searchInput?.value.trim() ?? ''

        const syncLoadMore = () => {
            loadMore.disabled = isLoading
            loadMore.hidden =
                (!hasMore && !isLoading) ||
                (select.options.length === 0 && !isLoading) ||
                select.isSearching
            loadMore.textContent = isLoading
                ? config.loadingLabel
                : config.loadMoreLabel

            select.dropdown.append(loadMore)
        }

        const loadPage = async (reset = false) => {
            if (
                isLoading ||
                select.isSearching ||
                select.searchTimeout ||
                (!reset && !hasMore)
            ) {
                return
            }

            const search = currentSearch()
            const offset = reset ? 0 : select.options.length
            const version = ++requestVersion

            isLoading = true

            if (reset) {
                select.showLoadingState(false)
                loadMore.hidden = true
            } else {
                syncLoadMore()
            }

            try {
                const page = await wire.callSchemaComponentMethod(
                    componentKey,
                    'getIconPageForJs',
                    { search, offset },
                )

                if (
                    version !== requestVersion ||
                    search !== currentSearch() ||
                    !page
                ) {
                    return
                }

                const options = Array.isArray(page.options)
                    ? page.options
                    : []

                select.options = reset
                    ? options
                    : [...select.options, ...options]
                select.populateLabelRepositoryFromOptions(options)
                hasMore = page.hasMore === true
                select.hideLoadingState()
                select.renderOptions()
                select.deferPositionDropdown()
            } catch (error) {
                console.error('Unable to load icon picker options.', error)
                select.hideLoadingState()
            } finally {
                if (version === requestVersion) {
                    isLoading = false
                    syncLoadMore()
                }
            }
        }

        const originalRenderOptions = select.renderOptions.bind(select)

        select.renderOptions = () => {
            originalRenderOptions()

            window.setTimeout(() => {
                if (!select.isSearching) {
                    if (select.options.length < config.pageSize) {
                        hasMore = false
                    }

                    syncLoadMore()
                }
            })
        }

        select.searchInput?.addEventListener('input', () => {
            requestVersion++
            isLoading = false
            hasMore = true
            loadMore.hidden = true

            if (currentSearch() === '') {
                window.setTimeout(() => loadPage(true))
            }
        })

        select.dropdown.addEventListener('scroll', () => {
            const remaining =
                select.dropdown.scrollHeight -
                select.dropdown.scrollTop -
                select.dropdown.clientHeight

            if (remaining < 80) {
                loadPage()
            }
        })

        loadMore.addEventListener('click', () => loadPage())

        const ensureFirstPage = () => {
            window.setTimeout(() => {
                if (
                    select.isOpen &&
                    currentSearch() === '' &&
                    select.options.length === 0
                ) {
                    hasMore = true
                    loadPage(true)
                } else {
                    syncLoadMore()
                }
            })
        }

        const openObserver = new MutationObserver(() => {
            if (
                select.selectButton.getAttribute('aria-expanded') === 'true'
            ) {
                ensureFirstPage()
            }
        })

        openObserver.observe(select.selectButton, {
            attributes: true,
            attributeFilter: ['aria-expanded'],
        })

        const originalDestroy = select.destroy.bind(select)

        select.destroy = () => {
            openObserver.disconnect()
            originalDestroy()
        }
    },
}
