<?php

use App\Models\Company;
use App\Models\MobileAccessCode;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Notfall-App')] class extends Component {
    /** Der zuletzt erzeugte Klartext-Code (nur einmalig sichtbar). */
    public ?string $generatedCode = null;

    #[Computed]
    public function company(): ?Company
    {
        return Auth::user()->currentCompany();
    }

    /**
     * Die letzten Kopplungs-Codes des aktuellen Users in dieser Firma.
     *
     * @return Collection<int, MobileAccessCode>
     */
    #[Computed]
    public function codes(): Collection
    {
        $company = $this->company;

        if ($company === null) {
            return collect();
        }

        return MobileAccessCode::query()
            ->where('user_id', Auth::id())
            ->where('company_id', $company->id)
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Onboarding-Payload für den QR-Code / die manuelle Einrichtung.
     */
    #[Computed]
    public function onboardingPayload(): ?string
    {
        if ($this->generatedCode === null) {
            return null;
        }

        return json_encode([
            'url' => rtrim((string) config('app.url'), '/'),
            'key' => (string) config('services.mobile.app_key', ''),
            'email' => Auth::user()->email,
            'code' => $this->generatedCode,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function generate(): void
    {
        $user = Auth::user();
        $company = $user->currentCompany();

        if ($company === null) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $issued = MobileAccessCode::issue($user, $company, $user->id);
        $this->generatedCode = $issued['code'];
        unset($this->codes);

        Flux::toast(variant: 'success', text: __('Zugangscode erzeugt.'));
    }

    public function revoke(string $id): void
    {
        $code = MobileAccessCode::query()
            ->where('user_id', Auth::id())
            ->whereKey($id)
            ->first();

        if ($code !== null && $code->revoked_at === null && $code->consumed_at === null) {
            $code->forceFill(['revoked_at' => now()])->save();
            $this->generatedCode = null;
            unset($this->codes);
            Flux::toast(variant: 'success', text: __('Zugangscode widerrufen.'));
        }
    }

    /**
     * @return array<string, array{label: string, color: string}>
     */
    public function statusMeta(): array
    {
        return [
            'active' => ['label' => __('Offen'), 'color' => 'amber'],
            'consumed' => ['label' => __('Gerät verbunden'), 'color' => 'emerald'],
            'revoked' => ['label' => __('Widerrufen'), 'color' => 'zinc'],
            'expired' => ['label' => __('Abgelaufen'), 'color' => 'zinc'],
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Notfall-App')" :subheading="__('Verbinden Sie Ihr Smartphone mit dem Offline-Notfallhandbuch.')">
        @if ($this->company === null)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                {{ __('Bitte legen Sie zuerst ein Firmenprofil an, bevor Sie die App verbinden.') }}
            </div>
        @else
            <div class="space-y-8">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-zinc-600 dark:text-zinc-300">
                        {{ __('Erzeugen Sie einen einmaligen Zugangscode und geben Sie ihn in der PlanB-Notfall-App zusammen mit Ihrer E-Mail-Adresse ein. Das Gerät wird damit fest mit dem Mandanten') }}
                        <strong>{{ $this->company->name }}</strong>
                        {{ __('verbunden. Der Code ist') }} {{ \App\Models\MobileAccessCode::TTL_MINUTES }} {{ __('Minuten gültig und nur einmal einlösbar.') }}
                    </flux:text>

                    <div class="mt-5">
                        <flux:button variant="primary" icon="device-phone-mobile" wire:click="generate">
                            {{ __('Zugangscode erzeugen') }}
                        </flux:button>
                    </div>

                    @if ($generatedCode !== null)
                        <div class="mt-6 rounded-xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-700 dark:bg-emerald-950">
                            <flux:text size="sm" class="text-emerald-800 dark:text-emerald-200">{{ __('Ihr Zugangscode (nur jetzt sichtbar):') }}</flux:text>
                            <div class="mt-2 font-mono text-3xl font-bold tracking-[0.3em] text-emerald-900 dark:text-emerald-100">
                                {{ $generatedCode }}
                            </div>
                            <flux:text size="sm" class="mt-3 text-emerald-800 dark:text-emerald-200">
                                {{ __('In der App eingeben:') }} <strong>{{ __('E-Mail') }}</strong> {{ Auth::user()->email }} · <strong>{{ __('Code') }}</strong> {{ $generatedCode }}
                            </flux:text>
                            <div class="mt-4">
                                <flux:text size="sm" class="text-emerald-800 dark:text-emerald-200">{{ __('QR-Inhalt (zum Erzeugen eines QR-Codes):') }}</flux:text>
                                <pre class="mt-1 overflow-x-auto rounded-lg bg-emerald-100/60 p-3 text-xs text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-100">{{ $this->onboardingPayload }}</pre>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($this->codes->isNotEmpty())
                    <div>
                        <flux:heading size="base">{{ __('Ausgestellte Codes') }}</flux:heading>
                        <div class="mt-3 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    <tr>
                                        <th class="px-5 py-3">{{ __('Erstellt') }}</th>
                                        <th class="px-5 py-3">{{ __('Gültig bis') }}</th>
                                        <th class="px-5 py-3">{{ __('Status') }}</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @php($meta = $this->statusMeta())
                                    @foreach ($this->codes as $code)
                                        @php($status = $code->status())
                                        <tr wire:key="code-{{ $code->id }}">
                                            <td class="px-5 py-3 text-zinc-600 dark:text-zinc-300">{{ $code->created_at?->format('d.m.Y H:i') }}</td>
                                            <td class="px-5 py-3 text-zinc-600 dark:text-zinc-300">{{ $code->expires_at?->format('d.m.Y H:i') }}</td>
                                            <td class="px-5 py-3">
                                                <flux:badge :color="$meta[$status]['color']" size="sm">{{ $meta[$status]['label'] }}</flux:badge>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                @if ($status === 'active')
                                                    <flux:button size="sm" variant="ghost" wire:click="revoke('{{ $code->id }}')">{{ __('Widerrufen') }}</flux:button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-pages::settings.layout>
</section>
