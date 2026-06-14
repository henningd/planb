@php
    $allowedPlans = ['starter' => 'Starter', 'advanced' => 'Advanced', 'enterprise' => 'Enterprise'];
    $requestedPlan = request()->string('plan')->toString();
    $intendedPlan = isset($allowedPlans[$requestedPlan]) ? $requestedPlan : null;
    if ($intendedPlan !== null) {
        session(['intended_plan' => $intendedPlan]);
    } else {
        $intendedPlan = session('intended_plan');
    }
@endphp

<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        @if ($intendedPlan && isset($allowedPlans[$intendedPlan]))
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-100">
                {{ __('Sie haben sich für :plan entschieden.', ['plan' => $allowedPlans[$intendedPlan]]) }}
                @if ($intendedPlan !== 'enterprise')
                    {{ __('14 Tage kostenlos testen, ohne Kreditkarte.') }}
                @endif
            </div>
        @endif

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form
            method="POST"
            action="{{ route('register.store') }}"
            class="flex flex-col gap-6"
            x-data="{ submitting: false }"
            x-on:submit="submitting ? $event.preventDefault() : (submitting = true)"
        >
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button" x-bind:disabled="submitting">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
