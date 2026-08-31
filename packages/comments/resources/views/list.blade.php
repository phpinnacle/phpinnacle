@if (count($comments))
<div class="grid gap-4">
    @foreach ($comments as $comment)
    <div
        class="fi-in-repeatable-item block rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="flex space-x-3">
            <div class="pr-2">
                <x-filament-panels::avatar.user size="md" :user="$comment->author"/>
            </div>
            <div class="flex-grow space-y-2 pt-[6px]">
                <div class="flex space-x-2 items-center justify-between">
                    <div class="flex space-x-2 items-center">
                        <div class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ $comment->author->name }}
                        </div>
                        <div class="text-xs font-medium text-gray-400 dark:text-gray-500">
                            {{ $comment->created_at->diffForHumans() }}
                        </div>
                    </div>

                    @if (auth()->user()->can('delete', $comment))
                    <div class="flex-shrink-0">
                        <x-filament::icon-button
                            wire:click="delete('{{ $comment->id }}')"
                            icon="heroicon-s-trash"
                            color="danger"
                            tooltip="{{ __('phpinnacle-comments::forms.delete') }}"
                        />
                    </div>
                    @endif
                </div>

                <div
                    class="prose dark:prose-invert [&>*]:mb-2 [&>*]:mt-0 [&>*:last-child]:mb-0 prose-sm text-sm leading-6 text-gray-950 dark:text-white">
                    {{ Str::of($comment->text)->toHtmlString() }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="flex-grow flex flex-col items-center justify-center space-y-4">
    <x-filament::icon
        icon="heroicon-s-chat-bubble-left-right"
        class="h-12 w-12 text-gray-400 dark:text-gray-500"
    />
    <div class="text-sm text-gray-400 dark:text-gray-500">
        {{ __('phpinnacle-comments::forms.empty') }}
    </div>
</div>
@endif
