<?php

namespace App\Support\Manual;

use League\CommonMark\CommonMarkConverter;

class ManualRenderer
{
    public static function toHtml(string $markdown): string
    {
        $converter = new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        $html = (string) $converter->convert($markdown);

        // Heading-IDs nachträglich injizieren, damit der Sidebar-TOC scrollen kann.
        return preg_replace_callback(
            '/<(h[23])>(.+?)<\/\1>/u',
            function ($m) {
                $tag = $m[1];
                $title = strip_tags($m[2]);
                $id = ManualCatalog::slugify($title);

                return '<'.$tag.' id="'.$id.'">'.$m[2].'</'.$tag.'>';
            },
            $html,
        ) ?? $html;
    }
}
