<?php
namespace Allofmex\TranslatingAutoLoader;

class TokenSet {

    public const CURVED_BRACKETS = ['{', '}'];

    private $enclosure;
    private $translateTag;
    private $keepTag;

    /**
     * Original {t} and {n} tags.
     * "{t}Translate me {n}except this{/n} but including this{/t}"
     * @return \Allofmex\TranslatingAutoLoader\TokenSet
     */
    public static function default() : TokenSet {
        return new TokenSet('t', 'n', self::CURVED_BRACKETS);
    }

    public static function custom(string $translateTag, string $keepTag, string $startBracket, $endBracket) : TokenSet {
        return new TokenSet($translateTag, $keepTag, [$startBracket, $endBracket]);
    }

    private function __construct(string $translateTag, $keepTag, array $enclosure) {
        if (count($enclosure) !== 2) {
            throw new \Exception("Invalid enclosure, must contain of two items for start and stop char");
        }
        if (empty($translateTag) || empty($keepTag)) {
            throw new \Exception("Tags must not be empty!");
        }
        $this->enclosure = $enclosure;
        $this->translateTag = $translateTag;
        $this->keepTag = $keepTag;
    }

    /**
     *
     * @return string regex string like '/{t}([\s\S]+?){\/t}/' matching content of tag
     */
    function translateRegStr() : string {
        return $this->makeRegStr($this->translateTag);
    }

    function keepRegStr() : string {
        return $this->makeRegStr($this->keepTag);
    }

    function keepStartStr() : string {
        return $this->makeTag($this->keepTag);
    }

    private function makeRegStr(string $tag) {
        return '/'.$this->makeTag($tag).'([\s\S]+?)'.$this->makeTag('\/'.$tag).'/';
    }

    private function makeTag(string $tagInner) : string {
        return "{$this->enclosure[0]}{$tagInner}{$this->enclosure[1]}";
    }


}