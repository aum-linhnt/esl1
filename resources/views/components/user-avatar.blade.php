@props([
    'user' => null,
    'size' => 'md',
    'showOnline' => false,
])

@php
    $name = is_object($user) ? ($user->name ?? 'User') : ($user['name'] ?? 'User');
    $avatar = is_object($user) ? ($user->avatar ?? null) : ($user['avatar'] ?? null);
    $role = is_object($user) ? ($user->role ?? 'student') : ($user['role'] ?? 'student');

    // Initials (up to 2 letters)
    $words = explode(' ', trim($name));
    $initials = '';
    if (count($words) >= 2) {
        $initials = mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1);
    } else {
        $initials = mb_substr($name, 0, 2);
    }
    $initials = mb_strtoupper($initials);

    $dimension = match($size) {
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'lg' => 'w-12 h-12 text-base',
        'xl' => 'w-16 h-16 text-xl',
        default => 'w-10 h-10 text-sm',
    };

    $roleBg = match(strtolower($role)) {
        'admin' => 'from-rose-500 to-red-600',
        'teacher' => 'from-amber-500 to-orange-600',
        default => 'from-indigo-600 to-blue-600',
    };
@endphp

<div {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center flex-shrink-0 {$dimension}"]) }}>
    @if(!empty($avatar))
        <img src="{{ $avatar }}" 
             alt="{{ $name }}" 
             class="w-full h-full object-cover rounded-xl border border-slate-700/60 shadow-sm"
             loading="lazy">
    @else
        <div class="w-full h-full rounded-xl bg-gradient-to-br {{ $roleBg }} text-white font-bold flex items-center justify-center border border-white/10 shadow-sm">
            {{ $initials }}
        </div>
    @endif

    @if($showOnline)
        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-[#080d1a]"></span>
    @endif
</div>
