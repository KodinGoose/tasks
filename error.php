<?php

declare(strict_types=1);

namespace error;

use DateTimeImmutable;
use DateTimeZone;

function logError(string $msg): void
{
    $time = (new DateTimeImmutable("now", (new DateTimeZone("UTC"))))->format("Y-m-d H:i:s p");
    if (file_put_contents("error_logs.txt", $time . ": " . $msg . "\n") === false) {
        $stdout = fopen('php://stdout', 'w');
        fwrite($stdout, "Failed to open \"error_logs.txt\" file\n");
        fclose($stdout);
    };
}
