@if ($user)
<div class="flex justify-end">
    <x-filament::button wire:click="toggleSubscription" color="gray" size="sm">
        {{ __($subscribed ? 'phpinnacle-comments::forms.unsubscribe' : 'phpinnacle-comments::forms.subscribe') }}
    </x-filament::button>
</div>
@endif

@if ($canCreate || $editingComment)
<div class="space-y-4">
    @if ($editingComment)
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('phpinnacle-comments::forms.editing') }}
        </div>
    @elseif ($replyingTo)
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('phpinnacle-comments::forms.replying', ['author' => $replyingTo->author->name]) }}
        </div>
    @endif

    {{ $this->form }}
    <div class="flex gap-2">
        <x-filament::button wire:click="{{ $editingComment ? 'update' : 'create' }}" color="primary">
            {{ __($editingComment ? 'phpinnacle-comments::forms.save' : 'phpinnacle-comments::forms.create') }}
        </x-filament::button>

        @if ($editingComment || $replyingTo)
            <x-filament::button wire:click="cancel" color="gray">
                {{ __('phpinnacle-comments::forms.cancel') }}
            </x-filament::button>
        @endif
    </div>
</div>
@endif
