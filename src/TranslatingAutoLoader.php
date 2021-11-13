<?php

namespace Allofmex\TranslatingAutoLoader;

use function Composer\Autoload\includeFile;

class TranslatingAutoLoader {

    static $instance = null;
    static $loader = null;
    static $toTranslate = null; 

    public function init() {
        self::$instance = $this;
        $composerLoader = require $_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php';
        if (empty($composerLoader)) {
            throw new \Exception('Composer autoloader not found!');
        }
        self::$loader = $composerLoader;
        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

    public static function loadClass($class) {
        $locale = defined('LANG') ? LANG : 'en';
        $classFile = self::$loader->findFile($class);
        if (!empty($classFile)) {
            if (self::$toTranslate == null) {
                self::loadConfig();
            }
            if ('PreProcessTranslator\Translate' === $class) {
                include $classFile;
            } else if (in_array($class, self::$toTranslate)) {
                echo '<b>translating '.$class.'</b><br>';
                include Translate::translateFile($classFile, $locale);
            } else {
                include $classFile;
            }
        }
    }

    private static function loadConfig() {
        $basePath = $_SERVER['DOCUMENT_ROOT'];
        $file = $basePath.'/../config/translating_auto_loader.config.php';
        if (file_exists($file)) {
            $config = require($file);
            self::$toTranslate = isset($config['classToTranslate']) ? $config['classToTranslate'] : array();
        } else {
            self::$toTranslate = array();
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
        preg_match_all('/^use.*$/m', $parts[0], $existingUse);
        $existingUseStatements = implode(PHP_EOL, $existingUse[0]);

        // keep original context
        extract($GLOBALS);
        eval($existingUseStatements.PHP_EOL.$parts[1]);
        exit(0);
    }
}
