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