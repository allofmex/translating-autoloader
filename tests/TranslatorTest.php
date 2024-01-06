<?php
namespace Allofmex\TranslatingAutoLoader;

use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase {
    public function testString_firstToLast_working() : void {
        $strings = ['Text to translate' => 'Translated result'];
        $translator = new Translator(TokenSet::default(), DictionaryTest::mockDict(['xy' => $strings], $this));
        $this->assertEquals('Translated result', $translator->translate('{t}Text to translate{/t}', 'xy'));
    }

    public function testString_middle_working() : void {
        $strings = ['Text to translate' => 'Translated result'];
        $translator = new Translator(TokenSet::default(), DictionaryTest::mockDict(['xy' => $strings], $this));
        $this->assertEquals('BEFORE Translated result AFTER', $translator->translate('BEFORE {t}Text to translate{/t} AFTER', 'xy'));
    }

    /**
     * Must translate text but keep original text between {n}
     */
    public function testString_withIgnoreSection_working() : void {
        $strings = ['Text that has' => 'Translated {n}placeholder{/n} result'];
        $translator = new Translator(TokenSet::default(), DictionaryTest::mockDict(['xy' => $strings], $this));
        $this->assertEquals('Translated with link result', $translator->translate('{t}Text that has {n}with link{/n} (this part does not matter) within{/t}', 'xy'));
    }

    public function testTranslateText_validText_working() : void {
        $strings = ['car' => '{n}color of car{/n} auto'];
        $translator = new Translator(TokenSet::default(), DictionaryTest::mockDict(['xy' => $strings], $this));
        $this->assertEquals('My red auto needs a wash!!!', $translator->translate('My {t}car{n}red{/n}{/t} needs a wash!!!', 'xy'));
    }

    public function testTranslate_dictWithKeepButTextNot_implement() : void {
        $this->markTestIncomplete('ToDo: implement');
//         $strings = ['car' => 'Auto {n}speed{/n} maximum, allowed {n}other speed{/n} on highways']; // 2 keep sections
//         $translator = new Translator(TokenSet::default(), DictionaryTest::mockDict(['en' => $strings], $this));
//         $this->assertEquals(
//                 '???', // To decide: Exception?
//                 $translator->translate('{t}car {n}100 km/h{/n} max{/t}', 'en')); // missing second keep section
    }

    public function testTranslate_invalidDictEntry_implement() : void {
        $this->markTestIncomplete('ToDo: implement');
//         $strings = ['car' => 'auto']; // missing keep section
//         $translator = new Translator(TokenSet::default(), DictionaryTest::mockDict(['de' => $strings], $this));
//         $this->assertEquals(
//                 '???', // To decide: Exception? Keep untranslated? Or append keep after?
//                 $translator->translate('{t}car {n}value{/n} maximum{/t}', 'de'));
    }

    public function testMultiTag_ownTags_replacedOnly() : void {
        // dictionary always uses default tags!
        $de = ['car local name' => 'Auto {n}price{/n} Euro'];
        $pl = ['car local name' => 'samochód {n}price{/n} Sloty'];
        $dict = DictionaryTest::mockDict(['de' => $de, 'pl' => $pl,], $this);

        $deTranslator = new Translator(TokenSet::default(), $dict);
        $plTranslator = new Translator(TokenSet::custom('T', 'N', '{', '}'), $dict);

        // text with {t} section that must be replaced by deTranslator only and {T} section for plTranslator only
        $text = 'Pricelist: in Germany {t}car local name {n}1000{/n} in currency{/t}, in Poland {T}car local name {N}5000{/N} in currency{/T}!';

        $this->assertEquals('Pricelist: in Germany Auto 1000 Euro, in Poland {T}car local name {N}5000{/N} in currency{/T}!',
                $deTranslator->translate($text, 'de'),
                'Must have translated lowercase t/n tags only');

        $this->assertEquals('Pricelist: in Germany {t}car local name {n}1000{/n} in currency{/t}, in Poland samochód 5000 Sloty!',
                $plTranslator->translate($text, 'pl'),
                'Must have translated uppercase T/N tags only');
    }


    public function testMultiTag_noDict_mustHandleKeepTags() : void {
        // no entry for string present in dictionary
        $dict = DictionaryTest::mockDict(['de' => []], $this);

        $customTranslator = new Translator(TokenSet::custom('T', 'N', '{', '}'), $dict);

        // text without matching entries in dictionary, Translator must simply strip all tags
        $text = '{T}car {N}1000{/N} dollar{/T}!';

        $this->assertEquals('car 1000 dollar!',
                $customTranslator->translate($text, 'de'),
                'Failed to strip tags');
    }
}