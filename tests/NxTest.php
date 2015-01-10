<?php
use \NXWSClient\nx;
use \org\bovigo\vfs\vfsStream;

$pathinfo = pathinfo(__DIR__);
$up_folder = $pathinfo['dirname'];
$current_folder = $pathinfo['dirname'] . "/" . $pathinfo['basename'];

require_once "$up_folder/vendor/autoload.php";
require_once "$current_folder/nx_test.php";

class NxTest extends PHPUnit_Framework_TestCase {
  public $fileSystemRoot;

  public function setUp() {
	$this->fileSystemRoot = vfsStream::setup('root');
  }

  function testCreateTmpAndDadosFoldersAndSubfolders() {

	$this->assertFalse($this->fileSystemRoot->hasChild('tmp'));
	$this->assertFalse($this->fileSystemRoot->hasChild('dados'));

	$nx = new nx_test();


	
	//$example->setDirectory(vfsStream::url('exampleDir'));
	//$this->assertTrue($this->root->hasChild('id'));
  }

  /**
   * @outputBuffering disabled
   */
  public function testOutput() {
	$this->expectOutputString('Ping');
	print "Ping";
  }   
  
}
