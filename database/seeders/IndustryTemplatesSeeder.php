<?php

namespace Database\Seeders;

use App\Models\IndustryTemplate;
use Database\Seeders\IndustryTemplates\ArztpraxisTemplate;
use Database\Seeders\IndustryTemplates\Contract;
use Database\Seeders\IndustryTemplates\EinzelhandelTemplate;
use Database\Seeders\IndustryTemplates\ElektrobetriebTemplate;
use Database\Seeders\IndustryTemplates\HeizungSanitaerTemplate;
use Database\Seeders\IndustryTemplates\HotelTemplate;
use Database\Seeders\IndustryTemplates\KleineFertigungTemplate;
use Database\Seeders\IndustryTemplates\OnlineshopTemplate;
use Database\Seeders\IndustryTemplates\PflegedienstTemplate;
use Database\Seeders\IndustryTemplates\SteuerberatungTemplate;
use Illuminate\Database\Seeder;

class IndustryTemplatesSeeder extends Seeder
{
    /**
     * Liste aller Template-Klassen, die hier per `make` instanziiert werden.
     * Neue Templates einfach hier eintragen — der Seeder ist idempotent
     * (per `name + industry`).
     *
     * @var list<class-string<Contract>>
     */
    private array $templates = [
        // Werden von den Branchen-Agenten ergänzt:
        ElektrobetriebTemplate::class,
        HeizungSanitaerTemplate::class,
        EinzelhandelTemplate::class,
        OnlineshopTemplate::class,
        SteuerberatungTemplate::class,
        ArztpraxisTemplate::class,
        HotelTemplate::class,
        KleineFertigungTemplate::class,
        PflegedienstTemplate::class,
    ];

    public function run(): void
    {
        foreach ($this->templates as $cls) {
            if (! class_exists($cls)) {
                $this->command?->warn("Template-Klasse fehlt: {$cls} — übersprungen.");

                continue;
            }
            /** @var Contract $tpl */
            $tpl = new $cls;

            IndustryTemplate::updateOrCreate(
                ['name' => $tpl->name(), 'industry' => $tpl->industry()->value],
                [
                    'description' => $tpl->description(),
                    'sort' => $tpl->sort(),
                    'is_active' => true,
                    'payload' => $tpl->payload(),
                ],
            );

            $this->command?->info("Template angelegt/aktualisiert: {$tpl->name()}");
        }
    }
}
