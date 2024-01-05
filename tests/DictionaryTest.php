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
        $this->testDir = sys_get_temp_dir().'/translating-autoloader-tmp';
        $this->cacheDir = $this->testDir.'/cache';
        $this->translationsDir = $this->testDir.'/translations';

        register_shutdown_function(function() { $this->cleanup(); });

        $this->cleanup();
        if (!file_exists($this->translationsDir)) {
            mkdir($this->translationsDir, 0700, true);
        }
    }


    public function testCheckUpToDate_outdatedCache_mustUpdate() : void {
        $this->assertTrue(true);
        $dict = new Dictionary($this->translationsDir, $this->cacheDir);

        $translFile = $this->translationsDir.'/de.yml';
        $cacheFile = $this->cacheDir.'/lang-file_de.php';

        self::prepareLangPhpFile($cacheFile, ['old' => 'alt']);
        self::prepareLangYmlFile($translFile, ['new' => 'neu']);

        $now = time();
        touch($cacheFile, $now - 200); // cache older than translations file
        touch($translFile, $now);

        // must update cacheFile
        $mTime = $dict->checkUpToDate('de');

        $this->assertGreaterThanOrEqual($now, $mTime);
        $this->assertGreaterThanOrEqual($now, filemtime($cacheFile));
        $this->assertLangPhpFile($this->cacheDir.'/lang-file_de.php', ['new' => 'neu']);
    }

    public function testCheckUpToDate_upToDateCache_mustNotUpdate() : void {
        $this->assertTrue(true);
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

    static function mockDict(array $strings, TestCase $testCase) : Dictionary {
        $mock = $testCase->createMock(Dictionary::class);
        $mock->method('getStringsForLocale')->willReturnCallback(function ($locale) use (&$strings) {
            return array_key_exists($locale, $strings) ? $strings[$locale] : array();
        });
        return $mock;
    }
}