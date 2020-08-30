A pack of PHP utilities

## Request
Basic function to make simple requests

### Params
Param | Description
------------ | -------------
method | Method name (Example: GET, POST)
url | Url
body | Body array to be sent (null by default)
headers | Array of headers to be sent (empty array by default)

### Usage example
```php
  use Adeon\request;

  $response = request('POST', 'https://example-url.com', ['id' => 'example-id']);

  print_r($response);
  /*
    Array (
      'data' => ['message' => 'ok'],
      'status' => 200,
      'headers' => [
        'Content-Type: application/json'
        'Content-Length: 19'
      ]
    )
  */
```

## GoogleRecaptcha
Google Recaptcha v3 class

### Usage example
```php
use Adeon\GoogleRecaptcha;

define('GOOGLE_RECAPTCHA_SECRET', 'your-secret-key');

$google_recaptcha = new GoogleRecaptcha(GOOGLE_RECAPTCHA_SECRET);
$result = $google_recaptcha->validate('your-token');

print_r($result);
/*
  Array (
    'success' => true,
    'error-codes' => []
  )
*/
```

## JWT
JWT Token class

### Usage example
```php
use Adeon\JWT;

define('JWT_SECRET', 'your-jwt-secret-key');

$jwt = new JWT(JWT_SECRET);

$token = JWT::build(['id' => 'example-id']);

if (JWT::validate($token)) {
  echo 'Valid jwt token!';
}

$payload = JWT::getPayload($token);

print_r($payload);

/*
  Array (
    id => 'example-id'
  )
*/

```

## MYSQLHandle
Custom mysql handle class

### Usage example
Coming soon

## Server
Express.js like php server class

### Features
- Routing
- Middlewares

### Usage example
```php
use Adeon\Server;

$app = new Server();
$app->get('/item', function($request, $response) {
  $response->header('Content-type', 'application/json');

  return $response->json([]);
});

$app->post('/item', function($request, $response) {
  $response->header('Content-type', 'application/json');

  $body = $request['body'];

  if (!array_key_exists('name')) {
    $response->code(400);
    return $response->json(['error' => 'Name is not set']);
  }

  // do something with data

  return $response->json([]);
});

$app->listen();

```
