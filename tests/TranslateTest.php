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
    
    public function testString_withIgnoreSection_working() {
        $strings = array (
                'Text' => 'Translated {n}placeholder{/n} result',
        );
        assertEquals('Translated with link result', Translate::translate('{t}Text {n}with link{/n} to translate{/t}', $strings));
    }
}
?>