<?php

use App\Models\Department;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Abteilungen')] class extends Component {
    public ?string $editingId = null;

    public string $name = '';

    public string $description = '';

    public int $sort = 0;

    public ?string $deletingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, Department>
     */
    #[Computed]
    public function departments(): Collection
    {
        return Department::withCount('employees')->orderBy('sort')->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('department-form')->show();
    }

    public function openEdit(string $id): void
    {
        $department = Department::findOrFail($id);

        $this->editingId = $department->id;
        $this->name = $department->name;
        $this->description = (string) $department->description;
        $this->sort = $department->sort;

        Flux::modal('department-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $companyId = Auth::user()->currentCompany()?->id;

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort' => ['integer', 'min:0'],
        ]);

        if ($this->editingId) {
            Department::findOrFail($this->editingId)->update($validated);
        } else {
            Department::create($validated);
        }

        Flux::modal('department-form')->close();
        $this->resetForm();
        unset($this->departments);

        Flux::toast(variant: 'success', text: __('Abteilung gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('department-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Department::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->departments);
            Flux::modal('department-delete')->close();
            Flux::toast(variant: 'success', text: __('Abteilung gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'sort']);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Abteilungen') }}</flux:heading>
            <flux:subheading>
                {{ __('Organisations-Einheiten Ihrer Firma — pro Mitarbeiter wählbar im Mitarbeiter-Formular.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neue Abteilung') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->departments as $department)
            <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $department->name }}</flux:heading>
                        <flux:badge color="zinc" size="sm" class="mt-1">
                            {{ trans_choice('{0} Keine Mitarbeiter|{1} 1 Mitarbeiter|[2,*] :count Mitarbeiter', $department->employees_count, ['count' => $department->employees_count]) }}
                        </flux:badge>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $department->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $department->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                @if ($department->description)
                    <flux:text class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $department->description }}</flux:text>
                @endif
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine Abteilung hinterlegt.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="department-form" class="max-w-xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Abteilung bearbeiten') : __('Neue Abteilung') }}
                </flux:heading>
                <flux:subheading>{{ __('Bezeichnung der Organisations-Einheit.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="z. B. Buchhaltung" />
            <flux:textarea wire:model="description" :label="__('Beschreibung')" rows="3" />
            <flux:input wire:model="sort" :label="__('Sortierung')" type="number" min="0" />

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

    <flux:modal name="department-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Abteilung löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Mitarbeiter dieser Abteilung verlieren ihre Zuordnung — die Mitarbeiter selbst bleiben erhalten.') }}</flux:subheading>
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
