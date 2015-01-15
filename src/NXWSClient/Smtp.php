<?php
namespace NXWSClient;

use Zend\Mail\Transport\Smtp as SmtpTransport;

class Smtp extends SmtpTransport {
  public function handshake() {
	$this->connect();
  }
}
