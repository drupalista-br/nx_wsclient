<?php
namespace NXWSClient\Test;

use NXWSClient\nx,
	NXWSClient\NxTestCase,
	Httpful\Request,
	org\bovigo\vfs\vfsStream,
	Pimple\Container,
	Zend\Config\Reader\Ini as IniReader;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends NxTestCase {
  public $root_folder;
  
  public function setUp() {
	parent::setUp();

	$pathinfo = pathinfo(__DIR__);
	$root_folder = dirname(dirname($pathinfo['dirname']));
	$this->root_folder = $root_folder;

	vfsStream::setup('home');
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

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigAmbienteIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['ambiente'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigEndpointForSandboxIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['endpoint']['sandbox'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigEndpointForProducaoIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['endpoint']['producao'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigEndpointForDevIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['endpoint']['dev'] = '';
	$this->bootstrap_validade_config($config);
  }
 
  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigServicosForLoginIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['servicos']['login'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigServicosForProdutoIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['servicos']['produto'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigServicosForPedidoIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['servicos']['pedido'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigServicosForCidadesIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['servicos']['cidades'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigCredenciaisForUsernameIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['credenciais']['username'] = '';
	$this->bootstrap_validade_config($config);
  }

  function testBootstrapValidateConfigShouldThrowExceptionWhenConfigCredenciaisForPasswordIsEmpty() {
	$this->setExpectedException('\Exception');

	$config['credenciais']['password'] = '';
	$this->bootstrap_validade_config($config);
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

  function testLogMethod() {
	$output = "Endpoint http://loja.nortaox.local/api esta acessivel." . PHP_EOL;
	$output .= "As pastas dados, tmp e suas subpastas foram criadas com sucesso." . PHP_EOL;
	$this->expectOutPutString($output);

	$root_folder = $this->root_folder;

	$date = new \DateTime();
	$date_gis = $date->format('G:i:s');
	$date_ymd = "logtest";
	$log_file = "$root_folder/tmp/logs/$date_ymd.log";

	$this->assertFalse(file_exists($log_file));

	$nx = new nx();	
	$nx->container['date_gis'] = $date_gis;
	$nx->container['date_time_gis'] = function($c) {
	  return $c['date_gis'];
	};

	$nx->container['date_ymd'] = $date_ymd;
	$nx->container['date_time_ymd'] = function($c) {
	  return $c['date_ymd'];
	};
	$nx->check(TRUE);

	$msg_1 = "1. PHPUnit." . PHP_EOL;
	$msg_2 = "2. PHPUnit." . PHP_EOL;

	$this->unlockObj = $nx;
	$this->unlockSetMethod('log');
	$this->unlockSetMethodArgs(array($msg_1));
	$this->unlock();

	$this->unlockObj = $nx;
	$this->unlockSetMethod('log');
	$this->unlockSetMethodArgs(array($msg_2));
	$this->unlock();

	$file_content_prediction = "----$date_gis----" . PHP_EOL;
	$file_content_prediction .= $msg_1;
	$file_content_prediction .= "----$date_gis----" . PHP_EOL;
	$file_content_prediction .= $msg_2;

	$file_content = file_get_contents($log_file);

	$this->assertTrue(file_exists($log_file));
	unlink($log_file);
  }

  function testBootstrapMerchantLoginRequestNewCredentialsToTheWebservice() {
	$root_folder = $this->root_folder;
	$session_file = "$root_folder/tmp/.session";

	$nx = new nx();

	if (file_exists($session_file)) {
	  $this->expectOutPutString("Login do usuario Francisco Luz foi bem sucessido." . PHP_EOL);
	  $session = $nx->container['ini_reader']
		->fromFile($session_file);

	  unlink($session_file);
	}
	else {
	  $output = "Login do usuario Francisco Luz foi bem sucessido." . PHP_EOL;
	  $output .= "Login do usuario Francisco Luz foi bem sucessido." . PHP_EOL;
	  $this->expectOutPutString($output);

	  $nx->bootstrap(TRUE);
	  $session = $nx->container['ini_reader']
		->fromFile($session_file);

	  unlink($session_file);
	}

	$nx = new nx();
	$login = $nx->bootstrap(TRUE);

	$this->unlockObj = $nx;
	$this->unlockSetProperty('merchant_login');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$session_new = $this->unlockObj;

	$this->assertTrue($session['session'] != $session_new['session']);
	$this->assertTrue($session['token'] != $session_new['token']);
  }

  function testBootstrapMerchantLoginReadCredentialsFromSessionFile() {
	$root_folder = $this->root_folder;
	$session_file = "$root_folder/tmp/.session";

	$nx = new nx();

	if (file_exists($session_file)) {
	  $session = $nx->container['ini_reader']
		->fromFile($session_file);
	  $this->expectOutPutString("Credenciais para o usuario Francisco Luz foram carregadas a partir de arquivo de sessao." . PHP_EOL);
	}
	else {
	  $nx->bootstrap(TRUE);
	  $session = $nx->container['ini_reader']
		->fromFile($session_file);

	  $output = "Credenciais para o usuario Francisco Luz foram carregadas a partir de arquivo de sessao." . PHP_EOL;
	  $output .= "Login do usuario Francisco Luz foi bem sucessido." . PHP_EOL;
	  $this->expectOutPutString($output);
	}

	$nx = new nx();
	$login = $nx->bootstrap(TRUE);

	$this->unlockObj = $nx;
	$this->unlockSetProperty('merchant_login');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$session_new = $this->unlockObj;

	$this->assertTrue($session['session'] == $session_new['session']);
	$this->assertTrue($session['token'] == $session_new['token']);
  }

  function testGetCitiesMethod() {
	$root_folder = $this->root_folder;

	if (file_exists("$root_folder/dados/consulta/cidades.txt")) {
	  unlink("$root_folder/dados/consulta/cidades.txt");
	}

	$this->assertFalse(file_exists("$root_folder/dados/consulta/cidades.txt"));

	$output = "Credenciais para o usuario Francisco Luz foram carregadas a partir de arquivo de sessao." . PHP_EOL .
			  "Consulta foi salva em $root_folder/dados/consulta/cidades.txt" . PHP_EOL;
	$this->expectOutPutString($output);
	
	$nx = new nx();
	$nx->bootstrap(TRUE);
	$nx->get_cities();

	$this->assertTrue(file_exists("$root_folder/dados/consulta/cidades.txt"));

	$reader = new IniReader();
	$cities = $reader->fromFile("$root_folder/dados/consulta/cidades.txt");

	$this->assertTrue(isset($cities[0]['cod_cidade']));
	$this->assertTrue(isset($cities[0]['nome']));
	$this->assertTrue(isset($cities[0]['status']));
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

	$root_folder = $this->root_folder;
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
   * Runs $nx->bootstrap_validade_config()
   */
  public function bootstrap_validade_config($config_value = array()) {
	$this->VfsBootstrapFolders();
	$nx = $this->unlockObj;

	// Get current config property.
	$this->unlockSetProperty('config');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	$config = $this->unlockObj;

	foreach ($config_value as $key => $value) {
	  $config[$key] = $value;  
	}

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
  }
}
