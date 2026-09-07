@php($branding = app(\App\Support\Mail\MailBranding::class)->resolve())
<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="$branding['frontend_url']">
            {{ $branding['app_name'] }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ $branding['app_name'] }}. @lang('All rights reserved.')
            {{ $branding['frontend_url'] }}
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
