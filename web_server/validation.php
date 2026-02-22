<?php

declare(strict_types=1);

namespace Validation;

use DB\DB;
use JWT\JWT;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use User\User;

require_once 'db.php';
require_once 'user.php';
require_once 'jwt.php';

class Validator
{
    /**
     * Passing in an undefined variable to $number is allowed
     * Use the "@" operator to make php stfu
     */
    public static function integer(mixed $number): int|null
    {
        if (isset($number) === false or is_int($number) === false) return null;
        return $number;
    }

    /**
     * Passing in an undefined variable to $number is allowed
     * Use the "@" operator to make php stfu
     */
    public static function bool(mixed $bool): bool|null
    {
        if (isset($bool) === false or is_bool($bool) === false) return null;
        return $bool;
    }

    /**
     * Passing in an undefined variable to $string is allowed
     * Use the "@" operator to make php stfu
     */
    public static function string(mixed $string, int|null $max_chars = null, int $min_chars = 0): string|null
    {
        if (isset($string) === false or is_string($string) === false) return null;
        if (strlen($string) < $min_chars or strlen($string) > $max_chars) return null;
        return $string;
    }

    public static function getRefreshToken(DB $db, JWT $jwt): UnencryptedToken|false|null {
        if (isset($_COOKIE["RefreshToken"]) === false or is_string($_COOKIE["RefreshToken"]) === false) return false;
        $token = $jwt->parseToken($_COOKIE["RefreshToken"]);
        if ($token === false) return false;
        if ($jwt->validateRefreshToken($token) === false) return false;
        $ret = $db->isRevokedToken($token->claims()->get("uid"), $token->claims()->get(RegisteredClaims::ID));
        if ($ret === null) return null;
        if ($ret === true) return false;
        $ret = User::existsDB($db, $token->claims()->get("uid"));
        if ($ret === null) return null;
        if ($ret === false) return false;
        return $token;
    }

    public static function getAccessToken(DB $db, JWT $jwt): UnencryptedToken|false|null {
        if (isset($_COOKIE["AccessToken"]) === false or is_string("AccessToken") === false) return false;
        $token = $jwt->parseToken($_COOKIE["AccessToken"]);
        if ($token === false) return false;
        if ($jwt->validateAccessToken($token) === false) return false;
        $ret = $db->isRevokedToken($token->claims()->get("uid"), $token->claims()->get(RegisteredClaims::ID));
        if ($ret === null) return null;
        if ($ret === true) return false;
        $ret = User::existsDB($db, $token->claims()->get("uid"));
        if ($ret === null) return null;
        if ($ret === false) return false;
        return $token;
    }
}
