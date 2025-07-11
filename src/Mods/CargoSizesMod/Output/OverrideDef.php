<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\Output;

use AppUtils\Interfaces\StringableInterface;

class OverrideDef implements StringableInterface
{
    private string $comment;
    private string $value;
    private string $path;
    private string $macroName;

    public function __construct(string $macroName)
    {
        $this->macroName = $macroName;
    }

    public function setMacroPath(string $path) : self
    {
        return $this->setPath(sprintf(
            "//macro[@name='%s']/%s",
            $this->macroName,
            ltrim($path, '/')
        ));
    }

    public function setPath(string $path) : self
    {
        $this->path = $path;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setString(string $value) : self
    {
        $this->value = $value;
        return $this;
    }

    public function setInt(int $value) : self
    {
        return $this->setString((string)$value);
    }

    public function setFloat(float $value, int $precision=2) : self
    {
        return $this->setString(number_format($value, $precision, '.', ''));
    }

    public function setComment(string|StringableInterface $comment, ...$args) : self
    {
        $this->comment = vsprintf((string)$comment, $args);
        return $this;
    }

    public function render() : string
    {
        $lines = array();

        if(!empty($this->comment)) {
            $lines[] = sprintf('    <!-- %s -->', str_replace("\n", " ", $this->comment));
        }

        $lines[] = $this->renderOverride();

        return implode("\n", $lines);
    }

    protected function renderOverride() : string
    {
        return sprintf(
            '    <replace sel="%s">%s</replace>',
            $this->path,
            $this->value
        );
    }

    public function __toString(): string
    {
        return $this->render();
    }
}