<?php

use App\Models\AppNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Benachrichtigungs-Glocke fürs Dashboard: zeigt den firmenweiten
 * {@see AppNotification}-Feed mit Ungelesen-Zähler. Der Gelesen-Status je Nutzer
 * liegt als Zeitstempel (`users.notifications_seen_at`) vor – alles danach gilt
 * als ungelesen. Firmen-Queries werden IMMER explizit auf `company_id`
 * eingeschränkt, da {@see AppNotification} bewusst nicht mandantengebunden ist.
 */
new class extends Component {
    public ?string $companyId = null;

    public function mount(): void
    {
        $this->companyId = Auth::user()?->currentCompany()?->id;
    }

    /**
     * Zeitpunkt, ab dem eine Benachrichtigung als „ungelesen" gilt. Nutzer, die
     * die Glocke noch nie geöffnet haben, sehen den kompletten Verlauf als neu.
     */
    protected function seenAt(): CarbonInterface
    {
        return Auth::user()?->notifications_seen_at ?? CarbonImmutable::createFromTimestamp(0);
    }

    /**
     * Anzahl der Benachrichtigungen der aktuellen Firma, die neuer sind als der
     * letzte „Alle als gelesen"-Zeitpunkt des Nutzers.
     */
    #[Computed]
    public function unreadCount(): int
    {
        if (! $this->companyId) {
            return 0;
        }

        return AppNotification::query()
            ->where('company_id', $this->companyId)
            ->where('created_at', '>', $this->seenAt())
            ->count();
    }

    /**
     * Die jüngsten Benachrichtigungen der aktuellen Firma.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AppNotification>
     */
    #[Computed]
    public function notifications()
    {
        if (! $this->companyId) {
            return AppNotification::query()->whereRaw('1 = 0')->get();
        }

        return AppNotification::query()
            ->where('company_id', $this->companyId)
            ->latest('created_at')
            ->limit(20)
            ->get();
    }

    /**
     * Setzt den Gelesen-Zeitstempel des Nutzers auf jetzt und leert damit den
     * Ungelesen-Zähler.
     */
    public function markAllAsRead(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $user->forceFill(['notifications_seen_at' => now()])->save();

        unset($this->unreadCount, $this->notifications);
    }
}; ?>

<div>
    @php
        $seenAt = Auth::user()?->notifications_seen_at;
    @endphp
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" class="relative" data-test="notification-bell-trigger">
            <flux:icon name="bell" class="size-5" />
            @if ($this->unreadCount > 0)
                <flux:badge
                    color="rose"
                    size="sm"
                    class="absolute -end-1 -top-1"
                    data-test="notification-bell-count"
                >{{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}</flux:badge>
            @endif
        </flux:button>

        <flux:menu class="w-96 max-w-[90vw]">
            <div class="flex items-center justify-between px-2 py-1.5">
                <flux:heading size="sm">{{ __('Benachrichtigungen') }}</flux:heading>
                @if ($this->unreadCount > 0)
                    <flux:button
                        variant="ghost"
                        size="xs"
                        wire:click="markAllAsRead"
                        data-test="notification-bell-mark-all"
                    >
                        {{ __('Alle als gelesen') }}
                    </flux:button>
                @endif
            </div>

            <flux:menu.separator />

            @forelse ($this->notifications as $notification)
                @php
                    $isUnread = ! $seenAt || $notification->created_at?->greaterThan($seenAt);
                    $severityColor = match ($notification->severity) {
                        'critical', 'danger' => 'red',
                        'warning' => 'amber',
                        'success' => 'green',
                        default => 'zinc',
                    };
                    $severityDot = match ($severityColor) {
                        'red' => 'bg-red-500',
                        'amber' => 'bg-amber-500',
                        'green' => 'bg-green-500',
                        default => 'bg-zinc-400',
                    };
                    $url = $notification->scenario_run_id
                        ? route('scenario-runs.show', ['run' => $notification->scenario_run_id])
                        : null;
                @endphp

                <div class="px-2 py-2 {{ $isUnread ? 'bg-sky-50/60 dark:bg-sky-500/5' : '' }}" data-test="notification-bell-item">
                    <div class="flex items-start gap-2">
                        <span class="mt-1.5 inline-block size-2 shrink-0 rounded-full {{ $severityDot }}" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                @if ($url)
                                    <a
                                        href="{{ $url }}"
                                        wire:navigate
                                        class="truncate text-sm no-underline hover:underline {{ $isUnread ? 'font-bold text-zinc-900 dark:text-white' : 'font-medium text-zinc-700 dark:text-zinc-200' }}"
                                    >{{ $notification->title }}</a>
                                @else
                                    <span class="truncate text-sm {{ $isUnread ? 'font-bold text-zinc-900 dark:text-white' : 'font-medium text-zinc-700 dark:text-zinc-200' }}">{{ $notification->title }}</span>
                                @endif
                                <flux:badge :color="$severityColor" size="sm" class="shrink-0">{{ $notification->severity }}</flux:badge>
                            </div>
                            @if ($notification->body)
                                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $notification->body }}</p>
                            @endif
                            <div class="mt-1 flex items-center gap-2 text-xs text-zinc-400 dark:text-zinc-500">
                                @if ($notification->triggered_by_name)
                                    <span>{{ $notification->triggered_by_name }}</span>
                                    <span aria-hidden="true">·</span>
                                @endif
                                <span>{{ $notification->created_at?->format('d.m.Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400" data-test="notification-bell-empty">
                    {{ __('Keine Benachrichtigungen') }}
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
