<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\DTO;

final readonly class PreparedSourceRefreshResult
{
    public function __construct(
        public bool $success,
        public ?int $rowCount = null,
        public ?string $message = null,
        public ?array $extra = null,
    ) {
    }

    public static function success(?int $rowCount = null, ?string $message = null, ?array $extra = null): self
    {
        return new self(true, $rowCount, $message, $extra);
    }

    public static function failed(?string $message = null, ?array $extra = null): self
    {
        return new self(false, null, $message, $extra);
    }
}