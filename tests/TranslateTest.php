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
        $this->assertFileContent('A car is called Auto in German.', $tgtFile);

    }

    public function testCache_ifTranslationsFileChanged_refreshed() : void {
        $srcFile = TESTING_WORK_DIR.'/source.php';
        file_put_contents($srcFile, 'abc {t}replace-me{/t} def');
        $translFile = TRANSLATIONS_ROOT.'/de.yml';

        // trigger loading of original version
        DictionaryTest::prepareLangYmlFile($translFile, ['replace-me' => 'old']);
        $cacheFile = Translate::translateFile($srcFile, 'de');
        $this->assertFileContent('abc old def', $cacheFile);

        // update translations file, must invalidate cache and use new version in translateFile
        sleep(1); // to allow file mtime to change
        DictionaryTest::prepareLangYmlFile($translFile, ['replace-me' => 'new']);
        $cacheFile = Translate::translateFile($srcFile, 'de');
        $this->assertFileContent('abc new def', $cacheFile);
    }

    public function testCache_ifSrcFileChanged_refreshed() : void {
        $translFile = TRANSLATIONS_ROOT.'/de.yml';
        DictionaryTest::prepareLangYmlFile($translFile, ['replace-me' => 'text']);

        $srcFile = TESTING_WORK_DIR.'/source.php';
        file_put_contents($srcFile, 'old {t}replace-me{/t} old');

        // trigger loading of original version
        $cacheFile = Translate::translateFile($srcFile, 'de');
        $this->assertFileContent('old text old', $cacheFile);

        // update source file, must invalidate cache and return new version
        sleep(1); // to allow file mtime to change
        file_put_contents($srcFile, 'new {t}replace-me{/t} new');
        $cacheFile = Translate::translateFile($srcFile, 'de');
        $this->assertFileContent('new text new', $cacheFile);
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

    private function assertFileContent(string $expTxt, string $file) : void {
        $this->assertEquals($expTxt, file_get_contents($file), 'File content missmatch');
    }
}
?>