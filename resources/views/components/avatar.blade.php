@props(['user', 'size' => 'h-9 w-9', 'text' => 'text-sm', 'ring' => ''])

@if ($user->photo_url)
    <img src="{{ $user->photo_url }}" alt="{{ $user->name }}"
         class="{{ $size }} {{ $ring }} inline-block shrink-0 rounded-full object-cover">
@else
    <span class="{{ $size }} {{ $text }} {{ $ring }} flex shrink-0 items-center justify-center rounded-full bg-[#f6c945] font-bold text-[#0a1f55]">
        {{ $user->initials }}
    </span>
@endif