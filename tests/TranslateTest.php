<?php
namespace Allofmex\TranslatingAutoLoader;

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

class TranslateTest extends TestCase {
    public function testString_firstToLast_working() {
        $strings = array (
                'Text to translate' => 'Translated result',
        );
        assertEquals('Translated result', Translate::translate('{t}Text to translate{/t}', $strings));
    }

    public function testString_middle_working() {
        $strings = array (
                'Text to translate' => 'Translated result',
        );
        assertEquals('BEFORE Translated result AFTER', Translate::translate('BEFORE {t}Text to translate{/t} AFTER', $strings));
    }

    /**
     * Must translate text but keep original text between {n}
     */
    public function testString_withIgnoreSection_working() {
        $strings = array (
                'Text that has' => 'Translated {n}placeholder{/n} result',
        );
        assertEquals('Translated with link result', Translate::translate('{t}Text that has {n}with link{/n} (this part does not matter) within{/t}', $strings));
    }
}
?>