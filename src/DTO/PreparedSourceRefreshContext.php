<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\DTO;

final readonly class PreparedSourceRefreshContext
{
    public function __construct(
        public string $reportKey,
        public string $triggeredBy,
        public bool $force,
        public bool $clearCacheAfterRefresh,
        public ?string $lockOwner = null,
    ) {
    }
}