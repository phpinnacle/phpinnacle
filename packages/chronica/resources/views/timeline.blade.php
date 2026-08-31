<div
    @if ($searchable)
        x-data="{ search: '' }"
    @endif
    class="space-y-4"
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

    @forelse ($items as $item)
        <article
            @if ($searchable)
                x-show="search === '' || @js($item['search']).includes(search.toLowerCase())"
            @endif
            @class([
                'rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10',
                'p-3' => $compact,
                'p-4' => ! $compact,
            ])
        >
            <div class="flex items-start gap-3">
                <div @class([
                    'mt-0.5 rounded-full p-2',
                    'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $item['color'] === null,
                    'bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400' => $item['color'] === 'success',
                    'bg-warning-50 text-warning-600 dark:bg-warning-400/10 dark:text-warning-400' => $item['color'] === 'warning',
                    'bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400' => $item['color'] === 'danger',
                ])>
                    <x-filament::icon :icon="$item['icon']" class="size-5" />
                </div>

                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-medium text-gray-950 dark:text-white">
                                {{ $item['description'] }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $item['model'] }} · {{ $item['causer'] }}
                            </p>
                        </div>

                        <time
                            datetime="{{ $item['datetime'] }}"
                            title="{{ $item['datetime'] }}"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ $item['date'] }}
                        </time>
                    </div>

                    @if (count($item['changes']))
                        <dl class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                            @foreach ($item['changes'] as $change)
                                <div class="grid gap-1 py-2 sm:grid-cols-3">
                                    <dt class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ $change['label'] }}
                                    </dt>
                                    @if ($verbose && $change['old'] !== null)
                                        <dd class="break-words text-gray-500 line-through dark:text-gray-400">
                                            {{ $change['old'] }}
                                        </dd>
                                    @endif
                                    <dd class="break-words text-gray-950 dark:text-white">
                                        {{ $change['new'] }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </article>
    @empty
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
    @endforelse
</div>
