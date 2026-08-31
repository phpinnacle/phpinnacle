@if(count($users) > 0)
<div class="fi-simple-header">
    <p>
        {{ __('phpinnacle-cerber::pages.login.as') }}
    </p>
</div>
@foreach ($users as $credentials => $label)
<form action="{{ $route }}" method="POST">
    <div class="fi-ac fi-width-full">
        @csrf

        <input type="hidden" name="panel" value="{{ $panel }}">
        <input type="hidden" name="credentials" value="{{ $credentials }}">

        <x-filament::button type="submit" outlined="true" color="gray">
            {{ "$label ($credentials)" }}
        </x-filament::button>
    </div>
</form>
@endforeach
@endif
