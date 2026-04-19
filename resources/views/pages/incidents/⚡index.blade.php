<?php

use App\Enums\IncidentType;
use App\Enums\ReportingObligation;
use App\Models\IncidentReport;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Meldepflichten')] class extends Component {
    public string $title = '';

    public string $type = 'cyber_attack';

    public string $occurred_at = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->occurred_at = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function reports()
    {
        return IncidentReport::with('obligations')->orderByDesc('occurred_at')->get();
    }

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    public function openCreate(): void
    {
        $this->reset(['title', 'notes']);
        $this->type = IncidentType::CyberAttack->value;
        $this->occurred_at = now()->format('Y-m-d\TH:i');
        Flux::modal('incident-create')->show();
    }

    public function create(): void
    {
        if (! $this->hasCompany) {
            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.collect(IncidentType::cases())->pluck('value')->implode(',')],
            'occurred_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $report = DB::transaction(function () use ($validated) {
            $report = IncidentReport::create($validated);

            $obligations = ReportingObligation::applicableFor($validated['type']);
            foreach ($obligations as $obligation) {
                $report->obligations()->create(['obligation' => $obligation->value]);
            }

            return $report;
        });

        Flux::modal('incident-create')->close();

        $this->redirectRoute('incidents.show', ['report' => $report->id], navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Vorfälle & Meldepflichten') }}</flux:heading>
            <flux:subheading>
                {{ __('Wenn ein Vorfall eintritt, starten Sie hier den Meldeprozess. Fristen werden automatisch berechnet.') }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Vorfall melden') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="space-y-3">
        @forelse ($this->reports as $report)
            @php
                $done = $report->obligations->whereNotNull('reported_at')->count();
                $total = $report->obligations->count();
                $overdue = $report->obligations->filter(function ($o) use ($report) {
                    $hours = $o->obligation->deadlineHours();
                    if ($hours === null || $o->reported_at) return false;
                    return now()->diffInHours($report->occurred_at, false) * -1 > $hours;
                })->count();
            @endphp
            <a href="{{ route('incidents.show', $report) }}" wire:navigate
               class="block rounded-xl border border-zinc-200 bg-white p-5 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <flux:badge color="zinc" size="sm">{{ $report->type->label() }}</flux:badge>
                            @if ($overdue > 0)
                                <flux:badge color="rose" size="sm">{{ $overdue }} {{ __('überfällig') }}</flux:badge>
                            @endif
                            <span class="font-medium">{{ $report->title }}</span>
                        </div>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Vorfallzeit') }}: {{ $report->occurred_at->format('d.m.Y H:i') }}
                        </flux:text>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">{{ $done }} / {{ $total }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Meldungen erledigt') }}</div>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 px-5 py-12 text-center dark:border-zinc-700">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Keine Vorfälle erfasst – gut so.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="incident-create" class="max-w-xl">
        <form wire:submit="create" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Vorfall melden') }}</flux:heading>
                <flux:subheading>{{ __('Erfassen Sie die Basisdaten – der Assistent zeigt Ihnen dann die Meldepflichten mit Fristen.') }}</flux:subheading>
            </div>

            <flux:input wire:model="title" :label="__('Kurzbezeichnung')" required placeholder="z. B. Ransomware-Vorfall Buchhaltung" />

            <flux:select wire:model="type" :label="__('Art des Vorfalls')" required>
                @foreach (\App\Enums\IncidentType::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="occurred_at" :label="__('Zeitpunkt der Kenntnisnahme')" type="datetime-local" required />

            <flux:textarea wire:model="notes" :label="__('Kurze Lagebeschreibung')" rows="3" />

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ __('Meldepflichten anzeigen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
