<?php

namespace App\Core;

/**
 * Classe Router
 * G�re le routage de l'application
 */
class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Ajouter une route GET
     */
    public function get(string $path, string $controller, string $method): void
    {
        $this->addRoute('GET', $path, $controller, $method);
    }

    /**
     * Ajouter une route POST
     */
    public function post(string $path, string $controller, string $method): void
    {
        $this->addRoute('POST', $path, $controller, $method);
    }

    /**
     * Ajouter une route (interne)
     */
    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void
    {
        $this->routes[] = [
            'http_method' => $httpMethod,
            'path' => $path,
            'controller' => $controller,
            'method' => $method
        ];
    }

    /**
     * Dispatcher la requ�te vers le bon controller
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Retirer le basePath de l'URI
        if ($this->basePath && strpos($requestUri, $this->basePath) === 0) {
            $requestUri = substr($requestUri, strlen($this->basePath));
        }

        $requestUri = '/' . trim($requestUri, '/');
        if ($requestUri !== '/') {
            $requestUri = rtrim($requestUri, '/');
        }

        foreach ($this->routes as $route) {
            // Vérifier la méthode HTTP
            if ($route['http_method'] !== $requestMethod) {
                continue;
            }

            // Convertir le pattern de route en regex
            $pattern = $this->convertRouteToRegex($route['path']);

            if (preg_match($pattern, $requestUri, $matches)) {
                // Retirer le premier élément (match complet)
                array_shift($matches);

                // Instancier le controller et appeler la m�thode
                $controllerName = $route['controller'];
                $methodName = $route['method'];

                if (!class_exists($controllerName)) {
                    $this->error404("Controller $controllerName not found");
                    return;
                }

                $controller = new $controllerName();

                if (!method_exists($controller, $methodName)) {
                    $this->error404("Method $methodName not found in $controllerName");
                    return;
                }

                // Appeler la m�thode avec les param�tres extraits de l'URL
                call_user_func_array([$controller, $methodName], $matches);
                return;
            }
        }

        // Aucune route trouv�e
        $this->error404();
    }

    /**
     * Convertir un pattern de route en regex
     * Ex: /project/{id} devient /^\/project\/([^\/]+)$/
     */
    private function convertRouteToRegex(string $route): string
    {
        // �chapper les slashes
        $route = str_replace('/', '\/', $route);

        // Remplacer {param} par un groupe de capture
        $route = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^\/]+)', $route);

        return '/^' . $route . '$/';
    }

    /**
     * Afficher une erreur 404
     */
    private function error404(string $message = 'Page not found'): void
    {
        http_response_code(404);

        $controllerName = 'App\\Controllers\\ErrorController';
        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            $controller->notFound();
        } else {
            echo "<h1>404 - $message</h1>";
        }
        exit;
    }

    /**
     * G�n�rer une URL � partir d'un nom de route
     */
    public function url(string $path): string
    {
        return $this->basePath . $path;
    }
}
