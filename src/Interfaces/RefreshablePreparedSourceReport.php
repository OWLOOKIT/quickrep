<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Interfaces;

use Owlookit\Quickrep\DTO\PreparedSourceRefreshContext;
use Owlookit\Quickrep\DTO\PreparedSourceRefreshResult;

interface RefreshablePreparedSourceReport extends PreparedSourceReport
{
    public function refreshPreparedSource(PreparedSourceRefreshContext $context): PreparedSourceRefreshResult;
}