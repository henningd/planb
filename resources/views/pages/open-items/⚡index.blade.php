<?php

use App\Enums\OpenItemConversion;
use App\Enums\OpenItemStatus;
use App\Models\Employee;
use App\Models\OpenItem;
use App\Models\Risk;
use App\Models\Role;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Offene Punkte')] class extends Component {
    public ?string $editingId = null;

    public string $title = '';

    public string $relevance = '';

    public string $risk_id = '';

    public string $business_process_id = '';

    public string $training_record_id = '';

    public string $responsible_employee_id = '';

    public string $responsible_role_id = '';

    public ?string $due_at = null;

    public ?string $review_at = null;

    public string $status = 'open';

    public string $conversion = '';

    public string $resolution_note = '';

    public string $filterStatus = '';

    public ?string $deletingId = null;

    #[Computed]
    public function hasCompany(): bool
    {
        return Auth::user()->currentCompany() !== null;
    }

    /**
     * @return Collection<int, OpenItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return OpenItem::query()
            ->with(['risk', 'responsible', 'responsibleRole'])
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByRaw("CASE WHEN status = 'resolved' THEN 1 ELSE 0 END")
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->orderBy('sort')
            ->get();
    }

    /**
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function employees(): Collection
    {
        return Employee::query()->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Risk>
     */
    #[Computed]
    public function risks(): Collection
    {
        return Risk::query()->orderBy('title')->get();
    }

    /**
     * @return Collection<int, \App\Models\BusinessProcess>
     */
    #[Computed]
    public function businessProcesses(): Collection
    {
        return \App\Models\BusinessProcess::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, \App\Models\TrainingRecord>
     */
    #[Computed]
    public function trainings(): Collection
    {
        if (! config('features.training_records')) {
            return new Collection;
        }

        return \App\Models\TrainingRecord::query()->orderBy('topic')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();

        Flux::modal('open-item-form')->show();
    }

    public function openEdit(string $id): void
    {
        $item = OpenItem::findOrFail($id);

        $this->editingId = $item->id;
        $this->title = (string) $item->title;
        $this->relevance = (string) $item->relevance;
        $this->risk_id = (string) ($item->risk_id ?? '');
        $this->business_process_id = (string) ($item->business_process_id ?? '');
        $this->training_record_id = (string) ($item->training_record_id ?? '');
        $this->responsible_employee_id = (string) ($item->responsible_employee_id ?? '');
        $this->responsible_role_id = (string) ($item->responsible_role_id ?? '');
        $this->due_at = $item->due_at?->toDateString();
        $this->review_at = $item->review_at?->toDateString();
        $this->status = $item->status->value;
        $this->conversion = $item->conversion?->value ?? '';
        $this->resolution_note = (string) $item->resolution_note;

        Flux::modal('open-item-form')->show();
    }

    public function save(): void
    {
        if (! $this->hasCompany) {
            Flux::toast(variant: 'warning', text: __('Bitte legen Sie zuerst ein Firmenprofil an.'));

            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'relevance' => ['nullable', 'string', 'max:5000'],
            'risk_id' => ['nullable', 'string', Rule::exists('risks', 'id')],
            'business_process_id' => ['nullable', 'string', Rule::exists('business_processes', 'id')],
            'training_record_id' => ['nullable', 'string', Rule::exists('training_records', 'id')],
            'responsible_employee_id' => ['nullable', 'string', Rule::exists('employees', 'id')],
            'responsible_role_id' => ['nullable', 'string', Rule::exists('roles', 'id')],
            'due_at' => ['nullable', 'date'],
            'review_at' => ['nullable', 'date'],
            'status' => ['required', new Enum(OpenItemStatus::class)],
            'conversion' => ['nullable', new Enum(OpenItemConversion::class)],
            'resolution_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->toArray();

        if ($payload['status'] === OpenItemStatus::Resolved->value) {
            $existing = $this->editingId ? OpenItem::find($this->editingId) : null;
            $payload['resolved_at'] = $existing?->resolved_at ?? now();
        } else {
            $payload['resolved_at'] = null;
            $payload['conversion'] = null;
        }

        if ($this->editingId) {
            OpenItem::findOrFail($this->editingId)->update($payload);
        } else {
            OpenItem::create($payload);
        }

        Flux::modal('open-item-form')->close();
        $this->resetForm();
        unset($this->items);

        Flux::toast(variant: 'success', text: __('Offener Punkt gespeichert.'));
    }

    public function confirmDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('open-item-delete')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            OpenItem::findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            unset($this->items);
            Flux::modal('open-item-delete')->close();
            Flux::toast(variant: 'success', text: __('Offener Punkt gelöscht.'));
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'title', 'relevance', 'risk_id', 'business_process_id', 'training_record_id', 'responsible_employee_id',
            'responsible_role_id', 'due_at', 'review_at', 'conversion', 'resolution_note',
        ]);
        $this->status = 'open';
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Offene Punkte / Klärpunkte') }}</flux:heading>
            <flux:subheading>
                {{ __('Bekannte, aber noch nicht final entschiedene, geprüfte, dokumentierte oder getestete Themen. Kein Risiko an sich, sondern eine nachzuhaltende Lücke – mit Verantwortlichem, Frist, Wiedervorlage und dem Nachweis, worin sie überführt wurde. Erscheint im Governance-Teil des Handbuch-PDFs.') }}
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreate" :disabled="! $this->hasCompany">
            {{ __('Neuer Punkt') }}
        </flux:button>
    </div>

    @unless ($this->hasCompany)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {{ __('Bitte legen Sie zuerst ein Firmenprofil an.') }}
        </div>
    @endunless

    <div class="mb-4">
        <flux:select wire:model.live="filterStatus" class="max-w-xs">
            <flux:select.option value="">{{ __('Alle Status') }}</flux:select.option>
            @foreach (App\Enums\OpenItemStatus::options() as $option)
                <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->items as $item)
            <div wire:key="open-item-{{ $item->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="base">{{ $item->title }}</flux:heading>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <flux:badge :color="$item->status->color()" size="sm">{{ $item->status->label() }}</flux:badge>
                            @if ($item->isOverdue())
                                <flux:badge color="red" size="sm" icon="clock">{{ __('Frist überschritten') }}</flux:badge>
                            @elseif ($item->due_at)
                                <flux:badge color="zinc" size="sm" icon="calendar">{{ __('Frist') }}: {{ $item->due_at->format('d.m.Y') }}</flux:badge>
                            @endif
                            @if ($item->review_at)
                                <flux:badge color="zinc" size="sm" icon="arrow-path">{{ __('Wiedervorlage') }}: {{ $item->review_at->format('d.m.Y') }}</flux:badge>
                            @endif
                            @if ($item->conversion)
                                <flux:badge color="emerald" size="sm">&rarr; {{ $item->conversion->shortLabel() }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <flux:dropdown align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="pencil" wire:click="openEdit('{{ $item->id }}')">
                                {{ __('Bearbeiten') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete('{{ $item->id }}')">
                                {{ __('Löschen') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <div class="mt-4 space-y-2 text-sm">
                    @if ($item->relevance)
                        <flux:text class="text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($item->relevance, 160) }}</flux:text>
                    @endif

                    @if ($item->risk)
                        <div class="flex items-center gap-2">
                            <flux:icon.shield-exclamation class="h-4 w-4 text-zinc-400" />
                            <span>{{ __('Risiko') }}: {{ $item->risk->title }}</span>
                        </div>
                    @endif

                    @if ($item->responsible || $item->responsibleRole)
                        <div class="flex items-center gap-2">
                            <flux:icon.user class="h-4 w-4 text-zinc-400" />
                            <span>
                                @if ($item->responsible){{ $item->responsible->fullName() }}@endif
                                @if ($item->responsible && $item->responsibleRole) · @endif
                                @if ($item->responsibleRole){{ __('Rolle') }}: {{ $item->responsibleRole->name }}@endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Noch keine offenen Punkte erfasst.') }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <flux:modal name="open-item-form" class="max-w-2xl">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Offenen Punkt bearbeiten') : __('Neuer offener Punkt') }}
                </flux:heading>
                <flux:subheading>{{ __('Was ist offen, warum ist es relevant, wer klärt es bis wann?') }}</flux:subheading>
            </div>

            <flux:input wire:model="title" :label="__('Was ist offen?')" type="text" placeholder="z. B. Alarmkette nachts noch nicht final geklärt" required />

            <flux:textarea wire:model="relevance" :label="__('Warum ist es relevant?')" rows="3" placeholder="z. B. Ohne geklärte Nacht-Alarmierung verzögert sich die Reaktion bei einem Ausfall außerhalb der Geschäftszeiten erheblich." />

            <flux:select wire:model="risk_id" :label="__('Verknüpftes Risiko')">
                <flux:select.option value="">{{ __('Kein Risiko verknüpft') }}</flux:select.option>
                @foreach ($this->risks as $risk)
                    <flux:select.option value="{{ $risk->id }}">{{ $risk->title }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="business_process_id" :label="__('Verknüpfter Geschäftsprozess')">
                <flux:select.option value="">{{ __('Kein Geschäftsprozess verknüpft') }}</flux:select.option>
                @foreach ($this->businessProcesses as $process)
                    <flux:select.option value="{{ $process->id }}">{{ $process->name }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->trainings->isNotEmpty())
                <flux:select wire:model="training_record_id" :label="__('Aus Schulung entstanden')">
                    <flux:select.option value="">{{ __('Nicht aus einer Schulung') }}</flux:select.option>
                    @foreach ($this->trainings as $training)
                        <flux:select.option value="{{ $training->id }}">{{ $training->topic }}@if ($training->employee) — {{ $training->employee->fullName() }}@endif</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="responsible_employee_id" :label="__('Verantwortlich (Person)')">
                    <flux:select.option value="">{{ __('Keine Person') }}</flux:select.option>
                    @foreach ($this->employees as $employee)
                        <flux:select.option value="{{ $employee->id }}">{{ $employee->fullName() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="responsible_role_id" :label="__('Verantwortlich (Rolle)')">
                    <flux:select.option value="">{{ __('Keine Rolle') }}</flux:select.option>
                    @foreach ($this->roles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="due_at" :label="__('Bis wann muss es geklärt sein?')" type="date" />
                <flux:input wire:model="review_at" :label="__('Wann erneut prüfen? (Wiedervorlage)')" type="date" />
            </div>

            <flux:select wire:model="status" :label="__('Status')" required>
                @foreach (App\Enums\OpenItemStatus::options() as $option)
                    <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="rounded-lg border border-zinc-100 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                <flux:subheading class="mb-3">{{ __('Bei Erledigung: worin wurde der Punkt überführt?') }}</flux:subheading>
                <flux:select wire:model="conversion" :label="__('Überführt in')">
                    <flux:select.option value="">{{ __('— noch nicht überführt') }}</flux:select.option>
                    @foreach (App\Enums\OpenItemConversion::options() as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:textarea wire:model="resolution_note" :label="__('Notiz zur Klärung')" rows="2" class="mt-3" placeholder="z. B. Als Maßnahme M-14 hinterlegt und im Handbuch dokumentiert." />
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

    <flux:modal name="open-item-delete" class="max-w-md">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Offenen Punkt löschen?') }}</flux:heading>
                <flux:subheading>{{ __('Diese Aktion kann nicht rückgängig gemacht werden.') }}</flux:subheading>
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
