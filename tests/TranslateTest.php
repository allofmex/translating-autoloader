<?php
namespace Allofmex\TranslatingAutoLoader;

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

class TranslateTest extends TestCase {

    /**
     *
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    public function setUp() : void {
        register_shutdown_function(function() { $this->cleanup(); });
        $this->cleanup();
        if (!file_exists(TRANSLATIONS_ROOT)) {
            mkdir(TRANSLATIONS_ROOT, 0700, true);
        }
    }

    public function testString_firstToLast_working() : void {
        $strings = array (
                'Text to translate' => 'Translated result',
        );
        assertEquals('Translated result', Translate::translate('{t}Text to translate{/t}', $strings));
    }

    public function testString_middle_working() : void {
        $strings = array (
                'Text to translate' => 'Translated result',
        );
        assertEquals('BEFORE Translated result AFTER', Translate::translate('BEFORE {t}Text to translate{/t} AFTER', $strings));
    }

    /**
     * Must translate text but keep original text between {n}
     */
    public function testString_withIgnoreSection_working() : void {
        $strings = array (
                'Text that has' => 'Translated {n}placeholder{/n} result',
        );
        assertEquals('Translated with link result', Translate::translate('{t}Text that has {n}with link{/n} (this part does not matter) within{/t}', $strings));
    }

    public function testTranslateText_validText_working() : void {
        $strings = array (
            'aaa' => '{n}color{/n} car',
        );
        $text = 'My {t}aaa{n}red{/n}{/t} needs a bbb!!!';
        assertEquals('My red car needs a bbb!!!', Translate::translate($text, $strings));
    }

    public function testTranslateFile_translate_working() : void {
        DictionaryTest::prepareLangYmlFile(TRANSLATIONS_ROOT.'/de.yml', ['car' => 'Auto']);
        $srcFile = TESTING_WORK_DIR.'/source.php';
        file_put_contents($srcFile, 'A car is called {t}car{/t} in German.');

        $tgtFile = Translate::translateFile($srcFile, 'de');

        $this->assertEquals(TRANSLATIONS_CACHE.'/de_source.php', $tgtFile);
        $this->assertFileExists($tgtFile);
        $this->assertEquals('A car is called Auto in German.', file_get_contents($tgtFile));

    }

    private function cleanup() {
        if(file_exists(TESTING_WORK_DIR)) {
            $dirIt = new \RecursiveDirectoryIterator(TESTING_WORK_DIR, \FilesystemIterator::SKIP_DOTS);
            $it = new \RecursiveIteratorIterator($dirIt, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $file) {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
        }
    }
}
?>