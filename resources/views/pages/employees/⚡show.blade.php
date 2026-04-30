<?php

use App\Enums\RaciRole;
use App\Enums\SystemOwnership;
use App\Models\Employee;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mitarbeiter')] class extends Component {
    public Employee $employee;

    public bool $confirmingDelete = false;

    public function mount(Employee $employee): void
    {
        abort_unless(Auth::user()?->currentCompany(), 403);

        $employee->load([
            'department',
            'location',
            'managers' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
            'reports' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name'),
            'roles' => fn ($q) => $q->orderBy('sort')->orderBy('name'),
            'systems' => fn ($q) => $q->orderBy('name'),
            'tasks.system',
            'roles.systemTasks.system',
        ]);

        $this->employee = $employee;
    }

    public function delete()
    {
        $this->employee->delete();

        Flux::toast(variant: 'success', text: __('Mitarbeiter gelöscht.'));

        return redirect()->route('employees.index');
    }
}; ?>

<section class="w-full">
    <div class="mb-2">
        <flux:link :href="route('employees.index')" wire:navigate class="text-sm">
            ← {{ __('Alle Mitarbeiter') }}
        </flux:link>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-start justify-between gap-4 p-6">
            <div class="flex min-w-0 flex-1 items-start gap-4">
                <flux:avatar :name="$employee->fullName()" size="lg" class="shrink-0" />
                <div class="min-w-0">
                    <flux:heading size="xl">{{ $employee->nameLastFirst() }}</flux:heading>
                    @if ($employee->position)
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ $employee->position }}</flux:text>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        @if ($employee->is_key_personnel)
                            <flux:badge color="amber" size="sm" icon="star">{{ __('Schlüsselmitarbeiter') }}</flux:badge>
                        @endif
                        @if (config('features.departments') && $employee->department)
                            <flux:badge color="zinc" size="sm">{{ $employee->department->name }}</flux:badge>
                        @endif
                        @if ($employee->location)
                            <flux:badge color="zinc" size="sm" icon="map-pin">{{ $employee->location->name }}</flux:badge>
                        @endif
                        @foreach ($employee->systems->sortBy('name') as $system)
                            <flux:badge color="zinc" size="sm" icon="server-stack">{{ $system->name }}</flux:badge>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <flux:button icon="pencil" :href="route('employees.edit', $employee)" wire:navigate>
                    {{ __('Bearbeiten') }}
                </flux:button>
                <flux:button variant="danger" icon="trash" wire:click="$set('confirmingDelete', true)">
                    {{ __('Löschen') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Kontakt') }}</flux:heading>
            <dl class="mt-4 space-y-3 text-sm">
                @if ($employee->mobile_phone)
                    <div class="flex items-start gap-3">
                        <flux:icon.device-phone-mobile class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Mobil (dienstlich)') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100"><a href="tel:{{ $employee->mobile_phone }}" class="hover:underline">{{ $employee->mobile_phone }}</a></dd>
                        </div>
                    </div>
                @endif
                @if ($employee->work_phone)
                    <div class="flex items-start gap-3">
                        <flux:icon.phone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Tel. (Büro)') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100"><a href="tel:{{ $employee->work_phone }}" class="hover:underline">{{ $employee->work_phone }}</a></dd>
                        </div>
                    </div>
                @endif
                @if ($employee->private_phone)
                    <div class="flex items-start gap-3">
                        <flux:icon.phone class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Privat') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100"><a href="tel:{{ $employee->private_phone }}" class="hover:underline">{{ $employee->private_phone }}</a></dd>
                        </div>
                    </div>
                @endif
                @if ($employee->email)
                    <div class="flex items-start gap-3">
                        <flux:icon.envelope class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('E-Mail') }}</dt>
                            <dd class="truncate text-zinc-900 dark:text-zinc-100"><a href="mailto:{{ $employee->email }}" class="hover:underline">{{ $employee->email }}</a></dd>
                        </div>
                    </div>
                @endif
                @if ($employee->emergency_contact)
                    <div class="flex items-start gap-3">
                        <flux:icon.lifebuoy class="mt-0.5 h-4 w-4 shrink-0 text-zinc-400" />
                        <div class="min-w-0">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Notfallkontakt') }}</dt>
                            <dd class="whitespace-pre-line text-zinc-900 dark:text-zinc-100">{{ $employee->emergency_contact }}</dd>
                        </div>
                    </div>
                @endif
                @if (! $employee->mobile_phone && ! $employee->work_phone && ! $employee->private_phone && ! $employee->email && ! $employee->emergency_contact)
                    <flux:text class="text-sm text-zinc-500">{{ __('Keine Kontaktdaten erfasst.') }}</flux:text>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Hierarchie') }}</flux:heading>
            <div class="mt-4 space-y-4 text-sm">
                <div>
                    <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="user-circle" class="h-4 w-4" />
                        {{ __('Vorgesetzt von') }}
                    </div>
                    @if ($employee->managers->isEmpty())
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Niemand zugeordnet.') }}</flux:text>
                    @else
                        <ul class="mt-1 space-y-1">
                            @foreach ($employee->managers as $manager)
                                <li>
                                    <flux:link :href="route('employees.show', $manager)" wire:navigate>{{ $manager->nameLastFirst() }}</flux:link>
                                    @if ($manager->position) <span class="text-zinc-500 dark:text-zinc-400">· {{ $manager->position }}</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div>
                    <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="users" class="h-4 w-4" />
                        {{ trans_choice('{1} Direkt unterstellt|[2,*] Direkt unterstellt (:count)', $employee->reports->count(), ['count' => $employee->reports->count()]) }}
                    </div>
                    @if ($employee->reports->isEmpty())
                        <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Niemand unterstellt.') }}</flux:text>
                    @else
                        <ul class="mt-1 space-y-1">
                            @foreach ($employee->reports as $report)
                                <li>
                                    <flux:link :href="route('employees.show', $report)" wire:navigate>{{ $report->nameLastFirst() }}</flux:link>
                                    @if ($report->position) <span class="text-zinc-500 dark:text-zinc-400">· {{ $report->position }}</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Systeme') }}</flux:heading>
            @php
                // Pro System sammeln: direkte Mitarbeiter-Zuordnung am System,
                // direkte Aufgaben-Zuordnung, indirekte Aufgaben-Zuordnung über
                // eine Rolle, der der Mitarbeiter angehört.
                //
                // tasks: map task_id => ['task' => SystemTask, 'sources' => [
                //     ['kind' => 'direct', 'is_deputy' => bool],
                //     ['kind' => 'via_role', 'role_name' => string,
                //      'employee_is_deputy_in_role' => bool],
                // ]]
                $systemEntries = [];
                $ensureSystem = function (\App\Models\System $sys, $directPivot = null) use (&$systemEntries) {
                    if (! isset($systemEntries[$sys->id])) {
                        $systemEntries[$sys->id] = [
                            'system' => $sys,
                            'direct_pivot' => $directPivot,
                            'tasks' => [],
                        ];
                    } elseif ($directPivot !== null && $systemEntries[$sys->id]['direct_pivot'] === null) {
                        $systemEntries[$sys->id]['direct_pivot'] = $directPivot;
                    }
                };
                $ensureTask = function (string $sid, \App\Models\SystemTask $task) use (&$systemEntries) {
                    if (! isset($systemEntries[$sid]['tasks'][$task->id])) {
                        $systemEntries[$sid]['tasks'][$task->id] = ['task' => $task, 'sources' => []];
                    }
                };

                foreach ($employee->systems as $sys) {
                    $ensureSystem($sys, $sys->pivot);
                }
                foreach ($employee->tasks as $task) {
                    if (! $task->system) {
                        continue;
                    }
                    $ensureSystem($task->system);
                    $ensureTask($task->system->id, $task);
                    $systemEntries[$task->system->id]['tasks'][$task->id]['sources'][] = [
                        'kind' => 'direct',
                        'is_deputy' => (bool) ($task->pivot->is_deputy ?? false),
                        'raci' => RaciRole::tryFrom((string) ($task->pivot->raci_role ?? '')),
                    ];
                }
                foreach ($employee->roles as $role) {
                    foreach ($role->systemTasks as $task) {
                        if (! $task->system) {
                            continue;
                        }
                        $ensureSystem($task->system);
                        $ensureTask($task->system->id, $task);
                        $systemEntries[$task->system->id]['tasks'][$task->id]['sources'][] = [
                            'kind' => 'via_role',
                            'role_name' => $role->name,
                            'employee_is_deputy_in_role' => (bool) ($role->pivot->is_deputy ?? false),
                            'raci' => RaciRole::tryFrom((string) ($task->pivot->raci_role ?? '')),
                        ];
                    }
                }

                $systemEntries = collect($systemEntries)
                    ->sortBy(fn ($e) => mb_strtolower($e['system']->name))
                    ->values();
            @endphp

            @if ($systemEntries->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">
                    {{ __('Keine System-Zuordnungen — weder direkt am System noch über Aufgaben.') }}
                </flux:text>
            @else
                <ul class="mt-4 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    @foreach ($systemEntries as $entry)
                        @php
                            $sys = $entry['system'];
                            $direct = $entry['direct_pivot'];
                            $ownership = $direct ? SystemOwnership::tryFrom((string) ($direct->ownership_kind ?? '')) : null;
                            $directIsDeputy = (bool) ($direct->is_deputy ?? false);
                            $note = (string) ($direct->note ?? '');
                            $tasks = collect($entry['tasks'])->sortBy(fn ($t) => mb_strtolower($t['task']->title));
                        @endphp
                        <li class="py-3">
                            <div class="flex items-center justify-between gap-3">
                                <flux:link :href="route('systems.show', $sys)" wire:navigate class="text-zinc-900 dark:text-zinc-100">
                                    {{ $sys->name }}
                                </flux:link>
                                <div class="flex items-center gap-1.5">
                                    @if ($ownership)
                                        <flux:badge :color="$ownership->badgeColor()" size="sm" :icon="$ownership->icon()">
                                            {{ $ownership->shortLabel() }}
                                        </flux:badge>
                                    @endif
                                    @if ($direct && $directIsDeputy)
                                        <flux:badge color="purple" size="sm">{{ __('Vertretung') }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                            @if ($note !== '')
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $note }}</div>
                            @endif
                            @if ($tasks->isNotEmpty())
                                <ul class="mt-2 space-y-2 pl-4">
                                    @foreach ($tasks as $taskEntry)
                                        @php $task = $taskEntry['task']; @endphp
                                        <li class="text-xs">
                                            <div class="flex items-center gap-1.5 font-medium text-zinc-700 dark:text-zinc-300">
                                                <flux:icon name="check-circle" class="h-3.5 w-3.5 text-zinc-400" />
                                                {{ $task->title }}
                                            </div>
                                            <ul class="mt-1 ml-5 space-y-0.5">
                                                @foreach ($taskEntry['sources'] as $source)
                                                    <li class="flex items-center justify-between gap-2 rounded px-2 py-0.5 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                                        @if ($source['kind'] === 'direct')
                                                            <flux:badge :color="$source['is_deputy'] ? 'purple' : 'emerald'" size="sm">
                                                                {{ $source['is_deputy'] ? __('Direkt · Vertretung') : __('Direkt · Hauptperson') }}
                                                            </flux:badge>
                                                        @else
                                                            <flux:badge color="sky" size="sm" icon="user-group">
                                                                {{ __('via Rolle :role', ['role' => $source['role_name']]) }}@if ($source['employee_is_deputy_in_role']) · {{ __('Vertretung') }}@endif
                                                            </flux:badge>
                                                        @endif
                                                        @if ($source['raci'])
                                                            <flux:badge :color="$source['raci']->badgeColor()" size="sm" :title="$source['raci']->description()">
                                                                {{ $source['raci']->value }} · {{ $source['raci']->label() }}
                                                            </flux:badge>
                                                        @else
                                                            <span class="text-zinc-400 dark:text-zinc-500">{{ __('— keine RACI —') }}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Rollen') }}</flux:heading>
            @if ($employee->roles->isEmpty())
                <flux:text class="mt-3 text-sm text-zinc-500">
                    {{ __('Keine Rollen zugeordnet.') }}
                </flux:text>
            @else
                <ul class="mt-4 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    @foreach ($employee->roles as $role)
                        @php $isDeputy = (bool) ($role->pivot->is_deputy ?? false); @endphp
                        <li class="flex items-center justify-between gap-3 py-2">
                            <span class="flex items-center gap-2">
                                <flux:link :href="route('roles.show', $role)" wire:navigate class="text-zinc-900 dark:text-zinc-100">
                                    {{ $role->name }}
                                </flux:link>
                                @if ($role->isSystem())
                                    <flux:badge color="blue" size="sm">{{ __('Pflichtrolle') }}</flux:badge>
                                @endif
                            </span>
                            <flux:badge :color="$isDeputy ? 'purple' : 'emerald'" size="sm">
                                {{ $isDeputy ? __('Stellvertretung') : __('Hauptperson') }}
                            </flux:badge>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($employee->notes)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <flux:heading size="lg">{{ __('Notizen') }}</flux:heading>
                <flux:text class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $employee->notes }}</flux:text>
            </div>
        @endif
    </div>

    <div class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
        {{ __('Zuletzt aktualisiert :at', ['at' => $employee->updated_at?->isoFormat('LLL')]) }}
    </div>

    <flux:modal wire:model.self="confirmingDelete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Mitarbeiter löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:button type="button" variant="filled" wire:click="$set('confirmingDelete', false)">
                    {{ __('Abbrechen') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
