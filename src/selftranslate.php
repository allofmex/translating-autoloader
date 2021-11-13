<?php
namespace Allofmex\TranslatingAutoLoader;

const FUN_NAME_SELFTRANSLAGE = 'translateFromHere';

require 'autoload.php';

function translateFromHere($file, $locale = null) {
    TranslatingAutoLoader::$instance->selfTranslate($file, $locale);
}
