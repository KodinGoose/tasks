<?php

declare(strict_types=1);

namespace Error;

use DateTimeImmutable;
use DateTimeZone;

function logError(string $msg): void
{
    $time = (new DateTimeImmutable("now", (new DateTimeZone("UTC"))))->format("Y-m-d H:i:s T");
    file_put_contents("error_logs.txt", $time.': '.$msg."\n", LOCK_EX | FILE_APPEND);
}
