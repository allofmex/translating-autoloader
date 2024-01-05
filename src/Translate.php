<?php

namespace Allofmex\TranslatingAutoLoader;

/**
 * Translates files and stores language specific versions to cache dir.
 *
 */
class Translate {

    static $dict = null;

    const MAX_KEY_LENGTH = 40;

    static $cacheDir = null;


    private static $defTranslator = null;

    public static function translateFile($fileToTranslate, $locale) {
        $cacheDir = self::getCacheDir();
        $cacheFile = $cacheDir.'/'.$locale.'_'.basename($fileToTranslate);

        $mTimeFile = self::getDict()->checkUpToDate($locale);

        // check if cached translation file needs to be updated
        if (!file_exists($cacheFile) || $mTimeFile > filemtime($cacheFile)) {
            file_put_contents($cacheFile, self::getTranslator()->translate(file_get_contents($fileToTranslate), self::getStringsForLocale($locale)), LOCK_EX);
        }
        return $cacheFile;
    }

    private static function getStringsForLocale($locale) : array {
        return self::getDict()->getStringsForLocale($locale);
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
        return self::getTranslator()->translateString($text, self::getStringsForLocale($locale));
    }

    /**
     * Only use this, if you need to translate dynamically generated text. For static content
     * prefere translateFile()
     * @param string $text text to translate (may contain multiple translateable sections)
     * @param string $locale
     * @return string translated text
     */
    public static function translateText(string $text, string $locale) : string {
        return self::getTranslator()->translate($text, self::getStringsForLocale($locale));
    }

    static function getTranslator(TokenSet $tokenSet = null) : Translator {
        if ($tokenSet === null) {
            if (self::$defTranslator === null) {
                self::$defTranslator = new Translator(TokenSet::default());
            }
            return self::$defTranslator;
        } else {
            return new Translator($tokenSet);
        }
    }

    private static function getCacheDir() {
        if (self::$cacheDir === null) {
            if (defined('TRANSLATIONS_CACHE')) {
                self::$cacheDir = TRANSLATIONS_CACHE;
            } else {
                self::$cacheDir = self::getProjectRootDir().'/var/cache';
                if (!file_exists(self::$cacheDir)) {
                    if (!mkdir(self::$cacheDir, 0700, true)) {
                        throw new \Exception('Could not create dir '.self::$cacheDir.', please make sure the file permission are correct');
                    }
                }
            }
        }
        return self::$cacheDir;
    }

    public static function getTranslationsDir() : string {
        if (defined('TRANSLATIONS_ROOT')) {
            return TRANSLATIONS_ROOT;
        } else {
            return self::getProjectRootDir().'/translations';
        }
    }

    private static function getProjectRootDir() {
        if (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') {
            $docRoot = realpath($_SERVER['DOCUMENT_ROOT'].'/..');
        } else {
            // fallback for testing,...
            $docRoot = getcwd();
        }
        return $docRoot;
    }

    private static function getDict() : Dictionary {
        if (self::$dict === null) {
            self::$dict = new Dictionary(self::getTranslationsDir(), self::getCacheDir());
        }
        return self::$dict;
    }
}

