<?php

namespace Allofmex\TranslatingAutoLoader;

use Allofmex\TranslatingAutoLoader\TranslatingAutoLoader;

/**
 * Translates files and stores language specific versions to cache dir.
 *
 */
class Translate {

    const MAX_KEY_LENGTH = 40;

    private static $dict = null;
    private static $defTranslator = null;

    private static $cacheDir = null;

    public static function translateFile($fileToTranslate, $locale) {
        $cacheDir = self::getCacheDir();
        $cacheFile = $cacheDir.'/'.$locale.'_'.basename($fileToTranslate);

        $mTimeTranslationFile = self::getDict()->checkUpToDate($locale);

        // check if cached translation file needs to be updated
        if (!file_exists($cacheFile) || $mTimeTranslationFile > filemtime($cacheFile) || $mTimeTranslationFile < filemtime($fileToTranslate)) {
            // update if translations-file or fileToTranslate is newer than existing cacheFile
            file_put_contents($cacheFile, self::getTranslator()->translate(file_get_contents($fileToTranslate), $locale), LOCK_EX);
        }
        return $cacheFile;
    }

    /**
     * To translate a single phrase (that would be within a single {t}{/t} block.
     *
     * Warning! This is only partially cached and should not be used for production.
     * You may call this in testing.
     *
     * @param string $text plain string to translate (without {t})
     * @param string $locale
     * @return string
     */
    public static function translateString(string $text, string $locale) : string {
        return self::getTranslator()->translateString($text, $locale);
    }

    /**
     * Only use this, if you need to translate dynamically generated text. For static content
     * prefere translateFile()
     * @param string $text text to translate (may contain multiple translateable sections)
     * @param string $locale
     * @return string translated text
     */
    public static function translateText(string $text, string $locale) : string {
        return self::getTranslator()->translate($text, $locale);
    }

    /**
     * You may use this to aquire a custom translator with a non-default TokenSet.
     *
     * @param TokenSet $tokenSet
     * @return Translator
     */
    public static function getTranslator(?TokenSet $tokenSet = null) : Translator {
        if ($tokenSet === null) {
            if (self::$defTranslator === null) {
                self::$defTranslator = new Translator(TokenSet::default(), self::getDict());
            }
            return self::$defTranslator;
        } else {
            return new Translator($tokenSet, self::getDict());
        }
    }

    private static function getCacheDir() {
        if (self::$cacheDir === null) {
            if (defined('TRANSLATIONS_CACHE')) {
                self::$cacheDir = TRANSLATIONS_CACHE;
            } else {
                self::$cacheDir = TranslatingAutoLoader::getProjectRootDir().'/var/cache';
                if (!file_exists(self::$cacheDir)) {
                    if (!mkdir(self::$cacheDir, 0700, true)) {
                        throw new \Exception('Could not create dir '.self::$cacheDir.', please make sure the file permission are correct');
                    }
                }
            }
        }
        return self::$cacheDir;
    }

    private static function getDict() : Dictionary {
        if (self::$dict === null) {
            self::$dict = new Dictionary(TranslatingAutoLoader::getTranslationsDir(), self::getCacheDir());
        }
        return self::$dict;
    }
}

