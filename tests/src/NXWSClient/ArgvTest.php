<?php
namespace NXWSClient\Test;

use NXWSClient\NxTestCase;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class ArgvTest extends NxTestCase {
  function testSomething() {

  }
}
