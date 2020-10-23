<?php
  namespace Adeon;

  include_once 'request.php';

  use Adeon\request;

  class GoogleRecaptcha {
    public function __construct($secret) {
		$this->secret = $secret;
    }

    function validate($token) {
      $response = request('POST', 'https://www.google.com/recaptcha/api/siteverify?secret='.$this->secret.'&response='.$token);

      return json_decode($response['data'], true);
    }
  }
?>