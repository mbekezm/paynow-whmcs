<?php

declare(strict_types=1);

namespace PaynowZW;

/**
 * Represents a Paynow status update, whether it arrived via the resulturl
 * callback or via a poll of the pollurl. Both shapes carry the same fields.
 */
final class StatusResponse
{
    private array $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function reference(): string
    {
        return (string) ($this->fields['reference'] ?? '');
    }

    public function paynowReference(): string
    {
        return (string) ($this->fields['paynowreference'] ?? '');
    }

    public function amount(): string
    {
        return (string) ($this->fields['amount'] ?? '');
    }

    public function status(): string
    {
        return strtolower((string) ($this->fields['status'] ?? ''));
    }

    public function pollUrl(): string
    {
        return (string) ($this->fields['pollurl'] ?? '');
    }

    public function isSettled(): bool
    {
        return in_array($this->status(), ['paid', 'awaiting delivery', 'delivered'], true);
    }

    public function raw(): array
    {
        return $this->fields;
    }
}
