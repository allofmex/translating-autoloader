<?php
namespace Allofmex\TranslatingAutoLoader;

use PHPUnit\Framework\TestCase;

class DictionaryTest extends TestCase {

    private $testDir;
    private $cacheDir;
    private $translationsDir;

    /**
     *
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    public function setUp() : void {
        $this->testDir = TESTING_WORK_DIR;
        $this->cacheDir = TRANSLATIONS_CACHE;
        $this->translationsDir = TRANSLATIONS_ROOT;

        register_shutdown_function(function() { $this->cleanup(); });

        $this->cleanup();
        if (!file_exists($this->translationsDir)) {
            mkdir($this->translationsDir, 0700, true);
        }
    }


    public function testCheckUpToDate_outdatedCache_mustUpdate() : void {
        $dict = new Dictionary($this->translationsDir, $this->cacheDir);

        $translFile = $this->translationsDir.'/de.yml';
        $cacheFile = $this->cacheDir.'/lang-file_de.php';

        self::prepareLangPhpFile($cacheFile, ['old' => 'alt']);
        self::prepareLangYmlFile($translFile, ['new' => 'neu']);

        $now = time();
        touch($cacheFile, $now - 10); // cache older than translations file
        $mTime = $dict->checkUpToDate('de');

        touch($translFile, $now);

        // must update cacheFile
        $mTime = $dict->checkUpToDate('de');

        $this->assertGreaterThanOrEqual($now, $mTime);
        $this->assertGreaterThanOrEqual($now, filemtime($cacheFile));
        $this->assertLangPhpFile($this->cacheDir.'/lang-file_de.php', ['new' => 'neu']);
    }

    public function testGetStringsForLocale_translationFileChanges_mustRefreshCache() : void {
        $dict = new Dictionary($this->translationsDir, $this->cacheDir);

        $translFile = $this->translationsDir.'/de.yml';
        self::prepareLangYmlFile($translFile, ['old' => 'alt']);

        // trigger loading of old version
        $this->assertEquals(['old' => 'alt'], $dict->getStringsForLocale('de'));
        $this->assertLangPhpFile($this->cacheDir.'/lang-file_de.php', ['old' => 'alt']);

        // simulate time passed (update check is only run every 1-2 seconds
        sleep(2);

        // now place new translations-file version
        self::prepareLangYmlFile($translFile, ['new' => 'neu']);

        // must not use old version from cache but must have updated cache
        $this->assertEquals(['new' => 'neu'], $dict->getStringsForLocale('de'));
        $this->assertLangPhpFile($this->cacheDir.'/lang-file_de.php', ['new' => 'neu']);
    }

    public function testCheckUpToDate_upToDateCache_mustNotUpdate() : void {
        $dict = new Dictionary($this->translationsDir, $this->cacheDir);

        $translFile = $this->translationsDir.'/de.yml';
        $cacheFile = $this->cacheDir.'/lang-file_de.php';

        self::prepareLangYmlFile($translFile, ['some' => 'data']);
        self::prepareLangPhpFile($cacheFile, ['some' => 'data']);

        $now = time();
        touch($cacheFile, $now - 200); // cache same as translations file
        touch($translFile, $now - 200);

        // must NOT update cacheFile
        $mTime = $dict->checkUpToDate('de');

        $this->assertEquals($now - 200, $mTime);
        $this->assertEquals($now - 200, filemtime($cacheFile));
    }


    static function prepareLangYmlFile($langFileYml, array $content) : void {
        $txt = '';
        foreach ($content as $key => $value) {
            $txt .= "{$key} : {$value}\n";
        }
        if (!is_dir(dirName($langFileYml))) mkdir(dirName($langFileYml), 0700, true);
        file_put_contents($langFileYml, $txt);
    }

    static function prepareLangPhpFile($langFilePhp, array $content) : void {
        if (!is_dir(dirName($langFilePhp))) mkdir(dirName($langFilePhp), 0700, true);
        file_put_contents($langFilePhp, '<?php return '.var_export($content, true).';', LOCK_EX);
    }

    private function assertLangPhpFile($langFilePhp, array $expContent) : void {
        $content = include $langFilePhp;
        $this->assertEquals($expContent, $content);
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

    static function mockDict(array $strings, TestCase $testCase) : Dictionary {
        $mock = $testCase->createMock(Dictionary::class);
        $mock->method('getStringsForLocale')->willReturnCallback(function ($locale) use (&$strings) {
            return array_key_exists($locale, $strings) ? $strings[$locale] : array();
        });
        return $mock;
    }
}