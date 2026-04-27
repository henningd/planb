<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recovery-Zeitplan')] class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Recovery-Zeitplan') }}</flux:heading>
    <flux:subheading>{{ __('RTO-Zeitleiste über die Wiederanlauf-Kette.') }}</flux:subheading>
</section>
