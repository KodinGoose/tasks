<?php

declare(strict_types=1);

namespace Error;

use DateTimeImmutable;
use DateTimeZone;
use DB\DB;

function logError(DB $db, string $msg): void
{
    $time = (new DateTimeImmutable("now", (new DateTimeZone("UTC"))))->format("Y-m-d H:i:s");
    $db->connection->execute_query(
        'INSERT INTO error_logs (time_, message) VALUE (?, ?)',
        array($time, $msg)
    );
}
