<?php
namespace Allofmex\TranslatingAutoLoader;

class Translator {

    /**
     *
     * @var TokenSet
     */
    private $tokenSet;

    /**
     *
     * @var Dictionary
     */
    private $dict;

    function __construct(TokenSet $tokenSet, Dictionary $dict) {
        $this->tokenSet = $tokenSet;
        $this->dict = $dict;
    }

    /**
     *
     * @param string $rawText original/untranslated text
     * @param string $langFilePhp
     * @return string translated text
     */
    public function translate(string $rawText, string $locale) : string {
        $replaceCb = function($match) use (&$locale) {
            $orgText = $match[1];
            return $this->translateString($orgText, $locale);
        };
        // replace all {t}translate.me{/t} in replaceCb()
        return preg_replace_callback($this->tokenSet->translateRegStr(), $replaceCb, $rawText);
    }

    /**
     *
     * @param string $orgText text inside {t}
     * @param array $strings
     * @return string
     */
    public function translateString(string $orgText, string $locale) : string {
        $key = $orgText;
        if (strlen($key) > Translate::MAX_KEY_LENGTH) {
            $key = substr($key, 0, Translate::MAX_KEY_LENGTH);
        }
        // search for not-to-translate section {n}keep{/n}
        $ignoreStart = strpos($orgText, $this->tokenSet->keepStartStr());
        $hasRestoreSection = $ignoreStart !== false;
        // key to match entry in translation files is first part until {n} (if existing)
        // and max length is MAX_KEY_LENGTH
        $key = trim($hasRestoreSection ? substr($key, 0, $ignoreStart) : $key);
        $strings = $this->dict->getStringsForLocale($locale);
        $translatedText = isset($strings[$key]) ? $strings[$key] : $orgText;

        if ($hasRestoreSection) {
            // restore not-to-translate section from original texts section
            return $this->restore($orgText, $translatedText);
        } else {
            return $translatedText;
        }
    }

    private function restore(string $originalText, string $translatedText) : string {
        $pattern = $this->tokenSet->keepRegStr().'m';
        // find sections in original text to keep in translation
        $originalMatches = array();
        preg_match_all($pattern, $originalText, $originalMatches);
        $count = -1;
        $ignore = function($restoreMatch) use (&$translatedText, &$originalMatches, &$count) {
            // replace translated text with sections from original text
            $count++;
            return $originalMatches[1][$count];
        };
        return preg_replace_callback($this->tokenSet->keepDictRegStr(), $ignore, $translatedText, -1, $count);
    }
}