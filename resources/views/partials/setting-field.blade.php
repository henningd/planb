@props(['key', 'def', 'modelPrefix' => 'values'])

@php
    $modelKey = "{$modelPrefix}.{$key}";
@endphp

@switch($def['type'])
    @case('bool')
        <div class="flex items-start justify-between gap-6">
            <div class="min-w-0 flex-1">
                <flux:text class="font-medium text-zinc-800 dark:text-zinc-100">{{ __($def['label']) }}</flux:text>
                @if (! empty($def['description']))
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __($def['description']) }}</flux:text>
                @endif
            </div>
            <flux:switch :wire:model.live="$modelKey" />
        </div>
        @break

    @case('int')
        <flux:input
            type="number"
            :wire:model="$modelKey"
            :label="__($def['label'])"
            :description="$def['description'] ? __($def['description']) : null"
            :min="$def['min'] ?? null"
            :max="$def['max'] ?? null"
        />
        @break

    @case('enum')
        <flux:select :wire:model="$modelKey" :label="__($def['label'])" :description="$def['description'] ? __($def['description']) : null">
            @foreach ($def['enum'] as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        @break

    @default
        <flux:input
            type="text"
            :wire:model="$modelKey"
            :label="__($def['label'])"
            :description="$def['description'] ? __($def['description']) : null"
        />
@endswitch
