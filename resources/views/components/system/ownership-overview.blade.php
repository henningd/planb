@props([
    'groups' => [],
    'labelGetter' => null,
    'unassigned' => null,
    'unassignedLabel' => 'Ohne Zuständigkeit',
    'emptyText' => '— nicht besetzt —',
    'emptyHint' => 'Mindestens eine Hauptperson empfohlen.',
    'showValidation' => true,
])

@php
    $getLabel = $labelGetter ?? function ($item) {
        if (method_exists($item, 'fullName')) {
            return $item->fullName();
        }

        return $item->name ?? '–';
    };
    $owner = $groups[App\Enums\SystemOwnership::Owner->value] ?? null;
    $operator = $groups[App\Enums\SystemOwnership::Operator->value] ?? null;
    $ownerPrimary = $owner ? $owner['primaries']->count() : 0;
    $operatorPrimary = $operator ? $operator['primaries']->count() : 0;
@endphp

<div class="space-y-4">
    @if ($showValidation && ($ownerPrimary === 0 || $operatorPrimary === 0 || $ownerPrimary > 1))
        <div class="flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm dark:border-rose-900 dark:bg-rose-950/40">
            <flux:icon.exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
            <div class="space-y-0.5 text-rose-800 dark:text-rose-200">
                @if ($ownerPrimary === 0)
                    <div>{{ __('Kein „System-Eigentümer" als Hauptperson – genau eine Hauptperson wird empfohlen.') }}</div>
                @elseif ($ownerPrimary > 1)
                    <div>{{ __(':n Personen mit „System-Eigentümer" als Hauptperson – klassisch nur eine.', ['n' => $ownerPrimary]) }}</div>
                @endif
                @if ($operatorPrimary === 0)
                    <div>{{ __('Kein „Administrator / Operator" als Hauptperson – mindestens eine empfohlen.') }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="grid gap-3 md:grid-cols-3">
        @foreach (App\Enums\SystemOwnership::ordered() as $kind)
            @php($bucket = $groups[$kind->value] ?? null)
            <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:icon :name="$kind->icon()" variant="mini" class="text-zinc-500 dark:text-zinc-400" />
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $kind->label() }}</span>
                        </div>
                        <flux:text class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $kind->description() }}</flux:text>
                    </div>
                </div>

                @php($primaries = $bucket['primaries'] ?? collect())
                @php($deputies = $bucket['deputies'] ?? collect())

                @if ($primaries->isEmpty() && $deputies->isEmpty())
                    <div class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __($emptyText) }}</div>
                @else
                    @if ($primaries->isNotEmpty())
                        <ul class="space-y-1 text-sm">
                            @foreach ($primaries as $item)
                                <li class="flex items-center gap-2 text-zinc-800 dark:text-zinc-100">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span class="truncate">{{ $getLabel($item) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($deputies->isNotEmpty())
                        <div class="mt-2 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                            <div class="mb-1 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Vertretung') }}</div>
                            <ul class="space-y-1 text-sm">
                                @foreach ($deputies as $item)
                                    <li class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                                        <span class="truncate">{{ $getLabel($item) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>

    @if ($unassigned && $unassigned->isNotEmpty())
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/30">
            <div class="mb-2 flex items-center gap-2 text-amber-800 dark:text-amber-200">
                <flux:icon.exclamation-triangle variant="mini" class="text-amber-600 dark:text-amber-400" />
                <span class="font-medium">{{ __($unassignedLabel) }}</span>
            </div>
            <ul class="ml-6 list-disc space-y-0.5 text-amber-900 dark:text-amber-100">
                @foreach ($unassigned as $item)
                    <li>{{ $getLabel($item) }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
