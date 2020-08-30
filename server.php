<?php
  namespace Adeon;

  class Response {
    public function __construct() {
      $this->headers = [];
      $this->code = 200;
    }

    function code($code) {
      http_response_code($code);
      $this->code = $code;
    }

    function header($key, $value) {
      header("$key: $value");
      $this->headers = array_merge($this->headers, ["$key" => $value]);
    }

    function json($array = []) {
      die(json_encode($array, JSON_UNESCAPED_UNICODE));
    }

    function send($text) {
      die($text);
    }
  }

  class Server {
    private function getRequestHeaders() {
      $headers = array();
      foreach($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
          continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
      }
      return $headers;
    }

    private function getRequestBody() {
      return json_decode(file_get_contents('php://input'), true);
    }

    function formatErrorMessage($code, $message, $file, $line) {
      return "$message file:$file line:$line";
    }

    private function matchRoute($route, $url) {
      if($route == '*') {
        return true;
      }

      $route = explode('/', $route);
      $url = explode('/', $url);

      array_shift($route);
      array_shift($url);

      $len1 = count($route);
      $len2 = count($url);

      if($len1 != $len2) {
        return false;
      }

      $params = [];

      for($i = 0; $i < $len1; ++$i) {
        if($route[$i] != $url[$i]) {
          $routeLen = strlen($route[$i]);
          if($routeLen != 0) {
            if($route[$i][0] != ':') {
              return false;
            } else {
              $key = substr($route[$i], 1, $routeLen-1);
              $value = $url[$i];
              $params = array_merge($params, ["$key" => $value]);
            }
          }
        }
      }

      return $params;
    }

    public function __construct() {
      $this->routes = [];
      $this->middlewares = [];

      set_error_handler(array($this, 'customErrorHandler'));
      register_shutdown_function(array($this, 'customFatalHandler'));
    }

    function customFatalHandler() {
      $message = 'shutdown';
      $code = E_CORE_ERROR;
      $file = 'unknown file';
      $line = 0;

      $error = error_get_last();

      if($error !== NULL) {
        $message = $error['message'];
        $code = $error['type'];
        $file = $error['file'];
        $line = $error['line'];

        http_response_code(500);
        die(json_encode(['error' => self::formatErrorMessage($code, $message, $file, $line)], JSON_UNESCAPED_UNICODE));
      }
    }

    function customErrorHandler($code, $message, $file, $line) {
      http_response_code(500);
      die(json_encode(['error' => self::formatErrorMessage($code, $message, $file, $line)], JSON_UNESCAPED_UNICODE));
    }

    function request(...$args) {
      $this->routes[] = count($args) === 4
        ? [
          'method' => $args[0],
          'url' => $args[1],
          'middlewares' => $args[2],
          'cb' => $args[3]
        ]
        : [
          'method' => $args[0],
          'url' => $args[1],
          'cb' => $args[2]
        ];
    }

    function get(...$args) {
      self::request('GET', ...$args);
    }

    function post(...$args) {
      self::request('POST', ...$args);
    }

    function put(...$args) {
      self::request('PUT', ...$args);
    }

    function patch(...$args) {
      self::request('PATCH', ...$args);
    }

    function delete(...$args) {
      self::request('DELETE', ...$args);
    }

    function options(...$args) {
      self::request('OPTIONS', ...$args);
    }

    function use($middleware) {
      $this->middlewares[] = $middleware;
    }

    function listen() {
      $url = parse_url($_SERVER['REQUEST_URI']);

      $request = [
        'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/',
        'path' => $url['path'],
        'query' => isset($url['query']) ? $url['query'] : '',
        'params' => [],
        'method' => $_SERVER['REQUEST_METHOD'],
        'headers' => self::getRequestHeaders(),
        'body' => self::getRequestBody()
      ];

      parse_str($request['query'], $request['params']);

      $response = new Response();

      foreach ($this->routes as &$route) {
        if($route['method'] == $request['method']) {
          $params = self::matchRoute($route['url'], $request['path']);
          if($params !== false) {
            $request['params'] = array_merge($params === true ? [] : $params, $request['params']);

            if (!array_key_exists('middlewares', $route)) {
              return $route['cb']($request, $response);
            }

            // run middlewares
            $middlewares = array_merge($this->middlewares, $route['middlewares']);
            $totalMiddlewaresCount = count($middlewares);
            $currentMiddlewareIndex = 0;

            $nextMiddleware = function() use(&$middlewares, &$totalMiddlewaresCount, &$currentMiddlewareIndex, &$nextMiddleware, &$route, &$request, &$response) {
              if ($currentMiddlewareIndex !== $totalMiddlewaresCount) {
                $middlewares[$currentMiddlewareIndex++]($request, $response, $nextMiddleware);
              } else {
                $route['cb']($request, $response);
              }
            };

            return $nextMiddleware();
          }
        }
      }

      if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        return http_response_code(204);
      }

      return http_response_code(404);
    }
  }
?>