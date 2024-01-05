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
        $this->assertEquals('My red auto needs a bbb!!!', $translator->translate('My {t}car{n}red{/n}{/t} needs a bbb!!!', $strings));
    }
}