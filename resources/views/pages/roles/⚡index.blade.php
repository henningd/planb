<?php

use App\Models\Employee;
use App\Models\Role;
use App\Support\AssignmentSync;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Rollen')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $description = '';

    public int $sort = 0;

    /** @var array<int, string> */
    public array $assignedEmployeeIds = [];

    public ?string $deletingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::with('employees')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employeeOptions(): Collection
    {
        return Employee::orderBy('last_name')->orderBy('first_name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('role-form')->show();
    }

    public function openEdit(string $id): void
    {
        $role = Role::with('employees')->findOrFail($id);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->description = (string) $role->description;
        $this->sort = $role->sort;
        $this->assignedEmployeeIds = $role->employees->pluck('id')->all();

        Flux::modal('role-form')->show();
    }

    public function toggleAssignedEmployee(string $employeeId): void
    {
        if (in_array($employeeId, $this->assignedEmployeeIds, true)) {
            $this->assignedEmployeeIds = array_values(array_filter(
                $this->assignedEmployeeIds,
                fn ($id) => $id !== $employeeId,
            ));
        } else {
            $this->assignedEmployeeIds[] = $employeeId;
        }
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
            'assignedEmployeeIds' => ['array'],
            'assignedEmployeeIds.*' => ['uuid', 'exists:employees,id'],
        ]);

        $role = $this->editingId
            ? Role::findOrFail($this->editingId)
            : new Role;

        $role->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort' => $validated['sort'] ?? 0,
        ])->save();

        AssignmentSync::sync($role, $role->employees(), $validated['assignedEmployeeIds'] ?? []);

        Flux::modal('role-form')->close();
        $this->resetForm();
        unset($this->roles);

        Flux::toast(variant: 'success', text: __('Rolle gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('role-delete')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $role = Role::findOrFail($this->deletingId);

        if ($role->isSystem()) {
            Flux::toast(variant: 'warning', text: __('Systemrollen können nicht gelöscht werden.'));
            $this->deletingId = null;
            Flux::modal('role-delete')->close();

            return;
        }

        $role->delete();
        $this->deletingId = null;
        unset($this->roles);
        Flux::modal('role-delete')->close();
        Flux::toast(variant: 'success', text: __('Rolle gelöscht.'));
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'sort', 'assignedEmployeeIds']);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Rollen') }}</flux:heading>
            <flux:subheading>
                {{ __('Organisatorische Rollen wie Geschäftsleitung, Buchhaltung oder Werkstatt. Jede Rolle bündelt beliebig viele Mitarbeitende.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Rolle') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->roles as $role)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="base">{{ $role->name }}</flux:heading>
                            @if ($role->isSystem())
                                <flux:badge color="indigo" size="sm" icon="shield-check">{{ __('System') }}</flux:badge>
                            @endif
                        </div>
                        @if ($role->description)
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $role->description }}
                            </flux:text>
                        @endif
                        <div class="mt-2">
                            <flux:badge color="zinc" size="sm">
                                {{ trans_choice(':count Mitarbeitender|:count Mitarbeitende', $role->employees->count(), ['count' => $role->employees->count()]) }}
                            </flux:badge>
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $role->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            @unless ($role->isSystem())
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $role->id }}')">
                                    {{ __('Löschen') }}
                                </flux:menu.item>
                            @endunless
                        </flux:menu>
                    </flux:dropdown>
                </div>

                @if ($role->employees->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-1.5 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        @foreach ($role->employees as $emp)
                            <span class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                <flux:icon.user class="h-3 w-3 text-zinc-400" />
                                {{ $emp->fullName() }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <flux:text class="mt-4 border-t border-zinc-100 pt-3 text-xs italic text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        {{ __('Noch keine Mitarbeitenden zugeordnet.') }}
                    </flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Rollen angelegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="role-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Rolle bearbeiten') : __('Neue Rolle anlegen') }}
                </flux:heading>
                <flux:subheading>
                    {{ __('Name der Rolle, optionale Beschreibung und Zuordnung der Mitarbeitenden.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Buchhaltung" />
            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="2" placeholder="optional" />
            <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />

            <div class="space-y-2">
                <flux:label>{{ __('Zugeordnete Mitarbeitende') }}</flux:label>
                @if ($this->employeeOptions->isEmpty())
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Noch keine Mitarbeitenden angelegt.') }}
                    </flux:text>
                @else
                    <div class="max-h-72 space-y-1 overflow-y-auto rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                        @foreach ($this->employeeOptions as $emp)
                            @php($checked = in_array($emp->id, $assignedEmployeeIds, true))
                            <button
                                type="button"
                                wire:click.prevent="toggleAssignedEmployee('{{ $emp->id }}')"
                                class="flex w-full items-center gap-3 rounded-md px-2 py-1.5 text-left text-sm transition hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $checked ? 'bg-emerald-50 dark:bg-emerald-950/40' : '' }}">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border {{ $checked ? 'border-emerald-600 bg-emerald-600' : 'border-zinc-300 dark:border-zinc-600' }}">
                                    @if ($checked)
                                        <flux:icon.check class="h-3 w-3 text-white" />
                                    @endif
                                </span>
                                <span class="flex-1">
                                    <span class="font-medium">{{ $emp->fullName() }}</span>
                                    @if ($emp->position)
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">· {{ $emp->position }}</span>
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </div>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ trans_choice(':count Mitarbeitender ausgewählt|:count Mitarbeitende ausgewählt', count($assignedEmployeeIds), ['count' => count($assignedEmployeeIds)]) }}
                    </flux:text>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Speichern') : __('Anlegen') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="role-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Rolle löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Die Zuordnungen zu Mitarbeitenden werden ebenfalls entfernt.') }}</flux:subheading>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" type="button">{{ __('Abbrechen') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" type="button" wire:click="delete">{{ __('Löschen') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
