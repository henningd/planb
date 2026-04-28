<?php

use App\Models\ApiToken;
use App\Models\MonitoringAlert;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('API & Webhooks')] class extends Component {
    public string $newName = '';

    public ?string $issuedToken = null;

    #[Computed]
    public function tokens()
    {
        return ApiToken::with('createdBy')
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function recentAlerts()
    {
        return MonitoringAlert::with('system', 'incidentReport')
            ->orderByDesc('received_at')
            ->limit(20)
            ->get();
    }

    public function openCreate(): void
    {
        $this->reset(['newName', 'issuedToken']);
        Flux::modal('token-create')->show();
    }

    public function createToken(): void
    {
        $this->validate([
            'newName' => ['required', 'string', 'max:120'],
        ]);

        $companyId = Auth::user()->currentCompany()?->id;
        if ($companyId === null) {
            Flux::toast(variant: 'warning', text: __('Kein Mandant aktiv.'));

            return;
        }

        $issued = ApiToken::issue($companyId, $this->newName, ['monitoring.write'], Auth::id());
        $this->issuedToken = $issued['token'];
        $this->newName = '';
    }

    public function revoke(string $id): void
    {
        $token = ApiToken::find($id);
        if (! $token || $token->isRevoked()) {
            return;
        }
        $token->forceFill(['revoked_at' => now()])->save();
    }
}; ?>

<section class="mx-auto w-full max-w-5xl space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('API & Webhooks') }}</flux:heading>
            <flux:subheading>
                {{ __('Tokens für externe Tools wie Zabbix oder Prometheus Alertmanager. Monitoring-Alarme können automatisch Incidents in PlanB anlegen.') }}
            </flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">
            {{ __('Token erstellen') }}
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:heading size="md">{{ __('Aktive Tokens') }}</flux:heading>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->tokens as $token)
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0 flex-1">
                        <div class="font-medium">{{ $token->name }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ $token->prefix }}…
                            · {{ __('Erstellt') }}: {{ $token->created_at->format('d.m.Y') }}
                            @if ($token->createdBy) · {{ $token->createdBy->name }} @endif
                            @if ($token->last_used_at) · {{ __('Zuletzt verwendet') }}: {{ $token->last_used_at->diffForHumans() }} @endif
                        </div>
                        @if ($token->scopes)
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach ($token->scopes as $scope)
                                    <flux:badge color="zinc" size="sm">{{ $scope }}</flux:badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div>
                        @if ($token->isRevoked())
                            <flux:badge color="rose" size="sm">{{ __('Widerrufen') }}</flux:badge>
                        @else
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="revoke('{{ $token->id }}')" wire:confirm="{{ __('Token widerrufen?') }}">
                                {{ __('Widerrufen') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-zinc-500">
                    {{ __('Noch keine Tokens. Erstelle einen, um Zabbix oder Prometheus mit PlanB zu verbinden.') }}
                </div>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:heading size="md">{{ __('Webhook-Endpoints') }}</flux:heading>
            <flux:subheading>
                {{ __('Zielen Sie Ihr Monitoring-Tool auf einen dieser Endpunkte. Authentifizierung über den Header :header', ['header' => 'Authorization: Bearer <token>']) }}
            </flux:subheading>
        </div>
        <div class="divide-y divide-zinc-100 px-5 py-4 dark:divide-zinc-800">
            <div class="flex items-center justify-between gap-3 py-2">
                <div>
                    <div class="font-medium">Zabbix</div>
                    <code class="text-xs">{{ url('/api/v1/webhooks/zabbix') }}</code>
                </div>
                <flux:badge color="zinc" size="sm">POST · JSON</flux:badge>
            </div>
            <div class="flex items-center justify-between gap-3 py-2">
                <div>
                    <div class="font-medium">Prometheus Alertmanager</div>
                    <code class="text-xs">{{ url('/api/v1/webhooks/prometheus') }}</code>
                </div>
                <flux:badge color="zinc" size="sm">POST · JSON</flux:badge>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:heading size="md">{{ __('Letzte eingegangene Alarme') }}</flux:heading>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-2">{{ __('Zeit') }}</th>
                    <th class="px-4 py-2">{{ __('Quelle') }}</th>
                    <th class="px-4 py-2">{{ __('Host') }}</th>
                    <th class="px-4 py-2">{{ __('Status') }}</th>
                    <th class="px-4 py-2">{{ __('System') }}</th>
                    <th class="px-4 py-2">{{ __('Verarbeitung') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->recentAlerts as $alert)
                    <tr>
                        <td class="px-4 py-2 text-xs text-zinc-500">{{ $alert->received_at->format('d.m. H:i') }}</td>
                        <td class="px-4 py-2"><flux:badge color="zinc" size="sm">{{ $alert->source }}</flux:badge></td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $alert->host ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <flux:badge :color="$alert->status === 'firing' ? 'rose' : 'emerald'" size="sm">
                                {{ $alert->status }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-2">{{ $alert->system?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs">
                            @if ($alert->incidentReport)
                                <a href="{{ route('incidents.show', $alert->incidentReport) }}" class="text-sky-600 underline" wire:navigate>
                                    {{ $alert->handling }}
                                </a>
                            @else
                                {{ $alert->handling }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                            {{ __('Noch keine Alarme empfangen.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="token-create" class="max-w-xl">
        @if ($issuedToken === null)
            <form wire:submit="createToken" class="space-y-5">
                <flux:heading size="lg">{{ __('Neuen Token erstellen') }}</flux:heading>
                <flux:input wire:model="newName" :label="__('Bezeichnung (z. B. „Zabbix-Produktion")')" required />
                <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button type="button" variant="filled">{{ __('Abbrechen') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">{{ __('Erstellen') }}</flux:button>
                </div>
            </form>
        @else
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Token einmalig anzeigen') }}</flux:heading>
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                    {{ __('Kopieren Sie den Token jetzt — er wird aus Sicherheitsgründen nie wieder angezeigt.') }}
                </div>
                <div class="rounded-lg bg-zinc-100 p-3 dark:bg-zinc-800">
                    <code class="break-all text-xs">{{ $issuedToken }}</code>
                </div>
                <div class="flex items-center justify-end border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="primary" type="button">{{ __('Verstanden') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</section>
