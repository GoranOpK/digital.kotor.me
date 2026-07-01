<?php

namespace App\Support;

class RichText
{
    /**
     * Dozvoljava samo osnovne tagove i sigurne http(s) linkove u opisu.
     */
    public static function formatLinks(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $html = strip_tags($html, '<a><br><p><strong><em>');

        return preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])(.*?)\2([^>]*)>(.*?)<\/a>/is',
            function (array $matches): string {
                $url = $matches[3];
                if (!preg_match('/^https?:\/\//i', $url)) {
                    return htmlspecialchars($matches[0], ENT_QUOTES, 'UTF-8');
                }

                $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $safeText = strip_tags($matches[5], '<strong><em>');

                return '<a href="'.$safeUrl.'" target="_blank" rel="noopener noreferrer">'.$safeText.'</a>';
            },
            $html
        ) ?? htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }
}
