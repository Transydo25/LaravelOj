<?php

namespace App\Helpers;

use Stichoza\GoogleTranslate\GoogleTranslate;

class Translate
{
    public static function translate($text, $targetLanguage)
    {
        return GoogleTranslate::setSource('en')->setTarget($targetLanguage)->translate($text);
    }
}
