<?php

use App\Models\Company;
use App\Models\MobileAccessCode;
use App\Models\User;
use App\Support\Mobile\MobileRolloutPdf;
use App\Support\Mobile\OnboardingQrCode;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Title('Notfall-App')] class extends Component {
    /** Der zuletzt erzeugte Klartext-Code (nur einmalig sichtbar). */
    public ?string $generatedCode = null;

    /**
     * Massen-Rollout: ausgewählte User-IDs.
     *
     * @var list<string>
     */
    public array $rolloutSelection = [];

    /** Massen-Rollout: Namens-/E-Mail-Filter für die Mitgliederliste. */
    public string $rolloutSearch = '';

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

    /**
     * Onboarding-QR-Code als Data-URI (nur nach dem Erzeugen eines Codes).
     */
    #[Computed]
    public function qrDataUri(): ?string
    {
        $payload = $this->onboardingPayload;

        return $payload === null ? null : OnboardingQrCode::dataUri($payload);
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
     * Darf der aktuelle User den Massen-Rollout nutzen? Codes für andere
     * Benutzer auszustellen ist eine Admin-Aktion.
     */
    #[Computed]
    public function canRollout(): bool
    {
        return $this->company !== null && Auth::user()->isCurrentTeamAdmin();
    }

    /**
     * Aktive Team-Mitglieder für den Massen-Rollout, optional gefiltert
     * nach Name/E-Mail.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function rolloutMembers(): Collection
    {
        if (! $this->canRollout) {
            return collect();
        }

        $team = $this->company->team;

        if ($team === null) {
            return collect();
        }

        $search = trim($this->rolloutSearch);

        return $team->members()
            ->wherePivotNull('disabled_at')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();
    }

    /** Wählt alle aktuell gefilterten Mitglieder aus. */
    public function selectAllRolloutMembers(): void
    {
        $this->rolloutSelection = $this->rolloutMembers
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function clearRolloutSelection(): void
    {
        $this->rolloutSelection = [];
    }

    /**
     * Erzeugt für alle ausgewählten Benutzer Kopplungs-Codes (Gültigkeit
     * {@see MobileAccessCode::ROLLOUT_TTL_DAYS} Tage) und streamt das
     * druckbare PDF — eine Seite pro Person.
     */
    public function downloadRolloutPdf(): ?StreamedResponse
    {
        abort_unless($this->canRollout, 403);

        $ids = collect($this->rolloutSelection)->map(fn ($id) => (int) $id)->filter()->unique();

        $users = $this->company->team->members()
            ->wherePivotNull('disabled_at')
            ->whereIn('users.id', $ids)
            ->orderBy('name')
            ->get();

        if ($users->isEmpty()) {
            Flux::toast(variant: 'warning', text: __('Bitte wählen Sie mindestens einen Benutzer aus.'));

            return null;
        }

        $result = MobileRolloutPdf::generate($this->company, $users, Auth::user());

        $this->rolloutSelection = [];
        unset($this->codes);

        Flux::toast(variant: 'success', text: __(':count Zugangscodes erzeugt — das PDF wird heruntergeladen.', ['count' => $users->count()]));

        return response()->streamDownload(
            fn () => print $result['binary'],
            $result['filename'],
            ['Content-Type' => 'application/pdf'],
        );
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
                                {{ __('In der App scannen — oder manuell eingeben:') }} <strong>{{ __('E-Mail') }}</strong> {{ Auth::user()->email }} · <strong>{{ __('Code') }}</strong> {{ $generatedCode }}
                            </flux:text>
                            <div class="mt-4 flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                                <img src="{{ $this->qrDataUri }}" alt="{{ __('Onboarding-QR-Code') }}" width="200" height="200"
                                     class="rounded-xl border border-emerald-200 bg-white p-2 dark:border-emerald-800" />
                                <flux:text size="sm" class="text-emerald-800 dark:text-emerald-200">
                                    {{ __('Öffnen Sie die PlanB-Notfall-App auf Ihrem Smartphone, tippen Sie auf „QR scannen" und richten Sie den Code ein. Die App verbindet sich damit fest mit diesem Mandanten.') }}
                                </flux:text>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($this->canRollout)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading size="base">{{ __('Massen-Rollout') }}</flux:heading>
                        <flux:text class="mt-2 text-zinc-600 dark:text-zinc-300">
                            {{ __('Erzeugen Sie Zugangscodes für mehrere Benutzer auf einmal und erhalten Sie ein druckbares PDF — eine Seite pro Person mit QR-Code und Anleitung. Diese Codes sind') }}
                            <strong>{{ \App\Models\MobileAccessCode::ROLLOUT_TTL_DAYS }} {{ __('Tage') }}</strong>
                            {{ __('gültig (statt :minutes Minuten), da die Blätter gedruckt verteilt werden — jeder Code bleibt nur einmal einlösbar.', ['minutes' => \App\Models\MobileAccessCode::TTL_MINUTES]) }}
                        </flux:text>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <flux:input wire:model.live.debounce.300ms="rolloutSearch" icon="magnifying-glass" :placeholder="__('Nach Name oder E-Mail filtern …')" class="sm:max-w-xs" />
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="filled" wire:click="selectAllRolloutMembers">{{ __('Alle auswählen') }}</flux:button>
                                @if ($rolloutSelection !== [])
                                    <flux:button size="sm" variant="ghost" wire:click="clearRolloutSelection">{{ __('Auswahl aufheben') }}</flux:button>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 max-h-72 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                            @forelse ($this->rolloutMembers as $member)
                                <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800" wire:key="rollout-{{ $member->id }}">
                                    <input type="checkbox" value="{{ $member->id }}" wire:model="rolloutSelection"
                                           class="size-4 rounded border-zinc-300 text-zinc-800 dark:border-zinc-600" />
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $member->name }}</span>
                                        <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $member->email }}</span>
                                    </span>
                                </label>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Keine Benutzer gefunden.') }}
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                {{ count($rolloutSelection) }} {{ __('ausgewählt') }}
                            </flux:text>
                            <flux:button variant="primary" icon="printer" wire:click="downloadRolloutPdf" :disabled="$rolloutSelection === []">
                                {{ __('Codes erzeugen & PDF herunterladen') }}
                            </flux:button>
                        </div>
                    </div>
                @endif

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
