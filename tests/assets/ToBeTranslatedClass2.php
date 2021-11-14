<?php
namespace Allofmex\TranslatingAutoLoader\Test;

use Allofmex\TranslatingAutoLoader\Test\ToBeTranslatedClass;
/**
 * Exact same as {@link ToBeTranslatedClass}. Each test needs own class, because class can only be 'required'
 * once. In second test, the previously manipulated(translated) class would be used.
 * Make sure content is exactly the same as first class, just different name.
 */
class ToBeTranslatedClass2 {
    public function getText() {
        return "# {t}Zu übersetzen{/t}!";
    }
}

