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
        <form wire:submit="submit" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm lg:p-8">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="kk-name" class="block text-sm font-medium text-slate-700">{{ __('Name') }} *</label>
                    <input id="kk-name" type="text" wire:model="contactName" required
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('contactName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="kk-org" class="block text-sm font-medium text-slate-700">{{ $source === 'web' ? __('Unternehmen / Organisation') : __('Kommune / Organisation') }} *</label>
                    <input id="kk-org" type="text" wire:model="organization" required placeholder="{{ $source === 'web' ? __('z. B. Beispiel GmbH') : __('z. B. Stadt Musterstadt') }}"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('organization') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="kk-email" class="block text-sm font-medium text-slate-700">{{ __('Dienstliche E-Mail') }} *</label>
                    <input id="kk-email" type="email" wire:model="email" required
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="kk-phone" class="block text-sm font-medium text-slate-700">{{ __('Telefon (optional)') }}</label>
                    <input id="kk-phone" type="tel" wire:model="phone"
                        class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mt-4">
                <label for="kk-message" class="block text-sm font-medium text-slate-700">{{ __('Ihre Nachricht') }} *</label>
                <textarea id="kk-message" rows="4" wire:model="message" required
                    placeholder="{{ $source === 'web' ? __('z. B. Bitte um eine Demo für unser Unternehmen mit 2 Standorten …') : __('z. B. Bitte um ein Angebot für den Kommunal-Tarif für unsere Verwaltung mit 3 Liegenschaften …') }}"
                    class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                @error('message') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- Honeypot — für Menschen unsichtbar --}}
            <input type="text" wire:model="website" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    {{ __('Ihre Angaben verwenden wir ausschließlich zur Bearbeitung Ihrer Anfrage.') }}
                    <a href="{{ route('legal.privacy') }}" class="underline hover:text-slate-700">{{ __('Datenschutzerklärung') }}</a>
                </p>
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60">
                    <span wire:loading.remove>{{ __('Anfrage senden') }}</span>
                    <span wire:loading>{{ __('Wird gesendet …') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>
