<?php
namespace Allofmex\TranslatingAutoLoader;

use Symfony\Component\Yaml\Yaml;

class Dictionary {

    private $strings = array();
    private $translationsDir;
    private $cacheDir;

    private $cacheChecked = false;

    function __construct(string $translationsDir, string $cacheDir) {
        $this->translationsDir = $translationsDir;
        $this->cacheDir = $cacheDir;
    }

    /**
     * This must be called before
     * @param string $locale
     * @return int mtime of cached translations file
     */
    public function checkUpToDate(string $locale) : int {
        $this->cacheChecked = true;
        $langFile = $this->getLangFile($locale);
        $langFilePhp = $this->getLangFilePhp($locale);
        $mTimeCache = file_exists($langFilePhp) ? filemtime($langFilePhp) : -1;
        // check if language file was updated
        if (filemtime($langFile) > $mTimeCache) {
            $this->parseLangFile($langFile, $langFilePhp);
            return filemtime($langFilePhp);
        } else {
            return $mTimeCache;
        }
    }

    public function getStringsForLocale($locale) : array {
        if (!$this->cacheChecked) {
            $this->checkUpToDate($locale);
        }
        if (!key_exists($locale, $this->strings)) {
            $this->strings[$locale] = include $this->getLangFilePhp($locale);
        }
        return $this->strings[$locale];
    }

    public function getLangFile($locale) {
        return $this->translationsDir.'/'.$locale.'.yml';
    }

    private function getLangFilePhp(string $locale) : string {
        return $this->cacheDir.'/lang-file_'.$locale.'.php';
    }

    private function parseLangFile($langFile, $langFilePhp) {
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