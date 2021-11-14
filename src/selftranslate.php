<?php
namespace Allofmex\TranslatingAutoLoader;

const FUN_NAME_SELFTRANSLAGE = 'translateFromHere';

require 'autoload.php';

/**
 * May be used to translate the current script.
 *
 * Call as `translateFromHere(__FILE__, 'en');`
 *
 * <p>Warning: this is not the preferred way of using this package. Use this only if you cannot move your entry page files
 * code to separate files (and to use as `require Translate::translateFile(../templates/my-page.php', 'en');`)</p>
 * @param string $file calling file name
 * @param string $locale
 * @throws \Exception
 */
function translateFromHere($file, $locale = null) {
    TranslatingAutoLoader::$instance->selfTranslate($file, $locale);
}
