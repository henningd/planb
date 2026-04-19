<?php

use App\Models\IncidentReport;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Vorfall')] class extends Component {
    public IncidentReport $report;

    public function mount(IncidentReport $report): void
    {
        abort_if($report->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->report = $report->load('obligations');
    }

    public function markReported(int $obligationId): void
    {
        $obligation = $this->report->obligations->firstWhere('id', $obligationId);
        abort_unless($obligation, 404);

        if ($obligation->reported_at) {
            $obligation->update(['reported_at' => null]);
        } else {
            $obligation->update(['reported_at' => now()]);
        }

        $this->report->load('obligations');

        Flux::toast(text: __('Meldestatus aktualisiert.'));
    }

    public function deadlineFor($obligation): ?\Carbon\CarbonInterface
    {
        $hours = $obligation->obligation->deadlineHours();
        if ($hours === null) {
            return null;
        }

        return $this->report->occurred_at->copy()->addHours($hours);
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="mb-2">
        <flux:link :href="route('incidents.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Vorfälle') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center gap-2">
            <flux:badge color="zinc">{{ $report->type->label() }}</flux:badge>
        </div>
        <flux:heading size="xl" class="mt-2">{{ $report->title }}</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Kenntnis seit') }}: {{ $report->occurred_at->format('d.m.Y H:i') }}
        </flux:text>
        @if ($report->notes)
            <flux:text class="mt-4 text-sm">{{ $report->notes }}</flux:text>
        @endif
    </div>

    <div class="space-y-3">
        @foreach ($report->obligations as $obligation)
            @php
                $deadline = $this->deadlineFor($obligation);
                $isReported = $obligation->reported_at !== null;
                $isOverdue = $deadline && ! $isReported && $deadline->isPast();
                $tight = $deadline && ! $isReported && ! $isOverdue && $deadline->diffInHours(now()) <= 12;
            @endphp
            <div @class([
                'rounded-xl border p-5 bg-white dark:bg-zinc-900',
                'border-emerald-300 dark:border-emerald-700' => $isReported,
                'border-rose-400 dark:border-rose-700' => $isOverdue,
                'border-amber-400 dark:border-amber-700' => $tight,
                'border-zinc-200 dark:border-zinc-700' => ! $isReported && ! $isOverdue && ! $tight,
            ])>
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <flux:heading size="base">{{ $obligation->obligation->label() }}</flux:heading>
                            @if ($isReported)
                                <flux:badge color="emerald" size="sm">{{ __('Erledigt') }}</flux:badge>
                            @elseif ($isOverdue)
                                <flux:badge color="rose" size="sm">{{ __('Überfällig') }}</flux:badge>
                            @elseif ($tight)
                                <flux:badge color="amber" size="sm">{{ __('Dringend') }}</flux:badge>
                            @endif
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $obligation->obligation->description() }}
                        </flux:text>

                        @if ($deadline)
                            <div class="mt-3 flex items-center gap-2 text-sm">
                                <flux:icon.clock class="h-4 w-4 text-zinc-400" />
                                <span class="{{ $isOverdue ? 'text-rose-700 dark:text-rose-400 font-medium' : '' }}">
                                    {{ __('Frist') }}: {{ $deadline->format('d.m.Y H:i') }}
                                    @if (! $isReported)
                                        ({{ $deadline->diffForHumans() }})
                                    @endif
                                </span>
                            </div>
                        @else
                            <div class="mt-3 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                <flux:icon.clock class="h-4 w-4 text-zinc-400" />
                                <span>{{ __('Frist: unverzüglich') }}</span>
                            </div>
                        @endif

                        @if ($isReported)
                            <flux:text class="mt-2 text-sm text-emerald-700 dark:text-emerald-400">
                                ✓ {{ __('Gemeldet am') }} {{ $obligation->reported_at->format('d.m.Y H:i') }}
                            </flux:text>
                        @endif
                    </div>

                    <flux:button
                        size="sm"
                        :variant="$isReported ? 'ghost' : 'primary'"
                        wire:click="markReported({{ $obligation->id }})"
                    >
                        {{ $isReported ? __('Zurücksetzen') : __('Als gemeldet markieren') }}
                    </flux:button>
                </div>
            </div>
        @endforeach
    </div>
</section>
