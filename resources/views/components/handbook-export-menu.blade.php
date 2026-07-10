{{-- Drei fachliche PDF-Exporttypen zum Handbuch (Punkt 14). --}}
<flux:dropdown position="bottom" align="end">
    <flux:button variant="primary" icon="arrow-down-tray" icon:trailing="chevron-down">
        {{ __('Exportieren') }}
    </flux:button>
    <flux:menu>
        <flux:menu.item icon="fire" :href="route('handbook-export.ernstfall')" target="_blank">
            {{ __('Ernstfall-Handbuch') }}
        </flux:menu.item>
        @if (config('features.bia'))
            <flux:menu.item icon="clipboard-document-check" :href="route('handbook-export.audit')" target="_blank">
                {{ __('Audit-Bericht') }}
            </flux:menu.item>
        @endif
        <flux:menu.item icon="archive-box" :href="route('handbook-export.full')" target="_blank">
            {{ __('Vollständiger Export') }}
        </flux:menu.item>
    </flux:menu>
</flux:dropdown>
