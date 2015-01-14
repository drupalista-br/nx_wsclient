<?php
namespace NXWSClient\Test;

use NXWSClient\nx;
use NXWSClient\NxTestCase;
use Httpful\Request;
use org\bovigo\vfs\vfsStream;
use Pimple\Container;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends NxTestCase {

  public $home_folder;
  
  public function setUp() {
	parent::setUp();
	$this->home_folder = vfsStream::setup('home');
  }

  function testBootstrapConfigMethodConfigIniFileDoesNotExist() {
	$this->setExpectedException('\Exception');

	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_config');
	$this->unlock();
  }

  function testRootFolderPropertyIsSetByBootstrapRootFolderMethod() {
	// Get the value of $nx->root_folder;
	$this->unlockObj = new nx();
	$this->unlockSetProperty('root_folder');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertFalse(is_dir($this->unlockObj));

	// Run $nx->bootstrap_root_folder();
	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_root_folder');
	$this->unlock();

	// Get the new value of $nx->root_folder;
	$this->unlockSetProperty('root_folder');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertTrue(is_dir($this->unlockObj));
  }

  function testBootstrapConfigMethodLoadsTheConfigIniFile() {
	// Run $nx->bootstrap_root_folder();
	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_root_folder');
	$this->unlock();
	$nx = $this->unlockObj;

	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertTrue(empty($this->unlockObj));

	$this->unlockObj = $nx;
	// Run $nx->bootstrap_config()
	$this->unlockSetMethod('bootstrap_config');
	$this->unlock();

	// Get the value of $nx->config;
	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$config = $this->unlockObj;

	$this->assertTrue(isset($config['ambiente']));
	$this->assertTrue(is_array($config['endpoint']));
	$this->assertTrue(is_array($config['servicos']));
	$this->assertTrue(is_array($config['pastas']));
	$this->assertTrue(is_array($config['credenciais']));
  }
  
  function testBootstrapFoldersMethod() {
	$this->unlockObj = new nx();
	$this->unlockSetMethod('bootstrap_root_folder');
	$this->unlock();

	$this->unlockSetMethod('bootstrap_config');
	$this->unlock();

	// Swap $this->unlock->config['tmp'] and $this->unlock->config['dados']
	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('set');
	$config = array(
	  'pastas' => array(
		'tmp' => vfsStream::url('home/test/tmp'),
		'dados' => vfsStream::url('home/test/dados'),
	  ),
	);
	$this->unlockSetPropertyNewValue($config);
	$this->unlock();

	// Keep our partially bootstraped object for later user.
	$nx = $this->unlockObj;

	$this->unlockSetMethod('bootstrap_folders');
	$this->unlock();

	$this->unlockSetProperty('folders');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$folders = $this->unlockObj;

	$tmp = $folders['tmp'];
	$dados = $folders['dados'];

	$this->assertTrue(is_dir("$dados/produto"));
	$this->assertTrue(is_dir("$dados/consulta"));
	$this->assertTrue(is_dir("$tmp/falhas/produto"));
	$this->assertTrue(is_dir("$tmp/sucessos/produto"));
	$this->assertTrue(is_dir("$tmp/logs"));

	// At this point the bootstrap shouldn't have triggered the merchant
	// authentication process.
	$this->assertFalse(file_exists("$tmp/.session"));

	// Expect output.
	$this->unlockObj = $nx;
	$this->unlockSetMethod('bootstrap_folders');
	$this->unlockSetMethodArgs(array(TRUE));
	$this->unlock();

	$this->unlockSetProperty('folders');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->expectOutputString("As pastas dados, tmp e suas subpastas foram criadas com sucesso." . PHP_EOL);
  }

  function testFoldersPropertyValuesGetPartiallyReplacedWithTheRootFolder() {
	$this->VfsBootstrapFolders();

	$this->unlockSetProperty('folders');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$folders = $this->unlockObj;
	$tmp = $folders['tmp'];
	$dados = $folders['dados'];

	$this->assertTrue($tmp == vfsStream::url('home/tmp'));
	$this->assertTrue($dados == vfsStream::url('home/dados'));
  }

  function testVfsBootstrapEndpointShouldThrowException() {
	$this->setExpectedException('\Exception');
	$this->VfsBootstrapEndpoint(FALSE, TRUE);
  }

  function testVfsBootstrapEndpointShouldThrowExceptionForDevEnvironment() {
	$this->setExpectedException('\Exception');
	$this->VfsBootstrapEndpoint(TRUE, TRUE);
  }

  function testVfsBootstrapEndpointIsADevEndPoint() {
	$this->VfsBootstrapEndpoint();
	$nx = $this->unlockObj;

	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	$dev_endpoint = $this->unlockObj['endpoint']['dev'];

	$this->unlockObj = $nx;
	$this->unlockSetProperty('endpoint');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	$set_endpoint = $this->unlockObj;

	$this->assertTrue($dev_endpoint == $set_endpoint);
	$this->assertTrue('http://loja.nortaox.local/api' == $set_endpoint);
  }

  function testVfsBootstrapEndpointIsAProductionEndPoint() {
	$this->VfsBootstrapEndpoint(FALSE);
	$nx = $this->unlockObj;

	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	$producao_endpoint = $this->unlockObj['endpoint']['producao'];

	$this->unlockObj = $nx;
	$this->unlockSetProperty('endpoint');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	$set_endpoint = $this->unlockObj;

	$this->assertTrue($producao_endpoint == $set_endpoint);
	$this->assertTrue('https://loja.nortaox.com/api' == $set_endpoint);
  }

  function testVfsBootstrapEndpointIsASandboxEndPoint() {
	$this->VfsBootstrapFolders();
	$nx = $this->unlockObj;

	// Get current config property.
	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	$config = $this->unlockObj;

	$sandbox_endpoint = $config['endpoint']['sandbox'];

	// Change config ambiente.
	$config['ambiente'] = 'sandbox';

	// Set the changed config back into $nx.
	$this->unlockObj = $nx;
	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('set');
	$this->unlockSetPropertyNewValue($config);
	$this->unlock();

	$this->unlockSetMethod('bootstrap_log_file');
	$this->unlock();

	$this->unlockSetMethod('bootstrap_validate_config');
	$this->unlock();

	$this->unlockSetMethod('bootstrap_endpoint');
	$this->unlockSetMethodArgs(array(FALSE));
	$this->unlock();

	$this->unlockSetProperty('endpoint');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	$set_endpoint = $this->unlockObj;

	$this->assertTrue($sandbox_endpoint == $set_endpoint);
	$this->assertTrue('http://loja.nortaoxsandbox.tk/api' == $set_endpoint);
  }

  /**
   * Sets a vfsStream folder for root_folder and bootstraps upto
   * bootstrap_folders();
   */
  public function VfsBootstrapFolders() {
	$this->unlockObj = new nx();
	$this->unlockSetProperty('root_folder');
	$this->unlockSetPropertyAction('set');
	$this->unlockSetPropertyNewValue(vfsStream::url('home'));
	$this->unlock();

	$pathinfo = pathinfo(__DIR__);
	$root_folder = dirname(dirname($pathinfo['dirname']));
	copy("$root_folder/config.ini", vfsStream::url('home/config.ini'));

	$this->unlockSetMethod('bootstrap_config');
	$this->unlock();

	$this->unlockSetMethod('bootstrap_folders');
	$this->unlock();
  }

  /**
   * Sets a vfsStream folder for root_folder and bootstraps upto
   * bootstrap_endpoint();
   */
  public function VfsBootstrapEndpoint($is_dev = TRUE, $fail_endpoint = FALSE) {
	$this->VfsBootstrapFolders();

	$this->unlockSetMethod('bootstrap_log_file');
	$this->unlock();

	$this->unlockSetMethod('bootstrap_validate_config');
	$this->unlock();

	if ($fail_endpoint) {
	  $this->unlockSetProperty('config');
	  $this->unlockSetPropertyAction('set');
	  $this->unlockSetPropertyNewValue(array('ambiente' => 'invalid value'));
	  $this->unlock();
	}

	$this->unlockSetMethod('bootstrap_endpoint');
	$this->unlockSetMethodArgs(array($is_dev));
	$this->unlock();
  }
  
  /**
   * Sets a vfsStream folder for root_folder and bootstraps upto
   * bootstrap_merchant_login();
   */
  public function VfsBootstrapMerchantLogin() {
	$this->VfsBootstrapEndpoint();
	$this->unlockSetMethod('bootstrap_merchant_login');
	$this->unlock();
  }

}
