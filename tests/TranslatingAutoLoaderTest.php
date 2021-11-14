<?php
namespace Allofmex\TranslatingAutoLoader;

use PHPUnit\Framework\TestCase;
use Allofmex\TranslatingAutoLoader\Test\ToBeTranslatedClass;
use function PHPUnit\Framework\assertEquals;
use Allofmex\TranslatingAutoLoader\Test\NotToBeTranslatedClass;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\assertNotFalse;
use Allofmex\TranslatingAutoLoader\Test\ToBeTranslatedClass2;
use Allofmex\TranslatingAutoLoader\Test\NotToBeTranslatedClass2;

class TranslatingAutoLoaderTest extends TestCase {
    const CONFIG_FILE_PATH = __DIR__.'/../config/translating_autoloader.config.php';
    const LANGUAGE_FILE_PATH = __DIR__.'/../translations/en.yml';

    public static function setUpBeforeClass() : void {
        require __DIR__.'/../src/autoload.php';
    }

    protected function setUp() : void {
        if (!file_exists(dirname(self::CONFIG_FILE_PATH))) {
            mkdir(dirname(self::CONFIG_FILE_PATH), 0700, true);
        }
        assertNotFalse(file_put_contents(self::CONFIG_FILE_PATH, file_get_contents(__DIR__.'/assets/translating_autoloader.config.php'), LOCK_EX));

        if (!file_exists(dirname(self::LANGUAGE_FILE_PATH))) {
            mkdir(dirname(self::LANGUAGE_FILE_PATH), 0700, true);
        }
        assertNotFalse(file_put_contents(self::LANGUAGE_FILE_PATH, file_get_contents(__DIR__.'/assets/en.yml'), LOCK_EX));
        TranslatingAutoLoader::reset();
    }

    protected function tearDown(): void {
        if (file_exists(self::CONFIG_FILE_PATH)) unlink(self::CONFIG_FILE_PATH);
        if (file_exists(dirname(self::CONFIG_FILE_PATH))) rmdir(dirname(self::CONFIG_FILE_PATH));
        if (file_exists(self::LANGUAGE_FILE_PATH)) unlink(self::LANGUAGE_FILE_PATH);
        if (file_exists(dirname(self::LANGUAGE_FILE_PATH))) rmdir(dirname(self::LANGUAGE_FILE_PATH));
        foreach (glob(__DIR__.'/../var/cache/*') as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Test that autoloader
     */
    public function testWhitelist_existingWhiteList_working() {
        assertEquals('# Translated!', (new ToBeTranslatedClass())->getText());
        // Class not in config white-list, must not be translated.
        assertEquals('{t}Zu übersetzen{/t}', (new NotToBeTranslatedClass())->getText());
    }

    /**
     * If no white-list config present, must translate all (own) classes
     */
    public function testWhitelist_noWhitelistPresent_mustTranslateAll() {
        assertTrue(unlink(self::CONFIG_FILE_PATH));
//         TranslatingAutoLoader::reset();
        assertEquals('# Translated!', (new ToBeTranslatedClass2())->getText());
        assertEquals('Translated', (new NotToBeTranslatedClass2())->getText());
    }
}

