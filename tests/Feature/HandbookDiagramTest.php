<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the home page shows the handbook building blocks diagram', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Die Bausteine eines belastbaren Notfallhandbuchs')
        ->assertSee('Die Bausteine eines Notfallhandbuchs:', false)
        ->assertSee('Wiederanlauf-')
        ->assertSee('Meldepflichten')
        ->assertSee(route('guides.show', 'notfallhandbuch'), false);
});
