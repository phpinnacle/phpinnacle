<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{ $getAction($getEditorActionName()) }}
</x-dynamic-component>
