<?php

namespace App\Controllers;

class ErrorController
{
    public function notFound(): void
    {
        http_response_code(404);
        require_once __DIR__ . '/../Views/404.phtml';
    }
}
