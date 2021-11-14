<?php
namespace Allofmex\TranslatingAutoLoader\Test;

use Allofmex\TranslatingAutoLoader\Test\NotToBeTranslatedClass;

/**
 * See note at {@link ToBeTranslatedClass2}
 */
class NotToBeTranslatedClass2 {
    public function getText() {
        return "{t}Zu übersetzen{/t}";
    }
}

