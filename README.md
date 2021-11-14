# Translating Autoloader

This is a **translating tool** for multilingual php code. It is based on the idea of [Thomas Bley's](https://we-love-php.blogspot.com/2012/07/how-to-implement-i18n-without.html) and the comments by [Glitch Desire](https://stackoverflow.com/a/19425499).

The main idea was, to auto-translate php class files via php text search/replace (everything tagged like `{t}translate me{/t}`), and to use this translated (and cached) version in `require`. The translation work happens only once per file and the cached version can be used furtheron with almost the same performance as if the translation would be hard-coded.

This implementation extends the original concept by the following:

- Added **auto-load** feature

  Translation happens at Composer autoload step. You do not need to call any translate() function. Whenever a class is used, the original class **file** is searched via composer (but not included yet). The found file will be translated, cached and **then included**. Except the line `require ../vendor/autoload.php'`, a few changes in your entry-page files, and of course the translation markers in your strings, no further code changes are needed.

- **Skip tags** `{n}` added

  Parts within the to-be-translated strings can be kept from original text. Use-full for example if you have `<a href="...">link</a>` in your texts. Wrap it in `{n}<a href...{/t}` and you don't need to split your strings in multiple parts.

- Translation files as **yaml**

  This allows to use multi-line values in translation files, which improves readability especially if html code is to be translated.


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
    {n}{/n} or {n}{/t} is clickable.
```


##### Auto-loading

For entrypoint pages use
> www-root/my-page.php

```
// require ROOT_PATH.'../vendor/autoload.php';` <-- replace
require ROOT_PATH.'../vendor/allofmex/translating-autoloader/src/autoload.php';

use Allofmex\TranslatingAutoLoader\Translate;

define('USER_LOCALE', 'en');

require Translate::translateFile(ROOT_PATH.'../templates/my-page.php', USER_LOCALE);
```

### Prepare your to-be-translated classes

Wrap all texts that need to be translated in `{t} {/t}`. You may also insert (multiple) `{n} {/n}`
sections within those strings to keep original test.

```
$string = "not translated, {t}will be <b>translated</b>{n} except <i>part within 'n'{/n}, translation 
will continue here.</i>{/t} This will not be translated.
```

You don't need to mind html tag positions or even php code. All will be replaced via raw text search/replace
and code will be parsed by php only AFTER translation is done.

Just make sure to encode special character like colon, semicolon,... with there html entity like `&lt`;

## Testing

Run

```
vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/
```

## Contribution

This package is not available on Composer package repository since I don't have the capacity to maintain it. You are welcome to open a pull request or start your own fork.
