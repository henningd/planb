<?php

namespace App\Support\Manual;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class ManualRenderer
{
    public static function toHtml(string $markdown): string
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        $converter = new MarkdownConverter($environment);
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
