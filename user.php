<?php

declare(strict_types=1);

class User
{
    public static function modifyPasswordDB(DB $db, int $uid, string $password_hash): true|null
    {
        return $db->logError($db->connection->execute_query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            array($password_hash, $uid)
        ));
    }

    public static function getIdViaUsernameDB(DB $db, string $username): int|null {
        return ($ret = $db->logError($db->connection->execute_query(
            'SELECT id FROM users WHERE username = ?',
            array($username)
        ))) === null ? null : $ret[0][0];
    }

    public static function getUsernameDB(DB $db, int $uid): int|null {
        return ($ret = $db->logError($db->connection->execute_query(
            'SELECT username FROM users WHERE id = ?',
            array($uid)
        ))) === null ? null : $ret[0][0];
    }

    public static function newDB(DB $db, string $username, string $password_hash): true|null
    {
        return $db->logError($db->connection->execute_query(
            'INSERT INTO users (username, password_hash) VALUE (?, ?)',
            array($username, $password_hash)
        ));
    }

    public static function deleteDB(DB $db, int $uid): true|null
    {
        return $db->logError($db->connection->execute_query(
            'DELETE FROM tasks WHERE id = ?',
            array($uid)
        ));
    }

    public static function existsDB(DB $db, int $uid): bool|null
    {
        return $db->logError($db->connection->execute_query(
            'SELECT EXISTS (SELECT * FROM users WHERE id = ?)',
            array($uid)
        ));
    }

    public static function existsViaUsernameDB(DB $db, string $username): bool|null
    {
        return $db->logError($db->connection->execute_query(
            'SELECT EXISTS (SELECT * FROM users WHERE username = ?)',
            array($username)
        ));
    }

    public static function getPasswordHashDB(DB $db, int $uid): string|null
    {
        return ($ret = $db->logError($db->connection->execute_query(
            'SELECT password_hash FROM users WHERE id = ?',
            array($uid)
        ))) === null ? null : $ret[0][0];
    }
}
