<?php

use App\Models\AuthorityContact;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Behörde / Meldestelle')] class extends Component {
    public AuthorityContact $authorityContact;

    public function mount(AuthorityContact $authorityContact): void
    {
        abort_unless(Auth::user()?->currentCompany(), 403);

        $this->authorityContact = $authorityContact->load(['responsibleRole', 'communicationTemplate']);
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('authority-contacts.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Behörden & Meldestellen') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <flux:heading size="xl">{{ $authorityContact->name }}</flux:heading>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <flux:badge :color="$authorityContact->type->color()" size="sm">{{ $authorityContact->type->label() }}</flux:badge>
                    @if ($authorityContact->deadline)
                        <flux:badge color="amber" size="sm" icon="clock">{{ $authorityContact->deadline }}</flux:badge>
                    @endif
                </div>
            </div>
        </div>

        @if ($authorityContact->occasion)
            <flux:text class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                <span class="font-medium">{{ __('Anlass / Meldepflicht:') }}</span> {{ $authorityContact->occasion }}
            </flux:text>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base" class="mb-4">{{ __('Kontakt') }}</flux:heading>
            <dl class="space-y-3 text-sm">
                @if ($authorityContact->phone)
                    <div>
                        <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Telefon') }}</dt>
                        <dd><a href="tel:{{ $authorityContact->phone }}" class="font-medium hover:underline">{{ $authorityContact->phone }}</a></dd>
                    </div>
                @endif
                @if ($authorityContact->email)
                    <div>
                        <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('E-Mail') }}</dt>
                        <dd><a href="mailto:{{ $authorityContact->email }}" class="font-medium hover:underline">{{ $authorityContact->email }}</a></dd>
                    </div>
                @endif
                @if ($authorityContact->contact_way)
                    <div>
                        <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Kontaktweg') }}</dt>
                        <dd>
                            @if (\Illuminate\Support\Str::startsWith($authorityContact->contact_way, ['http://', 'https://']))
                                <a href="{{ $authorityContact->contact_way }}" target="_blank" rel="noopener noreferrer" class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ $authorityContact->contact_way }}</a>
                            @else
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $authorityContact->contact_way }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($authorityContact->address)
                    <div>
                        <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Anschrift') }}</dt>
                        <dd class="text-zinc-700 dark:text-zinc-200">{{ $authorityContact->address }}</dd>
                    </div>
                @endif
                @if ($authorityContact->contact_name)
                    <div>
                        <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Ansprechpartner') }}</dt>
                        <dd class="text-zinc-700 dark:text-zinc-200">{{ $authorityContact->contact_name }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base" class="mb-4">{{ __('Zuständigkeit & Vorlage') }}</flux:heading>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Zuständige interne Rolle') }}</dt>
                    <dd class="text-zinc-700 dark:text-zinc-200">{{ $authorityContact->responsibleRole?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Passende Kommunikationsvorlage') }}</dt>
                    <dd>
                        @if ($authorityContact->communicationTemplate)
                            <flux:link :href="route('communication-templates.index')" wire:navigate>{{ $authorityContact->communicationTemplate->name }}</flux:link>
                        @else
                            <span class="text-zinc-700 dark:text-zinc-200">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    @if ($authorityContact->notes)
        <div class="mt-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="base" class="mb-2">{{ __('Notizen') }}</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">{{ $authorityContact->notes }}</flux:text>
        </div>
    @endif
</section>
