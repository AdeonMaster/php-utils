<?php
  namespace Adeon;

  class JWT {
    private static function base64_encode_fix($str) {
      return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($str));
    }

    private static function base64_decode_fix($str) {
      return base64_decode(str_replace(['-', '_', ''], ['+', '/', '='], $str));
    }

    public function __construct($secret) {
			$this->secret = $secret;
    }

    public static function build($payload) {
      $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
      ]);
      $payload = json_encode($payload);

      $base64UrlHeader = self::base64_encode_fix($header);
      $base64UrlPayload = self::base64_encode_fix($payload);
      $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", $this->secret, true);
      $base64UrlSignature = self::base64_encode_fix($signature);
      $token = "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";

      return $token;
    }

    public static function validate($token) {
      $parts = explode('.', $token);
      $base64UrlHeader = $parts[0];
      $base64UrlPayload = $parts[1];
      $base64UrlSignature = $parts[2];
      $signature = self::base64_decode_fix($base64UrlSignature);

      $testSignature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", $this->secret, true);
      if($testSignature !== $signature) {
        return false;
      }

      $payload = json_decode(self::base64_decode_fix($base64UrlPayload), true);
      if(isset($payload['exp'])) {
        $exp = $payload['exp'];
        if($exp > 0) {
          if(time() > $exp) {
            return false;
          }
        }
      }

      return true;
    }

    public static function getPayload($token) {
      $parts = explode('.', $token);

      return json_decode(self::base64_decode_fix($parts[1]), true);
    }
  }
?>