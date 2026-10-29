{{-- $author, $size: lớp Tailwind kích thước. --}}
@if ($url = $author->avatarUrl())
    <img src="{{ $url }}" alt="" class="{{ $size }} shrink-0 rounded-full object-cover">
@else
    <span class="{{ $size }} flex shrink-0 items-center justify-center rounded-full bg-primary-50 font-semibold text-primary-700" aria-hidden="true">{{ $author->initials() }}</span>
@endif
