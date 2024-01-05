<?php

namespace Allofmex\TranslatingAutoLoader;

use function Composer\Autoload\includeFile;

/**
 * Wrapper for composer autoload. Translates class files, saves result to cache dir
 * and auto-loads cached version if needed.
 *
 */
class TranslatingAutoLoader {

    /**
     *
     * @var TranslatingAutoLoader
     */
    static $instance = null;
    static $loader = null;
    private $isToTranslateCb = null;

    public function init() {
        self::$instance = $this;
        if (file_exists(__DIR__.'/../../../autoload.php')) {
            $composerLoader = require __DIR__.'/../../../autoload.php';
        } else if (file_exists(__DIR__.'/../vendor/autoload.php')) {
            // testing environment only
            $composerLoader = require __DIR__.'/../vendor/autoload.php';
        } else {
            throw new \Exception('Composer autoloader not found!');
        }
        self::$loader = $composerLoader;
        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

    public static function loadClass($class) {
        $locale = defined('LANG') ? LANG : 'en';
        $classFile = self::$loader->findFile($class);
        if (!empty($classFile)) {
            if (self::$instance->isToTranslateCb == null) {
                self::$instance->loadConfig();
            }
            $whitelistCb = self::$instance->isToTranslateCb;
            if ('Allofmex\TranslatingAutoLoader\Translate' === $class) {
                include $classFile;
            } else if ($whitelistCb($class, $classFile)) {
//                 echo '<b>translating '.$class.'</b><br>';
                include Translate::translateFile($classFile, $locale);
            } else {
                include $classFile;
            }
        }
    }

    private function loadConfig() {
        $file = Translate::getTranslationsDir().'/translating_autoloader.config.php';
        if (file_exists($file)) {
            $config = require($file);
            if (isset($config['classToTranslate'])) {
                $this->isToTranslateCb = function($class, $classFile) use ($config){
                    // return true if class is white-listed
                    return in_array($class, $config['classToTranslate']);
                };
            }
        }
        if ($this->isToTranslateCb == null) {
            // no white-list in config
            $this->isToTranslateCb = function($class, $classFile) {
                // translate all own files, but no vendor packages
                return preg_match('/^((?!\/vendor\/).)*$/m', realpath($classFile)) === 1;
            };
        }
    }

    public function selfTranslate($file, $locale = null) {
        if ($locale == null) {
            $locale = defined('LANG') ? LANG : 'en';
        }
        $translatedFileName = Translate::translateFile($file, $locale);
        $existingUse = array();
        $translatedContent = file_get_contents($translatedFileName);

        // find line calling method translateFromHere($file), cut content in part before and after this line
        $parts = preg_split('/^.*'.FUN_NAME.'[\s]*\(.*\);/m', $translatedContent);
        if ($parts === false || count($parts) < 2) {
            throw new \Exception('No line with call to function '.FUN_NAME.' found in '.$file);
        }
        // extract 'use ' statements existing before call to this function to re-append later
        preg_match_all('/^use.*$/m', $parts[0], $existingUse);
        $existingUseStatements = implode(PHP_EOL, $existingUse[0]);

        // keep original context
        extract($GLOBALS);
        // run rest of calling file from prepared string and exit
        eval($existingUseStatements.PHP_EOL.$parts[1]);
        exit(0);
    }

    public static function unregister() {
        if (self::$instance !== null) {
            spl_autoload_unregister(array(self::$instance, 'loadClass'),);
            self::$instance = null;
        }
    }

    /**
     * For testing, invalidate loaded config.
     */
    public static function reset() {
        if (self::$instance !== null) {
            self::$instance->isToTranslateCb = null;
        }
    }
}
