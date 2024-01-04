<?php
namespace Allofmex\TranslatingAutoLoader;

use PHPUnit\Framework\TestCase;

class TokenSetTest extends TestCase {
    public function testDefault_keepStartStr_correct() : void {
        $set = TokenSet::default();
        $this->assertEquals('{n}', $set->keepStartStr());
    }

    public function testDefault_keepRegStr_correct() : void {
        $set = TokenSet::default();
        $this->assertEquals('/{n}([\s\S]+?){\/n}/', $set->keepRegStr());
    }

    public function testDefault_translateRegStr_correct() : void {
        $set = TokenSet::default();
        $this->assertEquals('/{t}([\s\S]+?){\/t}/', $set->translateRegStr());
    }
}