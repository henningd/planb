<?php

namespace App\Support;

/**
 * Kuratierte Bibel-Verse für das Krisen-Cockpit. Zwei Listen à 20 Versen:
 * - peace: Vorsorge, Wachsamkeit, Vertrauen — wenn kein Notfall läuft
 * - crisis: Stärke, Beistand, Mut — wenn ein Szenario aktiv ist
 *
 * Übersetzung: Lutherbibel 1912 (gemeinfrei). Wer eine andere Übersetzung
 * nutzen möchte, kann die Konstanten direkt anpassen.
 *
 * @phpstan-type Verse array{text: string, reference: string}
 */
class BibleVerses
{
    /** @var list<Verse> */
    private const PEACE = [
        ['text' => 'Der Kluge sieht das Unglück und verbirgt sich; die Unverständigen aber gehen hindurch und müssen büßen.', 'reference' => 'Sprüche 22,3'],
        ['text' => 'Wer von euch ist, der einen Turm bauen will und sitzt nicht zuvor und überschlägt die Kosten, ob er\'s habe hinauszuführen?', 'reference' => 'Lukas 14,28'],
        ['text' => 'Die Anschläge eines Emsigen bringen Überfluß; wer aber allzu rasch ist, dem wird\'s mangeln.', 'reference' => 'Sprüche 21,5'],
        ['text' => 'Befiehl dem HERRN deine Werke, so werden deine Anschläge fortgehen.', 'reference' => 'Sprüche 16,3'],
        ['text' => 'Wo der HERR nicht das Haus baut, so arbeiten umsonst, die daran bauen. Wo der HERR nicht die Stadt behütet, so wacht der Wächter umsonst.', 'reference' => 'Psalm 127,1'],
        ['text' => 'Bestelle deine Arbeit draußen und richte deinen Acker zu; danach baue dein Haus.', 'reference' => 'Sprüche 24,27'],
        ['text' => 'Ein jegliches hat seine Zeit, und alles Vornehmen unter dem Himmel hat seine Stunde.', 'reference' => 'Prediger 3,1'],
        ['text' => 'Ein Unverständiger glaubt alles; aber ein Kluger merkt auf seinen Gang.', 'reference' => 'Sprüche 14,15'],
        ['text' => 'Darum, wer diese meine Rede hört und tut sie, den vergleiche ich einem klugen Mann, der sein Haus auf einen Felsen baute.', 'reference' => 'Matthäus 7,24'],
        ['text' => 'Wer unter dem Schirm des Höchsten sitzt und unter dem Schatten des Allmächtigen bleibt.', 'reference' => 'Psalm 91,1'],
        ['text' => 'Verlaß dich auf den HERRN von ganzem Herzen und verlaß dich nicht auf deinen Verstand; sondern gedenke an ihn in allen deinen Wegen, so wird er dich recht führen.', 'reference' => 'Sprüche 3,5–6'],
        ['text' => 'Seid nüchtern und wachet; denn euer Widersacher, der Teufel, geht umher wie ein brüllender Löwe und sucht, welchen er verschlinge.', 'reference' => '1. Petrus 5,8'],
        ['text' => 'Darum seid auch ihr bereit; denn des Menschen Sohn wird kommen zu einer Stunde, da ihr\'s nicht meinet.', 'reference' => 'Lukas 12,40'],
        ['text' => 'Gehe hin zur Ameise, du Fauler; siehe ihre Weise an und lerne!', 'reference' => 'Sprüche 6,6'],
        ['text' => 'Sehet zu, wachet und betet; denn ihr wisset nicht, wann es Zeit ist.', 'reference' => 'Markus 13,33'],
        ['text' => 'Siehst du einen Mann behend in seinem Geschäft, der wird vor den Königen stehen und wird nicht stehen vor den Geringen.', 'reference' => 'Sprüche 22,29'],
        ['text' => 'Wo aber der Wächter sähe das Schwert kommen und die Drommete nicht bliese und sein Volk nicht warnte, …', 'reference' => 'Hesekiel 33,6'],
        ['text' => 'Teile aus unter sieben und unter acht; denn du weißt nicht, was für Unglück auf Erden kommen wird.', 'reference' => 'Prediger 11,2'],
        ['text' => 'Lasset alles ehrbar und ordentlich zugehen.', 'reference' => '1. Korinther 14,40'],
        ['text' => 'Der Faulen Hand verarmet; aber der Fleißigen Hand macht reich.', 'reference' => 'Sprüche 10,4'],
    ];

