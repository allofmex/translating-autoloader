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


}