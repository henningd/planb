<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        {{-- Gespeicherte Menübreite früh setzen, damit die Sidebar nicht in der Standardbreite aufblitzt. --}}
        <script>
            (function () {
                try {
                    var w = parseInt(localStorage.getItem('appSidebarWidth'), 10);
                    if (!isNaN(w)) {
                        w = Math.min(500, Math.max(200, w));
                        document.documentElement.style.setProperty('--app-sidebar-width', w + 'px');
                    }
                } catch (e) {}
            })();
        </script>

        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <livewire:incident-launcher />

            <livewire:team-switcher />

            @php
                // „Einrichtung" nur zeigen, solange das Onboarding NICHT komplett
                // abgeschlossen ist — gleiche Abschluss-Definition wie im Wizard/
                // Dashboard ({@see \App\Support\Onboarding\OnboardingProgress::isFullyDone()}),
                // pro Firma (Mandant). Die Route bleibt direkt aufrufbar.
                $sidebarCompany = auth()->user()?->currentCompany();
                $onboardingCompleted = false;
                if ($sidebarCompany !== null) {
                    $onboardingCompleted = \App\Support\Onboarding\OnboardingService::ensureState($sidebarCompany)->isCompleted()
                        || \App\Support\Onboarding\OnboardingService::progressFor($sidebarCompany)->isFullyDone();
                }
            @endphp

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="inbox" :href="route('tasks-inbox.index')" :current="request()->routeIs('tasks-inbox.*')" wire:navigate>
                        {{ __('Aufgaben-Inbox') }}
                    </flux:sidebar.item>
                    @unless ($onboardingCompleted)
                        <flux:sidebar.item icon="rocket-launch" :href="route('onboarding.index')" :current="request()->routeIs('onboarding.*')" wire:navigate>
                            {{ __('Einrichtung') }}
                        </flux:sidebar.item>
                    @endunless
                    @if (auth()->user()->isCurrentTeamAdmin())
                        <flux:sidebar.item icon="book-open" :href="route('handbook-versions.index')" :current="request()->routeIs('handbook-versions.*')" wire:navigate>
                            {{ __('Notfallhandbuch') }}
                        </flux:sidebar.item>
                    @endif
                    @if (config('features.compliance') && \Illuminate\Support\Facades\Route::has('compliance.index') && auth()->user()->isCurrentTeamAdmin())
                        <flux:sidebar.item icon="chart-bar" :href="route('compliance.index')" :current="request()->routeIs('compliance.*')" wire:navigate>
                            {{ __('Compliance') }}
                        </flux:sidebar.item>
                    @endif
                    @if (config('features.risk_register') && \Illuminate\Support\Facades\Route::has('risks.index') && auth()->user()->isAtLeastConsultant())
                        <flux:sidebar.item icon="shield-exclamation" :href="route('risks.index')" :current="request()->routeIs('risks.*')" wire:navigate>
                            {{ __('Risiken') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group
                    :heading="__('Stammdaten')"
                    expandable
                    :expanded="auth()->user()?->isSidebarGroupExpanded('stammdaten') ?? false"
                    data-sidebar-key="stammdaten"
                    class="grid"
                >
                    <flux:sidebar.item icon="building-office-2" :href="route('company.edit')" :current="request()->routeIs('company.*')" wire:navigate>
                        {{ __('Firma') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map-pin" :href="route('locations.index')" :current="request()->routeIs('locations.*')" wire:navigate>
                        {{ __('Standorte') }}
                    </flux:sidebar.item>
                    @if (config('features.departments') && \Illuminate\Support\Facades\Route::has('departments.index'))
                        <flux:sidebar.item icon="rectangle-stack" :href="route('departments.index')" :current="request()->routeIs('departments.*')" wire:navigate>
                            {{ __('Abteilungen') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="user-group" :href="route('employees.index')" :current="request()->routeIs('employees.*')" wire:navigate>
                        {{ __('Mitarbeiter') }}
                    </flux:sidebar.item>
                    @if (config('features.roles'))
                        <flux:sidebar.item icon="identification" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                            {{ __('Abteilungen / Rollen') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('service-providers.index')" :current="request()->routeIs('service-providers.*')" wire:navigate>
                        {{ __('Dienstleister') }}
                    </flux:sidebar.item>
                    @if (config('features.contracts') && \Illuminate\Support\Facades\Route::has('contracts.index'))
                        <flux:sidebar.item icon="document-text" :href="route('contracts.index')" :current="request()->routeIs('contracts.*')" wire:navigate>
                            {{ __('Verträge') }}
                        </flux:sidebar.item>
                    @endif
                    @if (auth()->user()->isAtLeastConsultant())
                        <flux:sidebar.item icon="shield-check" :href="route('insurance-policies.index')" :current="request()->routeIs('insurance-policies.*')" wire:navigate>
                            {{ __('Versicherungen') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group
                    :heading="__('Systeme & Vorsorge')"
                    expandable
                    :expanded="auth()->user()?->isSidebarGroupExpanded('systeme') ?? false"
                    data-sidebar-key="systeme"
                    class="grid"
                >
                    <flux:sidebar.item icon="server-stack" :href="route('systems.index')" :current="request()->routeIs('systems.index') || request()->routeIs('systems.create') || request()->routeIs('systems.show') || request()->routeIs('systems.edit') || request()->routeIs('systems.export')" wire:navigate>
                        {{ __('Systeme') }}
                    </flux:sidebar.item>
                    @if (config('features.dependencies') && \Illuminate\Support\Facades\Route::has('dependencies.index'))
                        <flux:sidebar.item icon="share" :href="route('dependencies.index')" :current="request()->routeIs('dependencies.*')" wire:navigate>
                            {{ __('Abhängigkeiten') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="shield-exclamation" :href="route('emergency-levels.index')" :current="request()->routeIs('emergency-levels.*')" wire:navigate>
                        {{ __('Notfall-Level') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="lifebuoy" :href="route('fallback-processes.index')" :current="request()->routeIs('fallback-processes.*')" wire:navigate>
                        {{ __('Notfallbetrieb') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" :href="route('emergency-resources.index')" :current="request()->routeIs('emergency-resources.*')" wire:navigate>
                        {{ __('Sofortmittel') }}
                    </flux:sidebar.item>
                    @if (config('features.preventive_measures') && \Illuminate\Support\Facades\Route::has('preventive-measures.index'))
                        <flux:sidebar.item icon="shield-check" :href="route('preventive-measures.index')" :current="request()->routeIs('preventive-measures.*')" wire:navigate>
                            {{ __('Prävention') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="chart-bar-square" :href="route('recovery-gantt.index')" :current="request()->routeIs('recovery-gantt.*')" wire:navigate>
                        {{ __('Recovery-Zeitplan') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calculator" :href="route('systems.cost-calculator')" :current="request()->routeIs('systems.cost-calculator')" wire:navigate>
                        {{ __('Ausfallrechner') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group
                    :heading="__('BCMS & Governance')"
                    expandable
                    :expanded="auth()->user()?->isSidebarGroupExpanded('bcms') ?? false"
                    data-sidebar-key="bcms"
                    class="grid"
                >
                    @if (config('features.bia') && \Illuminate\Support\Facades\Route::has('business-processes.index'))
                        <flux:sidebar.item icon="rectangle-group" :href="route('business-processes.index')" :current="request()->routeIs('business-processes.*')" wire:navigate>
                            {{ __('Geschäftsprozesse / BIA') }}
                        </flux:sidebar.item>
                    @endif
                    @if (config('features.supply_chain_risk') && \Illuminate\Support\Facades\Route::has('supplier-risk.index'))
                        <flux:sidebar.item icon="truck" :href="route('supplier-risk.index')" :current="request()->routeIs('supplier-risk.*')" wire:navigate>
                            {{ __('Lieferketten-Risiko') }}
                        </flux:sidebar.item>
                    @endif
                    @if (config('features.training_records') && \Illuminate\Support\Facades\Route::has('training-records.index'))
                        <flux:sidebar.item icon="academic-cap" :href="route('training-records.index')" :current="request()->routeIs('training-records.*')" wire:navigate>
                            {{ __('Schulungen') }}
                        </flux:sidebar.item>
                    @endif
                    @if (config('features.bcm_policy') && \Illuminate\Support\Facades\Route::has('bcm-policy.index'))
                        <flux:sidebar.item icon="document-check" :href="route('bcm-policy.index')" :current="request()->routeIs('bcm-policy.*')" wire:navigate>
                            {{ __('BCM-Leitlinie') }}
                        </flux:sidebar.item>
                    @endif
                    @if (config('features.management_review') && \Illuminate\Support\Facades\Route::has('management-reviews.index'))
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('management-reviews.index')" :current="request()->routeIs('management-reviews.*')" wire:navigate>
                            {{ __('Management-Review') }}
                        </flux:sidebar.item>
                    @endif
                    @if (config('features.maturity') && \Illuminate\Support\Facades\Route::has('maturity.index'))
                        <flux:sidebar.item icon="chart-bar-square" :href="route('maturity.index')" :current="request()->routeIs('maturity.*')" wire:navigate>
                            {{ __('Reifegrad (BSI 200-4)') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group
                    :heading="__('Ernstfall')"
                    expandable
                    :expanded="auth()->user()?->isSidebarGroupExpanded('emergency') ?? false"
                    data-sidebar-key="emergency"
                    class="grid"
                >
                    @if (config('features.incident_mode') && \Illuminate\Support\Facades\Route::has('incident-mode.index'))
                        <flux:sidebar.item icon="exclamation-triangle" :href="route('incident-mode.index')" :current="request()->routeIs('incident-mode.*')" wire:navigate>
                            {{ __('Krisen-Cockpit') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="bolt" :href="route('scenarios.index')" :current="request()->routeIs('scenarios.*')" wire:navigate>
                        {{ __('Szenarien') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-path" :href="route('systems.recovery')" :current="request()->routeIs('systems.recovery')" wire:navigate>
                        {{ __('Wiederanlauf') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" :href="route('incidents.index')" :current="request()->routeIs('incidents.*')" wire:navigate>
                        {{ __('Meldepflichten') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('scenario-runs.index')" :current="request()->routeIs('scenario-runs.*')" wire:navigate>
                        {{ __('Protokolle & Übungen') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="identification" :href="route('emergency-card.pdf')" target="_blank">
                        {{ __('Notfallkarte (PDF)') }}
                    </flux:sidebar.item>
                    @if (config('features.lessons_learned') && \Illuminate\Support\Facades\Route::has('lessons-learned.index'))
                        <flux:sidebar.item icon="academic-cap" :href="route('lessons-learned.index')" :current="request()->routeIs('lessons-learned.*')" wire:navigate>
                            {{ __('Lessons Learned') }}
                        </flux:sidebar.item>
                    @endif
                    @if (auth()->user()->isAtLeastConsultant())
                        <flux:sidebar.item icon="document-duplicate" :href="route('communication-templates.index')" :current="request()->routeIs('communication-templates.*')" wire:navigate>
                            {{ __('Vorlagen') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('handbook-tests.index')" :current="request()->routeIs('handbook-tests.*')" wire:navigate>
                            {{ __('Testplan') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group
                    :heading="__('Team & Freigaben')"
                    expandable
                    :expanded="auth()->user()?->isSidebarGroupExpanded('team') ?? false"
                    data-sidebar-key="team"
                    class="grid"
                >
                    <flux:sidebar.item
                        icon="key"
                        :href="auth()->user()?->currentTeam ? route('teams.edit', auth()->user()->currentTeam) : route('teams.index')"
                        :current="request()->routeIs('teams.*')"
                        wire:navigate
                    >
                        {{ __('App-Benutzer & Einladungen') }}
                    </flux:sidebar.item>
                    @if (auth()->user()->isCurrentTeamAdmin())
                        <flux:sidebar.item icon="share" :href="route('handbook-shares.index')" :current="request()->routeIs('handbook-shares.*')" wire:navigate>
                            {{ __('Freigabelinks') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('audit-log.index')" :current="request()->routeIs('audit-log.*')" wire:navigate>
                            {{ __('Aktivitäten') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="finger-print" :href="route('login-activity.index')" :current="request()->routeIs('login-activity.*')" wire:navigate>
                            {{ __('Anmeldungen') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @if (auth()->user()?->isCurrentTeamAdmin())
                    <flux:sidebar.group
                        :heading="__('Einstellungen')"
                        expandable
                        :expanded="auth()->user()->isSidebarGroupExpanded('settings')"
                        data-sidebar-key="settings"
                        class="grid"
                    >
                        <flux:sidebar.item icon="cog-8-tooth" :href="route('system-settings.index')" :current="request()->routeIs('system-settings.*')" wire:navigate>
                            {{ __('System') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="paint-brush" :href="route('branding.index')" :current="request()->routeIs('branding.*')" wire:navigate>
                            {{ __('Branding') }}
                        </flux:sidebar.item>
                        @if (config('features.monitoring_api') && \Illuminate\Support\Facades\Route::has('api-tokens.index'))
                            <flux:sidebar.item icon="bolt-slash" :href="route('api-tokens.index')" :current="request()->routeIs('api-tokens.*')" wire:navigate>
                                {{ __('API & Webhooks') }}
                            </flux:sidebar.item>
                        @endif
                        @if (auth()->user()?->isSuperAdmin())
                            <flux:sidebar.item icon="globe-alt" :href="route('admin.settings.system.index')" :current="request()->routeIs('admin.settings.system.*')" wire:navigate>
                                {{ __('Plattform') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                @if (auth()->user()?->isSuperAdmin())
                    <flux:sidebar.group
                        :heading="__('Administration')"
                        expandable
                        :expanded="auth()->user()->isSidebarGroupExpanded('administration')"
                        data-sidebar-key="administration"
                        class="grid"
                    >
                        <flux:sidebar.item icon="shield-check" :href="route('admin.index')" :current="request()->routeIs('admin.index')" wire:navigate>
                            {{ __('Übersicht') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-office" :href="route('admin.companies.index')" :current="request()->routeIs('admin.companies.*')" wire:navigate>
                            {{ __('Kunden') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user-plus" :href="route('admin.leads.index')" :current="request()->routeIs('admin.leads.*')" wire:navigate>
                            {{ __('Leads') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-duplicate" :href="route('admin.scenarios.index')" :current="request()->routeIs('admin.scenarios.*')" wire:navigate>
                            {{ __('Globale Szenarien') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="rectangle-stack" :href="route('admin.industry-templates.index')" :current="request()->routeIs('admin.industry-templates.*')" wire:navigate>
                            {{ __('Branchen-Templates') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="sparkles" :href="route('admin.demo.index')" :current="request()->routeIs('admin.demo.*')" wire:navigate>
                            {{ __('Demo-Firma') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            @php
                $platformFooter = \App\Support\Settings\SystemSetting::get('platform_footer', '');
            @endphp
            @if (filled($platformFooter))
                <div class="px-3 pb-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {!! e($platformFooter) !!}
                </div>
            @endif

            @auth
                {{-- Glocke + Hilfe nebeneinander, beide dauerhaft in Button-Optik. --}}
                <div class="hidden items-center gap-2 px-1 lg:flex">
                    @if (auth()->user()?->currentCompany())
                        <livewire:notification-bell />
                    @endif
                    <flux:tooltip :content="__('Hilfe & Handbuch')">
                        <flux:button
                            variant="filled"
                            icon="question-mark-circle"
                            :href="route('manual.index')"
                            target="_blank"
                            aria-label="{{ __('Hilfe & Handbuch') }}"
                            data-test="sidebar-help-button"
                        />
                    </flux:tooltip>
                </div>
            @endauth

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        {{-- Ziehgriff, um die Menübreite anzupassen (nur Desktop). Doppelklick setzt zurück. --}}
        <div
            x-data="{
                dragging: false,
                min: 200,
                max: 500,
                current() {
                    var v = getComputedStyle(document.documentElement).getPropertyValue('--app-sidebar-width').trim();
                    var px = parseFloat(v);
                    return isNaN(px) ? 256 : px;
                },
                start(e) {
                    this.dragging = true;
                    var startX = e.clientX;
                    var startW = this.current();
                    var self = this;
                    var move = function (ev) {
                        var w = Math.min(self.max, Math.max(self.min, startW + (ev.clientX - startX)));
                        document.documentElement.style.setProperty('--app-sidebar-width', w + 'px');
                    };
                    var up = function () {
                        self.dragging = false;
                        document.body.style.userSelect = '';
                        document.body.style.cursor = '';
                        window.removeEventListener('mousemove', move);
                        window.removeEventListener('mouseup', up);
                        try { localStorage.setItem('appSidebarWidth', self.current()); } catch (e) {}
                    };
                    document.body.style.userSelect = 'none';
                    document.body.style.cursor = 'col-resize';
                    window.addEventListener('mousemove', move);
                    window.addEventListener('mouseup', up);
                },
                reset() {
                    document.documentElement.style.setProperty('--app-sidebar-width', '16rem');
                    try { localStorage.removeItem('appSidebarWidth'); } catch (e) {}
                },
            }"
            @mousedown.prevent="start($event)"
            @dblclick="reset()"
            :style="{ left: 'var(--app-sidebar-width, 16rem)' }"
            class="group/resizer fixed inset-y-0 z-30 hidden w-2 -translate-x-1/2 cursor-col-resize lg:block"
            role="separator"
            aria-orientation="vertical"
            :aria-valuenow="Math.round(current())"
            aria-valuemin="200"
            aria-valuemax="500"
            title="{{ __('Menübreite ziehen · Doppelklick setzt zurück') }}"
        >
            <div
                class="mx-auto h-full w-px transition-colors group-hover/resizer:bg-sky-400 dark:group-hover/resizer:bg-sky-500"
                :class="dragging ? 'bg-sky-500' : 'bg-transparent'"
            ></div>
        </div>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @auth
                @if (auth()->user()?->currentCompany())
                    <livewire:notification-bell />
                @endif
            @endauth

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @auth
            <script data-navigate-once>
                (function () {
                    if (window.__sidebarGroupListenerAttached) return;
                    window.__sidebarGroupListenerAttached = true;

                    // Persistiert nur echte Toggle-Klicks auf den Disclosure-
                    // Button der Sidebar-Gruppe – nicht das `lofi-disclosable-
                    // change` Event, das beim wire:navigate-Morph fälschlich
                    // mit "zu" feuern und den Zustand überschreiben würde.
                    document.addEventListener('click', function (event) {
                        const group = event.target.closest('[data-sidebar-key]');
                        if (!group) return;

                        const button = event.target.closest('button');
                        if (!button || button.parentElement !== group) return;

                        const token = document.querySelector('meta[name="csrf-token"]')?.content;
                        if (!token) return;

                        // Nach Toggle-Click den neuen Zustand lesen (kurzer
                        // Tick, damit die Disclosure data-open setzen konnte).
                        setTimeout(function () {
                            fetch('{{ route('preferences.sidebar-group') }}', {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                },
                                body: JSON.stringify({
                                    key: group.dataset.sidebarKey,
                                    expanded: group.hasAttribute('data-open'),
                                }),
                            }).catch(function () { /* best-effort, ignore */ });
                        }, 50);
                    });
                })();
            </script>
        @endauth

        @fluxScripts
    </body>
</html>