    /** @var list<Verse> */
    private const CRISIS = [
        ['text' => 'Gott ist unsre Zuversicht und Stärke, eine Hilfe in den großen Nöten, die uns getroffen haben.', 'reference' => 'Psalm 46,2'],
        ['text' => 'Fürchte dich nicht, ich bin mit dir; weiche nicht, denn ich bin dein Gott! Ich stärke dich, ich helfe dir auch, ich halte dich durch die rechte Hand meiner Gerechtigkeit.', 'reference' => 'Jesaja 41,10'],
        ['text' => 'Und ob ich schon wanderte im finstern Tal, fürchte ich kein Unglück; denn du bist bei mir, dein Stecken und Stab trösten mich.', 'reference' => 'Psalm 23,4'],
        ['text' => 'Siehe, ich habe dir geboten, daß du getrost und freudig seist. Laß dir nicht grauen und entsetze dich nicht; denn der HERR, dein Gott, ist mit dir in allem, was du tun wirst.', 'reference' => 'Josua 1,9'],
        ['text' => 'Ich vermag alles durch den, der mich mächtig macht, Christus.', 'reference' => 'Philipper 4,13'],
        ['text' => 'Denn Gott hat uns nicht gegeben den Geist der Furcht, sondern der Kraft und der Liebe und der Zucht.', 'reference' => '2. Timotheus 1,7'],
        ['text' => 'HERR, mein Fels, meine Burg, mein Erretter; mein Gott, mein Hort, auf den ich traue, mein Schild und Horn meines Heils und mein Schutz!', 'reference' => 'Psalm 18,3'],
        ['text' => 'Wir wissen aber, daß denen, die Gott lieben, alle Dinge zum Besten dienen.', 'reference' => 'Römer 8,28'],
        ['text' => 'Ich hebe meine Augen auf zu den Bergen, von welchen mir Hilfe kommt. Meine Hilfe kommt von dem HERRN, der Himmel und Erde gemacht hat.', 'reference' => 'Psalm 121,1–2'],
        ['text' => 'Und so du durchs Wasser gehst, will ich bei dir sein, daß dich die Ströme nicht sollen ersäufen; und so du ins Feuer gehst, sollst du nicht brennen.', 'reference' => 'Jesaja 43,2'],
        ['text' => 'Wenn die Gerechten schreien, so hört der HERR und errettet sie aus all ihrer Not.', 'reference' => 'Psalm 34,18'],
        ['text' => 'Kommet her zu mir alle, die ihr mühselig und beladen seid; ich will euch erquicken.', 'reference' => 'Matthäus 11,28'],
        ['text' => 'Der HERR ist mein Licht und mein Heil; vor wem sollte ich mich fürchten? Der HERR ist meines Lebens Kraft; vor wem sollte mir grauen?', 'reference' => 'Psalm 27,1'],
        ['text' => 'Aber Gott ist getreu, der euch nicht läßt versuchen über euer Vermögen, sondern macht, daß die Versuchung so ein Ende gewinne, daß ihr\'s könnet ertragen.', 'reference' => '1. Korinther 10,13'],
        ['text' => 'Er hat gesagt: »Ich will dich nicht verlassen noch versäumen«; also daß wir dürfen sagen: »Der HERR ist mein Helfer, ich will mich nicht fürchten; was sollte mir ein Mensch tun?«', 'reference' => 'Hebräer 13,5–6'],
        ['text' => 'Meine lieben Brüder, achtet es eitel Freude, wenn ihr in mancherlei Anfechtungen fallet, und wisset, daß euer Glaube, wenn er rechtschaffen ist, Geduld wirket.', 'reference' => 'Jakobus 1,2–3'],
        ['text' => 'Wenn ich mitten in der Angst wandle, so erquickst du mich; du breitest deine Hand über den Zorn meiner Feinde und hilfst mir mit deiner Rechten.', 'reference' => 'Psalm 138,7'],
        ['text' => 'Der HERR ist gütig und eine Feste zur Zeit der Not und kennt die, die auf ihn trauen.', 'reference' => 'Nahum 1,7'],
        ['text' => 'Er gibt den Müden Kraft, und Stärke genug dem Unvermögenden.', 'reference' => 'Jesaja 40,29'],
        ['text' => 'Der Name des HERRN ist eine feste Burg; der Gerechte läuft dahin und wird beschirmt.', 'reference' => 'Sprüche 18,10'],
    ];

    /**
     * Liefert einen zufälligen Vers aus der angegebenen Liste.
     *
     * @return Verse|null null nur, wenn Feature deaktiviert oder Situation unbekannt.
     */
    public static function random(string $situation): ?array
    {
        if (! config('features.bible_verses')) {
            return null;
        }

        $list = match ($situation) {
            'peace' => self::PEACE,
            'crisis' => self::CRISIS,
            default => null,
        };

        if ($list === null || $list === []) {
            return null;
        }

        return $list[array_rand($list)];
    }

    /**
     * @return list<Verse>
     */
    public static function peace(): array
    {
        return self::PEACE;
    }

    /**
     * @return list<Verse>
     */
    public static function crisis(): array
    {
        return self::CRISIS;
    }
}
