<?php

declare(strict_types=1);

namespace DB;

use Config\Config;
use DateTimeImmutable;
use Exception;
use mysqli;
use mysqli_result;

use function error\logError;

require_once "error.php";
require_once "config.php";

class DB
{
    public mysqli $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public static function init(): DB|null
    {
        try {
            $connection = mysqli_connect(Config::$database_address, Config::$database_username, Config::$database_password, Config::$database_name);
            if ($connection === false) {
                return null;
            }
            return new DB($connection);
        } catch (Exception) {
            logError(mysqli_connect_error());
            return null;
        }
    }

    public function logError(mysqli_result|bool $result): array|true|null
    {
        if ($result === false) {
            logError($this->connection->error);
            return null;
        }
        if ($result === true) return true;
        return $result->fetch_all();
    }

    public function isRevokedToken(int $uid, string $token_id): bool|null
    {
        return ($ret = $this->logError($this->connection->execute_query(
            'SELECT EXISTS (SELECT * FROM tokens WHERE uid = ? AND token_id = ? AND revoked = TRUE)',
            array($uid, $token_id)
        ))) === null ? null : $ret[0][0] === 1;
    }

    public static function revokeAllTokens(DB $db, int $uid): true|null
    {
        return $db->logError($db->connection->execute_query(
            'UPDATE tokens SET revoked = TRUE WHERE uid = ?',
            array($uid)
        ));
    }

    public function revokeToken(int $uid, string $token_id): true|null
    {
        return $this->logError($this->connection->execute_query(
            'UPDATE tokens SET revoked = TRUE WHERE uid = ? AND token_id = ?',
            array($uid, $token_id)
        ));
    }

    public function newToken(int $uid, string $token_id, DateTimeImmutable $delete_after): true|null
    {
        return $this->logError($this->connection->execute_query(
            'INSERT INTO tokens (uid, token_id, delete_after) VALUE (?, ?, ?)',
            array($uid, $token_id, $delete_after->format("Y-m-d H:i:s"))
        ));
    }
}
