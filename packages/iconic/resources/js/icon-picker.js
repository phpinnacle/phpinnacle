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
        const preferenceKey = 'phpinnacle-iconic:preferences:v1'

        const normalizeIcons = (icons, limit = 100) =>
            Array.isArray(icons)
                ? [
                      ...new Set(
                          icons.filter((icon) => typeof icon === 'string'),
                      ),
                  ].slice(0, limit)
                : []

        const preferences = () => {
            try {
                const stored = JSON.parse(
                    window.localStorage.getItem(preferenceKey) ?? '{}',
                )

                return {
                    favorites: normalizeIcons(stored.favorites),
                    recent: normalizeIcons(stored.recent, 5),
                }
            } catch {
                return { favorites: [], recent: [] }
            }
        }

        const persistPreferences = (value) => {
            try {
                window.localStorage.setItem(
                    preferenceKey,
                    JSON.stringify(value),
                )
            } catch {}
        }

        const preferredIcons = () => {
            const { favorites, recent } = preferences()

            return [...new Set([...favorites, ...recent])]
        }

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
                    { search, offset, preferred: preferredIcons() },
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

        const decorateOptions = () => {
            const { favorites } = preferences()

            select.optionsList
                .querySelectorAll('li[role="option"]')
                .forEach((option) => {
                    const icon = option.dataset.value
                    const isFavorite = favorites.includes(icon)
                    const favorite = document.createElement('button')

                    favorite.type = 'button'
                    favorite.className = 'phpinnacle-icon-picker-favorite'
                    favorite.classList.toggle('is-favorite', isFavorite)
                    favorite.textContent = isFavorite ? '★' : '☆'
                    favorite.title = isFavorite
                        ? config.removeFavoriteLabel
                        : config.addFavoriteLabel
                    favorite.setAttribute('aria-label', favorite.title)
                    favorite.addEventListener('click', (event) => {
                        event.preventDefault()
                        event.stopPropagation()

                        const current = preferences()
                        current.favorites = current.favorites.includes(icon)
                            ? current.favorites.filter((value) => value !== icon)
                            : [icon, ...current.favorites]
                        persistPreferences(current)

                        if (!isLoading && !select.isSearching) {
                            loadPage(true)
                        } else {
                            select.renderOptions()
                        }
                    })

                    option.append(favorite)
                })
        }

        select.renderOptions = () => {
            const preference = new Map(
                preferredIcons().map((icon, index) => [icon, index]),
            )
            select.options = [...select.options].sort(
                (left, right) =>
                    (preference.get(left.value) ?? preference.size) -
                    (preference.get(right.value) ?? preference.size),
            )
            originalRenderOptions()
            decorateOptions()

            window.setTimeout(() => {
                if (!select.isSearching) {
                    if (select.options.length < config.pageSize) {
                        hasMore = false
                    }

                    syncLoadMore()
                }
            })
        }

        const originalSelectOption = select.selectOption.bind(select)

        select.selectOption = (icon) => {
            const current = preferences()
            current.recent = [
                icon,
                ...current.recent.filter((value) => value !== icon),
            ].slice(0, 5)
            persistPreferences(current)
            originalSelectOption(icon)
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
