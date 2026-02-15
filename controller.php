<?php

declare(strict_types=1);

namespace Controller;

use DateTimeImmutable;
use DB\DB;
use JWT\JWT;
use Lcobucci\JWT\Token\RegisteredClaims;
use Task\Task;
use User\User;
use Validation\Validator;

require 'validation.php';
require 'db.php';

class Controller
{
    public function login(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $data = json_decode(file_get_contents("php://input"));
        $username = Validator::string(@$data->username, 255);
        if ($username === null) return result(Result::bad_request);
        $password = Validator::string(@$data->password, 255, 12);
        if ($password === null) return result(Result::bad_request);

        $uid = User::getIdViaUsernameDB($db, $username);
        if ($uid === null) return result(Result::unexpected_error);
        $ret = User::existsDB($db, $uid);
        if ($ret === null) return result(Result::unexpected_error);
        if ($ret === false) return result(Result::unauthorised);
        $ret = User::getPasswordHashDB($db, $uid);
        if ($ret === null) return result(Result::unexpected_error);
        if (password_verify($password, $ret) === false) return result(Result::unauthorised);

        if (password_needs_rehash($ret, PASSWORD_DEFAULT) === true) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($password_hash === false) return result(Result::unexpected_error);
            if (User::modifyPasswordDB($db, $uid, $password_hash) === null) return result(Result::unexpected_error);
        }

        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $refresh_token = $jwt->issueRefreshToken($uid);
        $ret = $db->newRefreshToken($uid, $refresh_token->claims()->get(RegisteredClaims::ID), new DateTimeImmutable()->modify("+7 day"));
        if ($ret === null) return result(Result::unexpected_error);
        $access_token = $jwt->issueAccessToken($uid);

        $arr_cookie_options = array(
            'Max-Age' => 60 * 60 * 24 * 7,
            'path' => '/user/refresh/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', $refresh_token->toString(), $arr_cookie_options);

        $arr_cookie_options = array(
            'Max-Age' => 60 * 5,
            'path' => '/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('AccessToken', $access_token->toString(), $arr_cookie_options);

        return result(Result::success);
    }

    public function register(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $data = json_decode(file_get_contents("php://input"));
        $username = Validator::string(@$data->username, 255);
        if ($username === null) return result(Result::bad_request);
        $password = Validator::string(@$data->password, 255, 12);
        if ($password === null) return result(Result::bad_request);

        $ret = User::existsViaUsernameDB($db, $username);
        if ($ret === null) return result(Result::unexpected_error);
        if ($ret === true) return result(Result::user_already_exists);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($password_hash === false) return result(Result::unexpected_error);
        if (User::newDB($db, $username, $password_hash) === null) return result(Result::unexpected_error);

        return result(Result::success_created);
    }

