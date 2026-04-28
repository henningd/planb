@props([
    'sidebar' => false,
])

@php
    $company = auth()->check() ? auth()->user()->currentCompany() : null;
    $brandName = $company?->brandName() ?? config('app.name', 'PlanB');
    $logoUrl = $company?->logoUrl();
    $brandColor = $company?->brandColor() ?? '#4f46e5';
    $logoBgStyle = $logoUrl ? null : 'background: linear-gradient(135deg, '.$brandColor.', #2563eb)';
@endphp

@if($sidebar)
    <flux:sidebar.brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md text-white shadow-sm" :style="$logoBgStyle">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="size-full object-contain bg-white p-0.5" />
            @else
                <x-app-logo-icon class="size-5" />
            @endif
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md text-white shadow-sm" :style="$logoBgStyle">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="size-full object-contain bg-white p-0.5" />
            @else
                <x-app-logo-icon class="size-5" />
            @endif
        </x-slot>
    </flux:brand>
@endif
