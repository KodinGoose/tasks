<?php

declare(strict_types=1);

namespace router;

use Controller\Controller;

require 'vendor/autoload.php';
require_once 'controller.php';

class PathIterator
{
    public array $splt_path;
    public int $i;

    public function __construct()
    {
        $this->splt_path = explode("/", $_SERVER["REQUEST_URI"]);
        // Skip website name
        $this->i = 1;
    }

    public function next(): string|null
    {
        if (count($this->splt_path) <= $this->i) {
            return null;
        }
        $this->i += 1;
        return $this->splt_path[$this->i - 1];
    }
}

function endpointNotFound(): void
{
    http_response_code(404);
    header("Content-Type: text/plain");
    echo "Endpoint not found";
}

$controller = new Controller();
$path = new PathIterator();
switch ($path->next()) {
    case "":
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            http_response_code(405);
            break;
        }
        header('Location: resource/index.html', true, 308);
        break;
    case "resource":
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            http_response_code(405);
            break;
        }
        return false;
        break;
    case "tasks":
        switch ($path->next()) {
            case "tasks":
                if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                    http_response_code(405);
                    break;
                }
                $controller->getTasks();
                break;
            case "new":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                $controller->newTask();
                break;
            case "mod":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                $controller->modTask();
                break;
            case "del":
                if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
                    http_response_code(405);
                    break;
                }
                $controller->delTask();
                break;
            default:
                endpointNotFound();
                break;
        }
        break;
    case "user":
        switch ($path->next()) {
            case "login":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                $controller->login();
                break;
            case "register":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                $controller->register();
                break;
            case "mod_password":
                if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                    http_response_code(405);
                    break;
                }
                $controller->modPassword();
                break;
            case "delete":
                if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
                    http_response_code(405);
                    break;
                }
                $controller->deleteUser();
                break;
            case "refresh":
                switch ($path->next()) {
                    case "refresh_token":
                        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                            http_response_code(405);
                            break;
                        }
                        $controller->refreshRefreshToken();
                        break;
                    case "access_token":
                        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
                            http_response_code(405);
                            break;
                        }
                        $controller->getAccessToken();
                        break;
                    default:
                        endpointNotFound();
                        break;
                }
                break;
            default:
                endpointNotFound();
                break;
        }
        break;
    default:
        endpointNotFound();
        break;
}