    public function modPassword(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getAccessToken($db, $jwt);
        $data = json_decode(file_get_contents("php://input"));
        $old_password = Validator::string(@$data->old_password, 255, 12);
        if ($old_password === null) return result(Result::bad_request);
        $password = Validator::string(@$data->password, 255, 12);
        if ($password === null) return result(Result::bad_request);

        $ret = User::getPasswordHashDB($db, $token->claims()->get("uid"));
        if ($ret === null) return result(Result::unexpected_error);
        if (password_verify($old_password, $ret) === false) return result(Result::unauthorised);

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($password_hash === false) return result(Result::unexpected_error);
        if (User::modifyPasswordDB($db, $token->claims()->get("uid"), $password_hash) === null) return result(Result::unexpected_error);

        if ($db->revokeAllRefreshTokens($db, $token->claims()->get("uid")) === null) return result(Result::unexpected_error);

        $arr_cookie_options = array(
            'Max-Age' => 0,
            'path' => '/user/refresh/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', "", $arr_cookie_options);

        $arr_cookie_options = array(
            'Max-Age' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('AccessToken', "", $arr_cookie_options);

        return result(Result::success);
    }

    public function deleteUser(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getAccessToken($db, $jwt);
        if ($token === null) return result(Result::unexpected_error);
        if ($token === false) return result(Result::unauthorised);
        $data = json_decode(file_get_contents("php://input"));
        $password = Validator::string(@$data->password, 255, 12);
        if ($password === null) return result(Result::bad_request);

        $ret = User::getPasswordHashDB($db, $token->claims()->get("uid"));
        if ($ret === null) return result(Result::unexpected_error);
        if (password_verify($password, $ret) === false) return result(Result::unauthorised);

        if (User::deleteDB($db, $token->claims()->get("uid")) === null) return result(Result::unexpected_error);

        if ($db->revokeAllRefreshTokens($db, $token->claims()->get("uid")) === null) return result(Result::unexpected_error);

        $arr_cookie_options = array(
            'Max-Age' => 0,
            'path' => '/user/refresh/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', "", $arr_cookie_options);

        $arr_cookie_options = array(
            'Max-Age' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('AccessToken', "", $arr_cookie_options);

        return result(Result::success);
    }

    public function refreshRefreshToken(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getRefreshToken($db, $jwt);
        if ($token === null) return result(Result::unexpected_error);
        if ($token === false) return result(Result::unauthorised);

        $ret = $db->revokeRefreshToken($token->claims()->get("uid"), $token->claims()->get(RegisteredClaims::ID));
        if ($ret === null) return result(Result::unexpected_error);
        $new_token = $jwt->issueRefreshToken($token->claims()->get("uid"));

        $arr_cookie_options = array(
            'Max-Age' => 60 * 60 * 24 * 7,
            'path' => '/user/refresh/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('RefreshToken', $new_token->toString(), $arr_cookie_options);

        return result(Result::success);
    }

    public function getAccessToken(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getRefreshToken($db, $jwt);
        if ($token === null) return result(Result::unexpected_error);
        if ($token === false) return result(Result::unauthorised);

        $new_token = $jwt->issueAccessToken($token->claims()->get("uid"));

        $arr_cookie_options = array(
            'Max-Age' => 60 * 5,
            'path' => '/',
            'domain' => '',
            'secure' => php_sapi_name() !== "cli-server",
            'httponly' => true,
            'samesite' => 'Strict'
        );
        setcookie('AccessToken', $new_token->toString(), $arr_cookie_options);

        return result(Result::success);
    }

    public function getTasks(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getAccessToken($db, $jwt);
        if ($token === null) return result(Result::unexpected_error);
        if ($token === false) return result(Result::unauthorised);

        $ret = Task::allDB($db, $token->claims()->get("uid"));
        if ($ret === null) return result(Result::unexpected_error);
        header("Content-Type: application/json");
        echo json_encode($ret);

        return result(Result::success);
    }

    public function newTask(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getAccessToken($db, $jwt);
        if ($token === null) return result(Result::unexpected_error);
        if ($token === false) return result(Result::unauthorised);
        $data = json_decode(file_get_contents("php://input"));
        $title = Validator::string(@$data->title, 255);
        if ($title === null) return result(Result::bad_request);

        if (Task::newDB($db, $token->claims()->get("uid"), $title) === null) return result(Result::unexpected_error);

        return result(Result::success_created);
    }

    public function modTask(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getAccessToken($db, $jwt);
        if ($token === null) return result(Result::unexpected_error);
        if ($token === false) return result(Result::unauthorised);
        $data = json_decode(file_get_contents("php://input"));
        $id = Validator::integer(@$data->id);
        if ($id === null) return result(Result::bad_request);
        $title = Validator::string(@$data->title, 255);
        if ($title === null) return result(Result::bad_request);
        $done = Validator::bool(@$data->done);

        $ret = Task::taskOwnedByUser($db, $token->claims()->get("uid"), $id);
        if ($ret === null) return result(Result::unexpected_error);
        if ($ret === false) return result(Result::unauthorised);
        $ret = (new Task($id, $title, $done))->modifyDB($db, $token->claims()->get("uid"));
        if ($ret === null) return result(Result::unexpected_error);

        return result(Result::success);
    }

    public function delTask(): null
    {
        $db = DB::init();
        if ($db === null) return result(Result::unexpected_error);
        $jwt = JWT::init();
        if ($jwt === false) return result(Result::unexpected_error);
        $token = Validator::getAccessToken($db, $jwt);
        if ($token === null) return result(Result::unexpected_error);
        if ($token === false) return result(Result::unauthorised);
        $data = json_decode(file_get_contents("php://input"));
        $id = Validator::integer(@$data->id);
        if ($id === null) return result(Result::bad_request);

        $ret = Task::taskOwnedByUser($db, $token->claims()->get("uid"), $id);
        if ($ret === null) return result(Result::unexpected_error);
        if ($ret === false) return result(Result::unauthorised);
        $ret = Task::deleteDB($db, $token->claims()->get("uid"), $id);
        if ($ret === null) return result(Result::unexpected_error);

        return result(Result::success);
    }
}

function result(Result $result): void
{
    switch ($result) {
        case Result::success:
            http_response_code(200);
            break;
        case Result::success_created:
            http_response_code(201);
            break;
        case Result::bad_request:
            http_response_code(400);
            echo "Bad request";
            break;
        case Result::unauthorised:
            http_response_code(403);
            echo "Unauthorised";
            break;
        case Result::unexpected_error:
            http_response_code(500);
            echo "Unexpected error";
            break;
        default:
            http_response_code(500);
            echo "Unexpected error";
            break;
    }
}

enum Result
{
    case success;
    case success_created;
    case bad_request;
    case unauthorised;
    case user_already_exists;
    case unexpected_error;
}
