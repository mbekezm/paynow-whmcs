<?php

declare(strict_types=1);

namespace PaynowZW;

/**
 * Represents the parsed response to a Paynow initiate transaction request.
 */
final class InitResponse
{
    private array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function isSuccessful(): bool
    {
        return strtolower((string) ($this->fields['status'] ?? '')) === 'ok';
    }

    public function browserUrl(): string
    {
        return (string) ($this->fields['browserurl'] ?? '');
    }

    public function pollUrl(): string
    {
        return (string) ($this->fields['pollurl'] ?? '');
    }

    public function errorMessage(): string
    {
        return (string) ($this->fields['error'] ?? '');
    }

    public function raw(): array
    {
        return $this->fields;
    }
}
