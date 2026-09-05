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

                            @if ($comment->edited_at)
                                · {{ __('phpinnacle-comments::forms.edited') }}
                                {{ $comment->edited_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-shrink-0 gap-1">
                        @if ($canCreate)
                        <x-filament::icon-button
                            wire:click="reply('{{ $comment->id }}')"
                            icon="heroicon-s-arrow-uturn-left"
                            color="gray"
                            tooltip="{{ __('phpinnacle-comments::forms.reply') }}"
                        />
                        @endif

                        @if ($user?->can('update', $comment))
                        <x-filament::icon-button
                            wire:click="edit('{{ $comment->id }}')"
                            icon="heroicon-s-pencil-square"
                            color="gray"
                            tooltip="{{ __('phpinnacle-comments::forms.edit') }}"
                        />
                        @endif

                        @if ($user?->can('delete', $comment))
                        <x-filament::icon-button
                            wire:click="delete('{{ $comment->id }}')"
                            icon="heroicon-s-trash"
                            color="danger"
                            tooltip="{{ __('phpinnacle-comments::forms.delete') }}"
                        />
                        @endif
                    </div>
                </div>

                @if ($comment->parent)
                    <div class="border-l-2 border-gray-200 pl-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        <span class="font-medium">{{ $comment->parent->author->name }}</span>
                        — {{ Str::limit(trim(strip_tags($comment->parent->text)), 160) }}
                    </div>
                @endif

                <div
                    class="prose dark:prose-invert [&>*]:mb-2 [&>*]:mt-0 [&>*:last-child]:mb-0 prose-sm text-sm leading-6 text-gray-950 dark:text-white">
                    {!! Filament\Forms\Components\RichEditor\RichContentRenderer::make($comment->text)->mentions([$mentionProvider])->toHtml() !!}
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
