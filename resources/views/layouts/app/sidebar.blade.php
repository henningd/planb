<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <livewire:team-switcher />

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Notfallhandbuch')" class="grid">
                    <flux:sidebar.item icon="building-office-2" :href="route('company.edit')" :current="request()->routeIs('company.*')" wire:navigate>
                        {{ __('Firma') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map-pin" :href="route('locations.index')" :current="request()->routeIs('locations.*')" wire:navigate>
                        {{ __('Standorte') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('employees.index')" :current="request()->routeIs('employees.*')" wire:navigate>
                        {{ __('Mitarbeiter') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('service-providers.index')" :current="request()->routeIs('service-providers.*')" wire:navigate>
                        {{ __('Dienstleister') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="server-stack" :href="route('systems.index')" :current="request()->routeIs('systems.index') || request()->routeIs('systems.create') || request()->routeIs('systems.show') || request()->routeIs('systems.edit') || request()->routeIs('systems.export')" wire:navigate>
                        {{ __('Systeme') }}
                    </flux:sidebar.item>
                    @if (auth()->user()->isCurrentTeamAdmin())
                        <flux:sidebar.item icon="shield-check" :href="route('insurance-policies.index')" :current="request()->routeIs('insurance-policies.*')" wire:navigate>
                            {{ __('Versicherungen') }}
                        </flux:sidebar.item>
                    @endif
                    <flux:sidebar.item icon="briefcase" :href="route('emergency-resources.index')" :current="request()->routeIs('emergency-resources.*')" wire:navigate>
                        {{ __('Sofortmittel') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shield-exclamation" :href="route('emergency-levels.index')" :current="request()->routeIs('emergency-levels.*')" wire:navigate>
                        {{ __('Notfall-Level') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Ernstfall')" class="grid">
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
                    @if (auth()->user()->isCurrentTeamAdmin())
                        <flux:sidebar.item icon="document-duplicate" :href="route('communication-templates.index')" :current="request()->routeIs('communication-templates.*')" wire:navigate>
                            {{ __('Vorlagen') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('handbook-tests.index')" :current="request()->routeIs('handbook-tests.*')" wire:navigate>
                            {{ __('Testplan') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Team & Freigaben')" class="grid">
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
                        <flux:sidebar.item icon="document-text" :href="route('handbook-versions.index')" :current="request()->routeIs('handbook-versions.*')" wire:navigate>
                            {{ __('Versionshistorie') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @if (auth()->user()?->isSuperAdmin())
                    <flux:sidebar.group :heading="__('Administration')" class="grid">
                        <flux:sidebar.item icon="shield-check" :href="route('admin.index')" :current="request()->routeIs('admin.index')" wire:navigate>
                            {{ __('Übersicht') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-office" :href="route('admin.companies.index')" :current="request()->routeIs('admin.companies.*')" wire:navigate>
                            {{ __('Kunden') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-duplicate" :href="route('admin.scenarios.index')" :current="request()->routeIs('admin.scenarios.*')" wire:navigate>
                            {{ __('Globale Szenarien') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

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

        @fluxScripts
    </body>
</html>
