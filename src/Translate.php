<?php

namespace Allofmex\TranslatingAutoLoader;

use Symfony\Component\Yaml\Yaml;

/**
 * Translates files and stores language specific versions to cache dir.
 *
 */
class Translate {

    static $strings = null;
    const MAX_KEY_LENGTH = 40;

    static $cacheDir = null;

    public static function translateFile($fileToTranslate, $locale) {
        if (self::$cacheDir === null) {
            self::$cacheDir = $_SERVER['DOCUMENT_ROOT'].'/../var/cache';
        }
        if (!file_exists(self::$cacheDir)) {
            mkdir(self::$cacheDir, 700, true);
        }
        $cacheFile = self::$cacheDir.'/'.$locale.'_'.basename($fileToTranslate);
        $langFile = self::getLangFile($locale);
        $langFilePhp = self::$cacheDir.'/lang-file_'.$locale.'.php';
        $mTimeFile = filemtime($fileToTranslate);
        $forceUpdate = false;

        // check if language file was updated
        if (!file_exists($langFilePhp) || filemtime($langFile) > $mTimeFile) {
            self::parseLangFile($langFile, $langFilePhp);
            $forceUpdate = true;
        }

        // check if cached translation file needs to be updated
        if (!file_exists($cacheFile) || $mTimeFile > filemtime($cacheFile) || $forceUpdate) {
            // convert .ini file to .php file
            if (self::$strings === null) {
                self::$strings = include $langFilePhp;
            }
            file_put_contents($cacheFile, self::translate(file_get_contents($fileToTranslate), self::$strings), LOCK_EX);
        }
        return $cacheFile;
    }

    /**
     * Warning! This is only partially cached and should not be used for production.
     * You may call this in testing.
     * 
     * @param string $text plain string to translate (without {t})
     * @param string $locale
     * @return string
     */
    public static function translateString(string $text, string $locale) : string {
        if (self::$strings === null) {
            $langFilePhp = self::$cacheDir.'/lang-file_'.$locale.'.php';
            self::$strings = include $langFilePhp;
        }
        return self::findTranslateAndRestore($text, self::$strings);
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
        return preg_replace_callback('/{t}([\s\S]+?){\/t}/', $replaceCb, $rawText);
    }

    private static function findTranslateAndRestore(string $orgText, $strings) {
        $key = $orgText;
            if (strlen($key) > self::MAX_KEY_LENGTH) {
                $key = substr($key, 0, self::MAX_KEY_LENGTH);
            }
            // search for not-to-translate section {n}keep{/n}
            $ignoreStart = strpos($orgText, '{n}');
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
        $pattern = '/{n}([\s\S]+?){\/n}/m';
        // find sections in original text to keep in translation
        $originalMatches = array();
        preg_match_all($pattern, $originalText, $originalMatches);
        $count = -1;
        $ignore = function($restoreMatch) use (&$translatedText, &$originalMatches, &$count) {
            // replace translated text with sections from original text
            $count++;
            return $originalMatches[1][$count];
        };
        return preg_replace_callback('/{n}([\s\S]+?){\/n}/', $ignore, $translatedText, -1, $count);
    }
    
    private static function getLangFile($locale) {
        // return _SERVER['DOCUMENT_ROOT'].'/../translations/'.$locale.'.ini';
        return $_SERVER['DOCUMENT_ROOT'].'/../translations/'.$locale.'.yml';
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
}

