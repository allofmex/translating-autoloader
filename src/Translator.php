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
        if (isset($strings[$key])) { // $translatedText has default tags (if keep present)
            $translatedText = $strings[$key];
            $useDictRestore = true;
        } else { // no entry in dictionary, we use original text
            $translatedText = $orgText;
            $useDictRestore = false;
        }

        if ($hasRestoreSection) {
            // restore not-to-translate section from original texts section
            return $this->restore($orgText, $translatedText, $useDictRestore);
        } else {
            return $translatedText;
        }
    }

    /**
     *
     * @param string $originalText witout {t} tags
     * @param string $translatedText target text but still containing keep tags
     * @param bool $useDictRestore If true, $translatedText is from dictionary (with default keep tags).
     *              If false, $translatedText is stripped original text (with possible custom tags), since there was no
     *              entry in dictionary
     * @return string
     */
    private function restore(string $originalText, string $translatedText, bool $useDictRestore) : string {
        // find sections in original text to keep in translation
        $originalMatches = array();
        preg_match_all($this->tokenSet->keepRegStr().'m', $originalText, $originalMatches);
        $count = -1;
        $ignore = function($restoreMatch) use (&$translatedText, &$originalMatches, &$count) {
            // replace translated text with sections from original text
            $count++;
            return $originalMatches[1][$count];
        };
        $restoreRegex = $useDictRestore ? $this->tokenSet->keepDictRegStr() : $this->tokenSet->keepRegStr();
        return preg_replace_callback($restoreRegex, $ignore, $translatedText, -1, $count);
    }
}