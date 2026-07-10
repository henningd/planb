<?php

use App\Models\Scenario;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Szenario')] class extends Component {
    public Scenario $scenario;

    public function mount(Scenario $scenario): void
    {
        abort_if($scenario->company_id !== Auth::user()->currentCompany()?->id, 403);

        $this->scenario = $scenario->load('steps');
    }

    /**
     * Gepflegte Alarmketten-Felder als Frage => Antwort (leere ausgelassen).
     *
     * @return array<string, string>
     */
    public function alarmChain(): array
    {
        return array_filter([
            __('Wer erkennt / meldet?') => (string) $this->scenario->alarm_chain_detector,
            __('Wer wird zuerst informiert?') => (string) $this->scenario->alarm_chain_first_contact,
            __('Welche Rolle übernimmt die Lage?') => (string) $this->scenario->alarm_chain_lead_role,
            __('Welche Dienstleister werden informiert?') => (string) $this->scenario->alarm_chain_providers,
            __('Muss die Geschäftsführung informiert werden?') => (string) $this->scenario->alarm_chain_management,
            __('Müssen Behörden / externe Stellen informiert werden?') => (string) $this->scenario->alarm_chain_authorities,
            __('Wer gibt die Kommunikation frei?') => (string) $this->scenario->alarm_chain_comms_approval,
        ], fn (string $value) => $value !== '');
    }
}; ?>

<section class="mx-auto w-full max-w-3xl">
    <div class="mb-2">
        <flux:link :href="route('scenarios.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Szenarien') }}
        </flux:link>
    </div>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div class="min-w-0">
            <flux:heading size="xl">{{ $scenario->name }}</flux:heading>
            <flux:badge color="zinc" size="sm" class="mt-2">{{ $scenario->steps->count() }} {{ __('Schritte') }}</flux:badge>
        </div>
        <flux:button size="sm" variant="filled" icon="pencil" :href="route('scenarios.show', $scenario)" wire:navigate>
            {{ __('Bearbeiten') }}
        </flux:button>
    </div>

    <div class="space-y-6">
        @if ($scenario->description)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="whitespace-pre-line text-zinc-700 dark:text-zinc-200">{{ $scenario->description }}</flux:text>
            </div>
        @endif

        @if ($scenario->trigger)
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="base" class="mb-2">{{ __('Auslöser') }}</flux:heading>
                <flux:text class="whitespace-pre-line text-zinc-700 dark:text-zinc-200">{{ $scenario->trigger }}</flux:text>
            </div>
        @endif

        @php($chain = $this->alarmChain())
        @if (! empty($chain))
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="base" class="mb-3">{{ __('Alarmkette') }}</flux:heading>
                <dl class="space-y-2 text-sm">
                    @foreach ($chain as $question => $answer)
                        <div class="grid gap-1 sm:grid-cols-[18rem_1fr] sm:gap-3">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ $question }}</dt>
                            <dd class="text-zinc-800 dark:text-zinc-200">{{ $answer }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base" class="mb-4">{{ __('Schritte') }}</flux:heading>
            @if ($scenario->steps->isEmpty())
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Für dieses Szenario sind noch keine Schritte hinterlegt.') }}</flux:text>
            @else
                <ol class="space-y-3">
                    @foreach ($scenario->steps as $step)
                        <li class="flex gap-3">
                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $step->sort }}</span>
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $step->title }}</div>
                                @if ($step->description)
                                    <div class="mt-0.5 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-400">{{ $step->description }}</div>
                                @endif
                                @if ($step->responsible)
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span class="font-medium">{{ __('Verantwortlich') }}:</span> {{ $step->responsible }}
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</section>
