<?php
  namespace Adeon;

  function request($method, $url, $body = null, $headers = []) {
    $request_headers = $headers;
    $response_headers = [];

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

    if (!is_null($body)) {
      $encoded_body = json_encode($body);
      $request_headers = array_merge([
        'Content-Type: application/json',
        'Content-Length: '.strlen($encoded_body)
      ], $headers);

      curl_setopt($handle, CURLOPT_POSTFIELDS, $encoded_body);
    }
    curl_setopt($handle, CURLOPT_HTTPHEADER, $request_headers);
    curl_setopt($handle, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers) {
      $len = strlen($header);
      $header = explode(':', $header, 2);
      if (count($header) < 2) {
        return $len;
      }

      $response_headers[$header[0]] = trim($header[1]);
      return $len;
    });

    $response = curl_exec($handle);
    $response_code = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);

    return [
      'data' => $response,
      'status' => $response_code,
      'headers' => $response_headers
    ];
  };
?>