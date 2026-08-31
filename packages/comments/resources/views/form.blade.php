@if ($user->can('create', PHPinnacle\Comments\Models\Comment::class))
<div class="space-y-4">
    {{ $this->form }}
    <x-filament::button wire:click="create" color="primary">
        {{ __('phpinnacle-comments::forms.create') }}
    </x-filament::button>
</div>
@endif
