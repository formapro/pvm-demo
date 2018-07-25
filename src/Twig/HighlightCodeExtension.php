<?php
namespace App\Twig;

class HighlightCodeExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('highlight_code', function($value) {
                return new \Twig_Markup(highlight_file($value), 'utf8');
            }),
        ];
    }
}