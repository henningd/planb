<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PreferenceController extends Controller
{
    /**
     * Persist the expanded/collapsed state of a sidebar group for the
     * authenticated user. Whitelist of group keys is enforced server-side
     * to avoid storing arbitrary data in the preferences JSON.
     */
    public function updateSidebarGroup(Request $request): Response
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'in:handbook,emergency,team,settings,administration'],
            'expanded' => ['required', 'boolean'],
        ]);

        $request->user()->setSidebarGroupExpanded(
            $validated['key'],
            $validated['expanded'],
        );

        return response()->noContent();
    }
}
