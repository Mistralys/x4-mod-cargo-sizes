<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use AppUtils\AttributeCollection;

class TagOverrideDef extends OverrideDef
{
    private string $tagName;

    private AttributeCollection $attributes;

    /**
     * @var string[]
     */
    private array $comments = array();
    private bool $addMode = false;

    public function __construct(string $macroName)
    {
        parent::__construct($macroName);

        $this->attributes = AttributeCollection::create();
    }

    /**
     * Whether to use add mode, which will use the `<add>` tag instead of `<replace>`
     * to add the tag to the XML file instead of replacing it.
     *
     * @param bool $enable
     * @return $this
     */
    public function enableAddMode(bool $enable) : self
    {
        $this->addMode = $enable;
        return $this;
    }

    public function getPath(): string
    {
        $path = parent::getPath();

        $tagName = $this->getTagName();

        // When adding a tag, the tag name must not be present in the path.
        if($this->addMode)
        {
            if(str_contains($path, $tagName)) {
                $parts = explode('/', $path);
                array_pop($parts);
                $path = implode('/', $parts);
            }
        }
        // When replacing a tag, the tag name must be present in the path.
        else if(!str_contains($path, $tagName))
        {
            $path = rtrim($path, '/') . '/' . $this->getTagName();
        }

        return $path;
    }

    public function setTagName(string $name) : self
    {
        $this->tagName = $name;
        return $this;
    }

    public function getTagName() : string
    {
        return $this->tagName;
    }

    public function setAttribute(string $name, string $value) : self
    {
        $this->attributes->attr($name, $value);
        return $this;
    }

    public function addComment(string $comment, ...$args) : self
    {
        $this->comments[] = vsprintf($comment, $args);
        return $this;
    }

    private const TAG_TEMPLATE = <<<'XML'
    <%5$s sel="%1$s">
        <!--
%4$s
        -->
        <%2$s %3$s/>
    </%5$s>
XML;

    protected function renderOverride() : string
    {
        $comments = array();

        foreach($this->comments as $comment) {
            $comments[] = sprintf('            %s', $comment);
        }

        $overrideTag = 'replace';
        if($this->addMode) {
            $overrideTag = 'add';
        }

        return sprintf(
            self::TAG_TEMPLATE,
            $this->getPath(),
            $this->getTagName(),
            (string)$this->attributes,
            implode("\n", $comments),
            $overrideTag
        );
    }
}
