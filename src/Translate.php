<?php

namespace Allofmex\TranslatingAutoLoader;

use Symfony\Component\Yaml\Yaml;

/**
 * Translates files and stores language specific versions to cache dir.
 *
 */
class Translate {

    static $strings = array();
    const MAX_KEY_LENGTH = 40;

    static $cacheDir = null;

    private static $tokenSet = null;

    public static function translateFile($fileToTranslate, $locale) {
        $cacheDir = self::getCacheDir();
        $cacheFile = $cacheDir.'/'.$locale.'_'.basename($fileToTranslate);
        $langFile = self::getLangFile($locale);
        $langFilePhp = $cacheDir.'/lang-file_'.$locale.'.php';
        $mTimeFile = filemtime($fileToTranslate);
        $forceUpdate = false;

        // check if language file was updated
        if (!file_exists($langFilePhp) || filemtime($langFile) > $mTimeFile) {
            self::parseLangFile($langFile, $langFilePhp);
            $forceUpdate = true;
        }

        // check if cached translation file needs to be updated
        if (!file_exists($cacheFile) || $mTimeFile > filemtime($cacheFile) || $forceUpdate) {
            file_put_contents($cacheFile, self::translate(file_get_contents($fileToTranslate), self::getStringsForLocale($locale)), LOCK_EX);
        }
        return $cacheFile;
    }

    private static function getStringsForLocale($locale) : array {
        $cacheDir = self::getCacheDir();
        $langFilePhp = $cacheDir.'/lang-file_'.$locale.'.php';
        if (!key_exists($locale, self::$strings)) {
            self::$strings[$locale] = include $langFilePhp;
        }
        return self::$strings[$locale];
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
        return self::findTranslateAndRestore($text, self::getStringsForLocale($locale));
    }

    /**
     * Only use this, if you need to translate dynamically generated text. For static content
     * prefere translateFile()
     * @param string $text text to translate (may contain multiple translateable sections)
     * @param string $locale
     * @return string translated text
     */
    public static function translateText(string $text, string $locale) : string {
        return self::translate($text, self::getStringsForLocale($locale));
    }

    /**
     *
     * @param string $rawText original/untranslated text
     * @param string $langFilePhp
     * @return string translated text
     */
    static function translate($rawText, $strings) {
        $replaceCb = function($match) use (&$strings) {
            $orgText = $match[1];
            return self::findTranslateAndRestore($orgText, $strings);
        };
        // replace all {t}translate.me{/t} in replaceCb()
        return preg_replace_callback(self::getTokenSet()->translateRegStr(), $replaceCb, $rawText);
    }

    /**
     *
     * @param string $orgText text inside {t}
     * @param array $strings
     * @return string
     */
    static function findTranslateAndRestore(string $orgText, array $strings) : string {
        $key = $orgText;
        if (strlen($key) > self::MAX_KEY_LENGTH) {
            $key = substr($key, 0, self::MAX_KEY_LENGTH);
        }
        // search for not-to-translate section {n}keep{/n}
        $ignoreStart = strpos($orgText, self::getTokenSet()->keepStartStr());
        $hasRestoreSection = $ignoreStart !== false;
        // key to match entry in translation files is first part until {n} (if existing)
        // and max length is MAX_KEY_LENGTH
        $key = trim($hasRestoreSection ? substr($key, 0, $ignoreStart) : $key);
        $translatedText = isset($strings[$key]) ? $strings[$key] : $orgText;

        if ($hasRestoreSection) {
            // restore not-to-translate section from original texts section
            return self::restore($orgText, $translatedText);
        } else {
            return $translatedText;
        }
    }

    private static function restore($originalText, $translatedText) {
        $pattern = self::getTokenSet()->keepRegStr().'m';
        // find sections in original text to keep in translation
        $originalMatches = array();
        preg_match_all($pattern, $originalText, $originalMatches);
        $count = -1;
        $ignore = function($restoreMatch) use (&$translatedText, &$originalMatches, &$count) {
            // replace translated text with sections from original text
            $count++;
            return $originalMatches[1][$count];
        };
        return preg_replace_callback(self::getTokenSet()->keepRegStr(), $ignore, $translatedText, -1, $count);
    }

    private static function getLangFile($locale) {
        return self::getProjectRootDir().'/translations/'.$locale.'.yml';
    }

    private static function parseLangFile($langFile, $langFilePhp) {
        if (file_exists($langFile)) {
            $rawData = Yaml::parseFile($langFile);
            // $rawData = yaml_parse_file($langFile);
            // $rawData = parse_ini_file($langFile, false, INI_SCANNER_RAW);
            if ($rawData != null) {
                $keys = array_keys($rawData);
                $values = array_values($rawData);
                foreach ($keys as $keyIndex => $key) {
                    if (strlen($key) > self::MAX_KEY_LENGTH) {
                        $keys[$keyIndex] = trim(substr($key, 0, self::MAX_KEY_LENGTH));
                    }
                }
                foreach ($values as $valueIndex => $value) {
                    $values[$valueIndex] = trim($value);
                }
                $rawData = array_combine($keys, $values);
            } else {
                $rawData = array();
            }
        } else {
            $rawData = array();
        }
        file_put_contents($langFilePhp, '<?php return '.var_export($rawData, true).';', LOCK_EX);
    }

    private static function getCacheDir() {
        if (self::$cacheDir === null) {
            self::$cacheDir = self::getProjectRootDir().'/var/cache';
            if (!file_exists(self::$cacheDir)) {
                if (!mkdir(self::$cacheDir, 0700, true)) {
                    throw new \Exception('Could not create dir '.self::$cacheDir.', please make sure the file permission are correct');
                }
            }
        }
        return self::$cacheDir;
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

    private static function getTokenSet() : TokenSet {
        if (self::$tokenSet == null) {
            self::$tokenSet = TokenSet::default();
        }
        return self::$tokenSet;
    }
}

