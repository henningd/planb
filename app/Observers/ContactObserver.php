<?php

namespace App\Observers;

use App\Models\Contact;
use App\Scopes\CurrentCompanyScope;

class ContactObserver
{
    /**
     * Auto-promote the very first contact of a company to primary.
     * Ensures the invariant that every company has at least one primary
     * contact as soon as contacts exist.
     */
    public function creating(Contact $contact): void
    {
        if ($contact->is_primary || empty($contact->company_id)) {
            return;
        }

        $primaryExists = Contact::withoutGlobalScope(CurrentCompanyScope::class)
            ->where('company_id', $contact->company_id)
            ->where('is_primary', true)
            ->exists();

        if (! $primaryExists) {
            $contact->is_primary = true;
        }
    }
}
