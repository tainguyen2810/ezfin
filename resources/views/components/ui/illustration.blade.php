{{-- Hình minh họa trang trí (không chứa số liệu). name: card, piggy, house, bag, phone. Luôn aria-hidden. --}}
@props(['name'])
@switch($name)
    @case('card')
        <svg viewBox="0 0 120 90" fill="none" aria-hidden="true" {{ $attributes }}>
            <rect x="30" y="14" width="78" height="50" rx="7" transform="rotate(-12 30 14)" fill="#93c5fd" />
            <rect x="14" y="30" width="82" height="52" rx="7" fill="#1d4ed8" />
            <rect x="14" y="30" width="82" height="52" rx="7" fill="url(#ill-card)" />
            <rect x="24" y="44" width="14" height="11" rx="2" fill="#fde68a" />
            <rect x="24" y="66" width="40" height="4" rx="2" fill="#bfdbfe" />
            <circle cx="78" cy="68" r="6" fill="#f87171" fill-opacity=".9" />
            <circle cx="86" cy="68" r="6" fill="#fbbf24" fill-opacity=".9" />
            <defs><linearGradient id="ill-card" x1="14" y1="30" x2="96" y2="82"><stop stop-color="#0b1f3a" stop-opacity=".6" /><stop offset="1" stop-color="#2563eb" stop-opacity="0" /></linearGradient></defs>
        </svg>
        @break
    @case('piggy')
        <svg viewBox="0 0 120 90" fill="none" aria-hidden="true" {{ $attributes }}>
            <circle cx="62" cy="14" r="9" fill="#fbbf24" /><circle cx="62" cy="14" r="5" fill="#f59e0b" />
            <ellipse cx="58" cy="54" rx="34" ry="26" fill="#f9a8d4" />
            <path d="M40 32l-4-12 14 7z" fill="#f472b6" />
            <ellipse cx="90" cy="54" rx="8" ry="9" fill="#f472b6" />
            <circle cx="88" cy="52" r="1.6" fill="#831843" /><circle cx="92" cy="56" r="1.6" fill="#831843" />
            <circle cx="74" cy="44" r="2.4" fill="#831843" />
            <rect x="52" y="30" width="16" height="3" rx="1.5" fill="#be185d" />
            <rect x="36" y="74" width="9" height="12" rx="3" fill="#f472b6" /><rect x="66" y="74" width="9" height="12" rx="3" fill="#f472b6" />
            <path d="M24 50c-6 0-8 6-4 8" stroke="#f472b6" stroke-width="3" stroke-linecap="round" />
        </svg>
        @break
    @case('house')
        <svg viewBox="0 0 120 90" fill="none" aria-hidden="true" {{ $attributes }}>
            <circle cx="98" cy="62" r="13" fill="#86efac" /><rect x="96" y="68" width="4" height="18" rx="2" fill="#15803d" />
            <path d="M18 44L56 14l38 30v42H18z" fill="#fef3c7" />
            <path d="M12 46L56 10l44 36" stroke="#b45309" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" />
            <rect x="48" y="58" width="16" height="28" rx="2" fill="#2563eb" />
            <rect x="26" y="52" width="14" height="12" rx="2" fill="#93c5fd" /><rect x="72" y="52" width="14" height="12" rx="2" fill="#93c5fd" />
            <rect x="8" y="84" width="104" height="4" rx="2" fill="#4ade80" />
        </svg>
        @break
    @case('bag')
        <svg viewBox="0 0 120 90" fill="none" aria-hidden="true" {{ $attributes }}>
            <path d="M46 30v-6a14 14 0 0128 0v6" stroke="#7c3aed" stroke-width="5" stroke-linecap="round" />
            <path d="M30 30h60l6 54H24z" fill="#a78bfa" />
            <path d="M30 30h60l2 18H28z" fill="#8b5cf6" />
            <circle cx="88" cy="66" r="15" fill="#fbbf24" /><circle cx="88" cy="66" r="10" fill="#f59e0b" />
            <rect x="85" y="60" width="6" height="12" rx="2" fill="#fde68a" />
        </svg>
        @break
    @case('phone')
        <svg viewBox="0 0 160 300" fill="none" aria-hidden="true" {{ $attributes }}>
            <rect x="4" y="4" width="152" height="292" rx="26" fill="#0b1f3a" />
            <rect x="12" y="12" width="136" height="276" rx="20" fill="#fff" />
            <rect x="58" y="18" width="44" height="8" rx="4" fill="#0b1f3a" />
            <rect x="24" y="40" width="40" height="8" rx="4" fill="#2563eb" />
            <rect x="24" y="58" width="112" height="56" rx="10" fill="#eff6ff" />
            <rect x="34" y="68" width="48" height="6" rx="3" fill="#94a8c8" />
            <rect x="34" y="82" width="72" height="10" rx="5" fill="#0b1f3a" />
            <path d="M34 106c14-6 22 0 34-4s20-10 30-6 18-4 28-8" stroke="#22c55e" stroke-width="3" stroke-linecap="round" />
            @foreach ([[30, '#dbeafe', '#2563eb'], [70, '#dcfce7', '#16a34a'], [110, '#fee2e2', '#dc2626']] as [$x, $soft, $strong])
                <rect x="{{ $x - 4 }}" y="126" width="28" height="28" rx="8" fill="{{ $soft }}" /><rect x="{{ $x + 4 }}" y="134" width="12" height="12" rx="3" fill="{{ $strong }}" />
            @endforeach
            @foreach ([168, 200, 232] as $y)
                <rect x="24" y="{{ $y }}" width="24" height="24" rx="7" fill="#f1f5f9" />
                <rect x="56" y="{{ $y + 4 }}" width="44" height="6" rx="3" fill="#0b1f3a" fill-opacity=".8" />
                <rect x="56" y="{{ $y + 14 }}" width="30" height="5" rx="2.5" fill="#94a8c8" />
                <rect x="108" y="{{ $y + 8 }}" width="28" height="6" rx="3" fill="{{ $loop->first ? '#16a34a' : '#dc2626' }}" fill-opacity=".8" />
            @endforeach
            <rect x="24" y="268" width="112" height="4" rx="2" fill="#e2e8f0" />
        </svg>
        @break
@endswitch
