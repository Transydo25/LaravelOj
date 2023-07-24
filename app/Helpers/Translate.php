<?php

use Stichoza\GoogleTranslate\GoogleTranslate;

function Translate($text, $targetLanguage)
{
    $tr = new GoogleTranslate;

    return $tr->setSource('en')->setTarget($targetLanguage)->translate($text);
}
