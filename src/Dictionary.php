<?php
namespace Allofmex\TranslatingAutoLoader;

use Symfony\Component\Yaml\Yaml;

class Dictionary {

    private $strings = array();
    private $translationsDir;
    private $cacheDir;

    private $lastChecked = -1;

    function __construct(string $translationsDir, string $cacheDir) {
        $this->translationsDir = $translationsDir;
        $this->cacheDir = $cacheDir;
    }

    /**
     * This will update internal dictionary cache (only if translation-strings changed)
     * You may use returned time to check/update other cache entries too. Update those caches
     * if their last-modified time is older than returned timestamp
     *
     * @param string $locale
     * @return int mtime (Unix timestamp) of translation files
     */
    public function checkUpToDate(string $locale) : int {
        $this->lastChecked = time();
        $langFile = $this->getLangFile($locale);
        $langFilePhp = $this->getLangFilePhp($locale);
        $mTimeCache = file_exists($langFilePhp) ? filemtime($langFilePhp) : -1;
        // check if language file was updated
        if (filemtime($langFile) > $mTimeCache) {
            unset($this->strings[$locale]);
            $this->parseLangFile($langFile, $langFilePhp);
            return filemtime($langFilePhp);
        } else {
            return $mTimeCache;
        }
    }

    public function getStringsForLocale(string $locale) : array {
        if (time() > $this->lastChecked + 1) {
            // do not check on every call, 1-2 seconds should be enough to check only once for most backend requests
            // warning, does not consider locale!
            $this->checkUpToDate($locale);
        }

        if (!key_exists($locale, $this->strings)) {
            $langFile = $this->getLangFilePhp($locale);
            if (!file_exists($langFile)) {
                $this->checkUpToDate($locale);
            }
            $this->strings[$locale] = include $langFile;
        }
        return $this->strings[$locale];
    }

    private function getLangFile(string $locale) : string {
        return $this->translationsDir.'/'.$locale.'.yml';
    }

    private function getLangFilePhp(string $locale) : string {
        return $this->cacheDir.'/lang-file_'.$locale.'.php';
    }

    private function parseLangFile($langFile, $langFilePhp) : void {
        if (file_exists($langFile)) {
            $rawData = Yaml::parseFile($langFile);
            if ($rawData != null) {
                $keys = array_keys($rawData);
                $values = array_values($rawData);
                foreach ($keys as $keyIndex => $key) {
                    if (strlen($key) > Translate::MAX_KEY_LENGTH) {
                        $keys[$keyIndex] = trim(substr($key, 0, Translate::MAX_KEY_LENGTH));
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
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0700, true);
        }
        file_put_contents($langFilePhp, '<?php return '.var_export($rawData, true).';', LOCK_EX);
    }
}