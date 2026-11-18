@if(count($users) > 0)
<div class="fi-cerber-developer-logins" style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
    <header class="fi-simple-header">
        <h2 class="fi-simple-header-heading">
            {{ __('phpinnacle-cerber::pages.login.as') }}
        </h2>
    </header>

    @foreach ($users as $credentials => $label)
        <form action="{{ $route }}" method="POST">
            <div class="fi-ac fi-width-full">
                @csrf

                <input type="hidden" name="panel" value="{{ $panel }}">
                <input type="hidden" name="credentials" value="{{ $credentials }}">

                <x-filament::button type="submit" color="primary">
                    {{ "$label ($credentials)" }}
                </x-filament::button>
            </div>
        </form>
    @endforeach
</div>
@endif
