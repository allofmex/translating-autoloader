<?php
namespace Allofmex\TranslatingAutoLoader;

use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase {
    public function testString_firstToLast_working() : void {
        $translator = new Translator(TokenSet::default());
        $strings = ['Text to translate' => 'Translated result'];
        $this->assertEquals('Translated result', $translator->translate('{t}Text to translate{/t}', $strings));
    }

    public function testString_middle_working() : void {
        $translator = new Translator(TokenSet::default());
        $strings = ['Text to translate' => 'Translated result'];
        $this->assertEquals('BEFORE Translated result AFTER', $translator->translate('BEFORE {t}Text to translate{/t} AFTER', $strings));
    }

    /**
     * Must translate text but keep original text between {n}
     */
    public function testString_withIgnoreSection_working() : void {
        $translator = new Translator(TokenSet::default());
        $strings = ['Text that has' => 'Translated {n}placeholder{/n} result'];
        $this->assertEquals('Translated with link result', $translator->translate('{t}Text that has {n}with link{/n} (this part does not matter) within{/t}', $strings));
    }

    public function testTranslateText_validText_working() : void {
        $translator = new Translator(TokenSet::default());
        $strings = ['car' => '{n}color of car{/n} auto'];
        $this->assertEquals('My red auto needs a wash!!!', $translator->translate('My {t}car{n}red{/n}{/t} needs a wash!!!', $strings));
    }

    public function testMultiTag_ownTags_replacedOnly() : void {
        $de = ['car local name' => 'Auto {n}price{/n} Euro'];
        $pl = ['car local name' => 'samochód {N}price{/N} Sloty'];
        $deTranslator = new Translator(TokenSet::default());
        $plTranslator = new Translator(TokenSet::custom('T', 'N', '{', '}'));

        // text with {t} section that must be replaced by deTranslator only and {T} section for plTranslator only
        $text = 'Pricelist: in Germany {t}car local name {n}1000{/n} in currency{/t}, in Poland {T}car local name {N}5000{/N} in currency{/T}!';

        $this->assertEquals('Pricelist: in Germany Auto 1000 Euro, in Poland {T}car local name {N}5000{/N} in currency{/T}!',
                $deTranslator->translate($text, $de),
                'Must have translated lowercase t/n tags only');

        $this->assertEquals('Pricelist: in Germany {t}car local name {n}1000{/n} in currency{/t}, in Poland samochód 5000 Sloty!',
                $plTranslator->translate($text, $pl),
                'Must have translated uppercase T/N tags only');
    }
}