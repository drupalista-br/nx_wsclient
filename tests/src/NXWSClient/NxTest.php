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
  
}
