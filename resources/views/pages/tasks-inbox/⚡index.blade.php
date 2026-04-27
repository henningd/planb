<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Aufgaben-Inbox')] class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Aufgaben-Inbox') }}</flux:heading>
    <flux:subheading>{{ __('Zentrale Sicht aller System-Aufgaben.') }}</flux:subheading>
</section>
