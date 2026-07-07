<?php

use App\Models\Lead;
use App\Support\Settings\SystemSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use Livewire\Component;

/**
 * Öffentliches Kontaktformular der Kommunen-Seite. Speichert die Anfrage als
 * Lead (source=kommunen; Nachricht + Telefon im answers-JSON) und benachrichtigt
 * die Plattform-Kontaktadresse per E-Mail. Honeypot + IP-Rate-Limit gegen Spam.
 */
new class extends Component {
    /** Lead-Quelle: 'kommunen' (Kommunen-Seite) oder 'web' (Startseite). */
    public string $source = 'kommunen';

    public string $contactName = '';

    public string $organization = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    /** Honeypot: bleibt bei Menschen leer — Bots füllen es aus. */
    public string $website = '';

    public bool $sent = false;

    public function submit(): void
    {
        // Honeypot: von Bots ausgefülltes Feld → still verwerfen.
        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        // Rate-Limit je IP: schützt vor Formular-Spam, ohne Menschen zu stören.
        $key = 'kommunen-kontakt:'.Request::ip();
        if (! RateLimiter::attempt($key, maxAttempts: 5, callback: fn () => true, decaySeconds: 3600)) {
            $this->addError('message', __('Zu viele Anfragen — bitte versuchen Sie es später erneut.'));

            return;
        }

        $validated = $this->validate([
            'contactName' => ['required', 'string', 'max:255'],
            'organization' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:4000'],
        ], attributes: [
            'contactName' => __('Name'),
            'organization' => $this->source === 'web' ? __('Unternehmen / Organisation') : __('Kommune / Organisation'),
            'email' => __('E-Mail-Adresse'),
            'phone' => __('Telefon'),
            'message' => __('Nachricht'),
        ]);

        $lead = Lead::create([
            'email' => $validated['email'],
            'company_name' => $validated['organization'],
            'contact_name' => $validated['contactName'],
            'source' => $this->source === 'web' ? 'web' : 'kommunen',
            'answers' => [
                'message' => $validated['message'],
                'phone' => $validated['phone'] ?? null,
            ],
            'consent_marketing' => false,
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
        ]);

        // Interne Benachrichtigung — best-effort: ein Mail-Fehler darf die
        // Anfrage nie verlieren (der Lead ist bereits gespeichert).
        rescue(function () use ($lead) {
            $to = (string) SystemSetting::get('platform_contact_email');
            if ($to === '') {
                return;
            }

            Mail::to($to)->send(new \App\Mail\KommunenInquiryReceived($lead));
        }, report: false);

        $this->sent = true;
    }
}; ?>

<div>
    @if ($sent)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-8 text-center">
            <svg class="mx-auto h-10 w-10 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <h3 class="mt-4 text-xl font-semibold text-emerald-900">{{ __('Vielen Dank für Ihre Anfrage!') }}</h3>
            <p class="mt-2 text-emerald-800">
                {{ __('Wir melden uns zeitnah bei Ihnen — in der Regel innerhalb eines Werktags.') }}
            </p>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 text-left shadow-sm lg:p-8">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="contactName" :label="__('Name')" required />
                <flux:input wire:model="organization" :label="$source === 'web' ? __('Unternehmen / Organisation') : __('Kommune / Organisation')" :placeholder="$source === 'web' ? __('z. B. Beispiel GmbH') : __('z. B. Stadt Musterstadt')" required />
                <flux:input type="email" wire:model="email" :label="__('Dienstliche E-Mail')" required />
                <flux:input type="tel" wire:model="phone" :label="__('Telefon (optional)')" />
            </div>

            <flux:textarea wire:model="message" rows="4" :label="__('Ihre Nachricht')" :placeholder="$source === 'web' ? __('z. B. Bitte um eine Demo für unser Unternehmen mit 2 Standorten …') : __('z. B. Bitte um ein Angebot für den Kommunal-Tarif für unsere Verwaltung mit 3 Liegenschaften …')" required />

            {{-- Honeypot — für Menschen unsichtbar --}}
            <input type="text" wire:model="website" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-zinc-500">
                    {{ __('Ihre Angaben verwenden wir ausschließlich zur Bearbeitung Ihrer Anfrage.') }}
                    <a href="{{ route('legal.privacy') }}" class="underline hover:text-zinc-700">{{ __('Datenschutzerklärung') }}</a>
                </p>
                <flux:button type="submit" variant="primary" icon="paper-airplane">
                    <span wire:loading.remove>{{ __('Anfrage senden') }}</span>
                    <span wire:loading>{{ __('Wird gesendet …') }}</span>
                </flux:button>
            </div>
        </form>
    @endif
</div>
