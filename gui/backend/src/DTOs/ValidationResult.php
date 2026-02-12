<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs;

/**
 * Configuration validation result.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ValidationResult
{
    /**
     * @param bool $isValid Whether the configuration is valid
     * @param array<string> $errors List of validation error messages
     */
    public function __construct(
        private bool $isValid,
        private array $errors = []
    ) {}

    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array{isValid: bool, errors: array<string>}
     */
    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid,
            'errors' => $this->errors
        ];
    }
}