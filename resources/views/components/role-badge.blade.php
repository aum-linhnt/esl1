@props([
    'role' => 'student',
    'size' => 'sm',
])

@php
    $roleValue = is_object($role) && enum_exists(get_class($role)) 
        ? $role->value 
        : (is_object($role) && isset($role->role) ? $role->role : (string) $role);

    $roleValue = strtolower($roleValue);

    $config = match($roleValue) {
        'admin' => [
            'label' => 'Quản trị viên',
            'classes' => 'bg-rose-500/10 text-rose-300 border-rose-500/20 shadow-rose-500/5',
            'dot' => 'bg-rose-400',
        ],
        'teacher' => [
            'label' => 'Giảng viên',
            'classes' => 'bg-amber-500/10 text-amber-300 border-amber-500/20 shadow-amber-500/5',
            'dot' => 'bg-amber-400',
        ],
        default => [
            'label' => 'Học viên',
            'classes' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20 shadow-emerald-500/5',
            'dot' => 'bg-emerald-400',
        ],
    };

    $sizeClasses = match($size) {
        'xs' => 'text-[9px] px-1.5 py-0.5 gap-1',
        'md' => 'text-xs px-2.5 py-1 gap-1.5',
        default => 'text-[10px] px-2 py-0.5 gap-1',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-full border shadow-sm {$config['classes']} {$sizeClasses}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }}"></span>
    <span>{{ $config['label'] }}</span>
</span>
