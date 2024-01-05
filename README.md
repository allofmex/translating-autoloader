# Translating Autoloader

This is a **translating tool** for multilingual php code. It is based on the idea of [Thomas Bley's](https://we-love-php.blogspot.com/2012/07/how-to-implement-i18n-without.html) and the comments by [Glitch Desire](https://stackoverflow.com/a/19425499).

The main idea was, to auto-translate php class files via php text search/replace (everything tagged like `{t}translate me{/t}`), and to use this translated (and cached) version in `require`. The translation work happens only once per file and the cached version can be used furtheron with almost the same performance as if the translation would be hard-coded.

This implementation extends the original concept by the following:

- Added **auto-load** feature

  Translation happens at Composer autoload step. You do not need to call any translate() function. Whenever a class is used, the original class **file** is searched via composer (but not included yet). The found file will be translated, cached and **then included**. Except the line `require ../vendor/autoload.php'`, a few changes in your entry-page files, and of course the translation markers in your strings, no further code changes are needed.

- **Skip tags** `{n}` added

  Parts within the to-be-translated strings can be kept from original text. Use-full for example if you have `<a href="...">link</a>` in your texts. Wrap it in `{t}...{n}<a href...>{/n}...{/t}` and you don't need to split your strings in multiple parts.

- Translation files as **yaml**

  This allows to use multi-line values in translation files, which improves readability especially if html code is inlcuded in target translation text.


## How-to-use

##### Composer

Add to your `composer.json`:

```
"require" : {
    "allofmex/translating-autoloader" : "~1"
},
"repositories": [
    {
        "type": "git",
        "url":  "https://github.com/allofmex/translating-autoloader.git",
        "reference": "master"
    }
],
```

##### Translation marker

Mark all strings in your code that you want translate

> templates/my-page.php

```
<body>
    <div>
    {t}Text mit Tags{t}
    </div>
    
    <p>BRANDING: {t}Klick {n}first link{/n} oder {n}<?php echo 'another link';?>{/n}.{/t}</p>
</body>
```
> src/MyClass.php

```
class MyClass {
    function getText() {
        return '{t}Zu übersetzen{/t}'.'...';
    }
}
```

##### Language files

Create language files like `translations/en.yml`

```
Zu übersetzen : Translated
Text mit Tags : |
    This is text with tags, in this case even a list
    <ul>
        <li>number 1</li>
    </ul>
Klick {first link} oder {another link}. : |
    {n}{/n} or {n}{/n} is clickable.
```

You may choose a custom translation-files path by setting `TRANSLATIONS_ROOT` constant like `define(TRANSLATIONS_ROOT, '/var/www-root/my-translations');`

##### Auto-loading
Change your auto-loading calls:

```
// require ROOT_PATH.'../vendor/autoload.php';` <-- replace
require ROOT_PATH.'../vendor/allofmex/translating-autoloader/src/autoload.php';
define('LANG', 'en');
...
$myClass = new MyClass();
echo $myClass->getText(); // will output 'Translated'
```

For entrypoint/non-class files:
> www-root/my-page.php

```

// require ROOT_PATH.'../vendor/autoload.php';` <-- replace
require ROOT_PATH.'../vendor/allofmex/translating-autoloader/src/autoload.php';

use Allofmex\TranslatingAutoLoader\Translate;

define('LANG', 'en');

require Translate::translateFile(ROOT_PATH.'../templates/navigation.php', LANG);
require Translate::translateFile(ROOT_PATH.'../templates/my-page.php', LANG);
```

### Additional details
##### Tags
Wrap all texts that need to be translated in `{t} {/t}`. You may also insert (multiple) `{n} {/n}` sections within those strings to keep original test.

```
$string = "not translated, {t}will be <b>translated</b>{n} except <i>part within 'n'{/n}, translation 
will continue here.</i>{/t} This will not be translated.
```

You don't need to mind html tag positions or even php code. All within {t} will be replaced via raw text search/replace and code will be parsed by php only AFTER translation is done.

Just make sure to encode special character like colon, semicolon,... in your `translations/xx.yml` files with there html entity like `&lt;` or use multiline ( | ) yml style.

If your key may become too short (e.g. if your real text may have a {n} section early in string), just use a longer string in code and handle correct format in translation.
Example:
- Instead of `echo {t}This {n}link{/n} needs translation{/t}.` (key would be only 'This') 
- use `echo {t}link to translate {n}link{/n}{/t}.` and in translation file the real order: `link to translate: This {n}link{/n} needs translation `


##### Define only specific classes to be translated

By default, all auto-loaded classes will be translated. If most of your classes do not have text-to-translate, you may whitelist only specific classes to franslation by creating the following file

> translations/translating_autoloader.config.php

```
<?php
return array(
        'classToTranslate' => array(
                Your\NameSpace\ToBeTranslatedClass::class,
                ...
        )
);
```

##### Cache
Files will be translated only once on very first access (file-changed time, not per client-session). Translated result is saved to `var/cache/` (`en_filename.php`) and loaded from there on following auto-load calls. In case of problems, you may delete the cached files, they will be recreated on next access.

Custom cache path may be set by setting `TRANSLATIONS_CACHE` constant like `define(TRANSLATIONS_CACHE, '/tmp/my/cache');`

## Testing

Run test as

```
vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/
```

Or straight with docker (call from TranslatingAutoloader root dir, where directories `tests` and `src` are)

```
docker run -it -v $PWD:/src --workdir=/src php:8-alpine vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/
```

## Contribution

This package is not available on Composer package repository since I don't have the capacity to maintain it. You are welcome to open a pull request or start your own fork.
