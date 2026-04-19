@props([
    'sidebar' => false,
])

@php($brandName = config('app.name', 'PlanB'))

@if($sidebar)
    <flux:sidebar.brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-indigo-600 to-blue-600 text-white shadow-sm">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-indigo-600 to-blue-600 text-white shadow-sm">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:brand>
@endif
