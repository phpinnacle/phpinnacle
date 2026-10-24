@php
    $field = $getContainer()->getParentComponent();
@endphp

<x-phpinnacle-eidos::mapping-item :compact="$field->isCompact()" class="relative" :item="$item" :presentation="$presentation" :warning="$field->getSourceWarnings()[$item['id']] ?? null" />
