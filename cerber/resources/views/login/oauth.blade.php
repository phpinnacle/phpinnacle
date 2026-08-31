@if(session('error'))
<x-filament::section>
    <div class="fi-alert bg-danger-50 dark:bg-danger-400/10 text-danger-600 dark:text-danger-400 p-4 rounded-lg">
        <div class="flex gap-3">
            <x-filament::icon
                icon="heroicon-m-x-circle"
                class="h-5 w-5 shrink-0"
            />
            <div class="flex-1">
                <p class="text-sm font-medium">
                    {{ session('error') }}
                </p>
            </div>
        </div>
    </div>
</x-filament::section>
@endif

@if($providers->isNotEmpty())
<div class="fi-simple-header">
    <p>{{ __('phpinnacle-cerber::auth.login.or_login_with') }}</p>
</div>
<div class="flex flex-col gap-4">
    @foreach ($providers as $provider)
    <x-filament::button
        tag="a"
        outlined="true"
        class="w-full"
        :color="$provider->color"
        :href="$provider->url"
        :icon="$provider->icon"
    >
        {{ $provider->label }}
    </x-filament::button>
    @endforeach
</div>
@endif
