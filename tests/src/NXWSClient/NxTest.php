<?php
namespace NXWSClient\Test;

use NXWSClient\nx;
use org\bovigo\vfs\vfsStream;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends \PHPUnit_Framework_TestCase {

  function testCreateTmpAndDadosFoldersAndSubfolders() {

  }

  /**
   * @outputBuffering disabled
   */
  public function testOutput() {
	$this->expectOutputString('Ping');
	print "Ping";
  }   

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object
   *   Instantiated object that we will run method on.
   * @param string $methodName
   *   Method name to call
   * @param array $parameters
   *   Array of parameters to pass into method.
   *
   * @return mixed Method return.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = array()) {
	$reflection = new \ReflectionClass(get_class($object));
	$method = $reflection->getMethod($methodName);
	$method->setAccessible(true);

	return $method->invokeArgs($object, $parameters);
  }
}
