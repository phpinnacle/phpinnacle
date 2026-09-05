<div
    @if ($searchable)
        x-data="{ search: '' }"
    @endif
    class="space-y-6"
>
    @if ($items->isNotEmpty() && $searchable)
        <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
            <x-filament::input
                type="search"
                x-model.debounce.150ms="search"
                aria-label="{{ __('phpinnacle-chronica::messages.search.placeholder') }}"
                placeholder="{{ __('phpinnacle-chronica::messages.search.placeholder') }}"
            />
        </x-filament::input.wrapper>
    @endif

    @if ($items->isNotEmpty())
        <div class="relative">
            <div class="absolute inset-y-5 left-5 w-px bg-gray-200 dark:bg-white/10" aria-hidden="true"></div>

            <div @class([
                'space-y-4' => $compact,
                'space-y-6' => ! $compact,
            ])>
                @foreach ($items as $item)
                    <article
                        wire:key="activity-{{ $item['id'] }}"
                        @if ($searchable)
                            x-show="search === '' || @js($item['search']).includes(search.toLowerCase())"
                        @endif
                        class="relative grid grid-cols-[2.5rem_minmax(0,1fr)] gap-3"
                    >
                        <div @class([
                            'relative z-10 flex size-10 items-center justify-center rounded-full bg-white ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/15',
                            'text-gray-500 dark:text-gray-400' => $item['color'] === null,
                            'text-success-600 dark:text-success-400' => $item['color'] === 'success',
                            'text-warning-600 dark:text-warning-400' => $item['color'] === 'warning',
                            'text-danger-600 dark:text-danger-400' => $item['color'] === 'danger',
                        ])>
                            <x-filament::icon :icon="$item['icon']" class="size-5" />
                        </div>

                        <div class="min-w-0 pt-0.5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-gray-950 dark:text-white">
                                            {{ $item['model'] }}
                                        </p>
                                        <span @class([
                                            'inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                            'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10' => $item['color'] === null,
                                            'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20' => $item['color'] === 'success',
                                            'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20' => $item['color'] === 'warning',
                                            'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/20' => $item['color'] === 'danger',
                                        ])>
                                            {{ $item['description'] }}
                                        </span>
                                    </div>

                                    <p class="mt-1 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                                        <x-filament::icon icon="heroicon-m-user" class="size-4 shrink-0" />
                                        <span class="truncate">{{ $item['causer'] }}</span>
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if ($item['revertable'] && $revertAction)
                                        {{ $revertAction(['activity' => $item['id']]) }}
                                    @endif

                                    <time
                                        datetime="{{ $item['datetime'] }}"
                                        title="{{ $item['datetime'] }}"
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $item['date'] }}
                                    </time>
                                </div>
                            </div>

                            @if (count($item['changes']))
                                <details class="group mt-3 overflow-hidden rounded-xl bg-white text-sm ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2 font-medium text-gray-700 select-none [&::-webkit-details-marker]:hidden dark:text-gray-300">
                                        <span>{{ __('phpinnacle-chronica::messages.changes.label') }}</span>
                                        <span class="flex items-center gap-2">
                                            <span class="rounded-md bg-white px-1.5 py-0.5 text-xs text-gray-500 ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">
                                                {{ count($item['changes']) }}
                                            </span>
                                            <x-filament::icon
                                                icon="heroicon-m-chevron-down"
                                                class="size-4 text-gray-400 transition-transform group-open:rotate-180 dark:text-gray-500"
                                            />
                                        </span>
                                    </summary>

                                    <dl class="divide-y divide-gray-200 border-t border-gray-200 dark:divide-white/10 dark:border-white/10">
                                        @foreach ($item['changes'] as $change)
                                            <div @class([
                                                'grid gap-2 px-3 sm:grid-cols-[minmax(8rem,0.75fr)_minmax(0,2fr)] sm:items-start',
                                                'py-2' => $compact,
                                                'py-3' => ! $compact,
                                            ])>
                                                <dt class="font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $change['label'] }}
                                                </dt>

                                                @if ($verbose && $change['old'] !== null)
                                                    <dd class="grid min-w-0 grid-cols-[minmax(0,1fr)_1rem_minmax(0,1fr)] items-start gap-2">
                                                        <del class="break-words text-gray-500 decoration-gray-400 dark:text-gray-400">
                                                            <x-phpinnacle-chronica::value :value="$change['old']" />
                                                        </del>
                                                        <x-filament::icon
                                                            icon="heroicon-m-arrow-right"
                                                            class="mt-0.5 size-4 text-gray-400 dark:text-gray-500"
                                                        />
                                                        <ins class="break-words font-medium text-gray-950 no-underline dark:text-white">
                                                            <x-phpinnacle-chronica::value :value="$change['new']" />
                                                        </ins>
                                                    </dd>
                                                @else
                                                    <dd class="break-words font-medium text-gray-950 dark:text-white">
                                                        <x-phpinnacle-chronica::value :value="$change['new']" />
                                                    </dd>
                                                @endif
                                            </div>
                                        @endforeach
                                    </dl>
                                </details>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @else
        <div class="flex flex-col items-center gap-3 py-12 text-center">
            <x-filament::icon
                :icon="config('phpinnacle-chronica.icon', 'heroicon-o-arrows-up-down')"
                class="size-12 text-gray-400 dark:text-gray-500"
            />
            <div>
                <p class="font-medium text-gray-950 dark:text-white">
                    {{ __('phpinnacle-chronica::messages.empty.heading') }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('phpinnacle-chronica::messages.empty.description') }}
                </p>
            </div>
        </div>
    @endif
</div>
