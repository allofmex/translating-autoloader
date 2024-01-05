<?php
namespace Allofmex\TranslatingAutoLoader;

class Translator {

    private $tokenSet = null;

    function __construct(TokenSet $tokenSet) {
        $this->tokenSet = $tokenSet;
    }

    /**
     *
     * @param string $rawText original/untranslated text
     * @param string $langFilePhp
     * @return string translated text
     */
    public function translate($rawText, $strings) {
        $replaceCb = function($match) use (&$strings) {
            $orgText = $match[1];
            return self::findTranslateAndRestore($orgText, $strings);
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
    public function findTranslateAndRestore(string $orgText, array $strings) : string {
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
        $translatedText = isset($strings[$key]) ? $strings[$key] : $orgText;

        if ($hasRestoreSection) {
            // restore not-to-translate section from original texts section
            return $this->restore($orgText, $translatedText);
        } else {
            return $translatedText;
        }
    }

    private function restore($originalText, $translatedText) {
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
        return preg_replace_callback($this->tokenSet->keepRegStr(), $ignore, $translatedText, -1, $count);
    }

//     public static function getTokenSet() : TokenSet {
//         if (self::$tokenSet == null) {
//             self::$tokenSet = TokenSet::default();
//         }
//         return self::$tokenSet;
//     }


}