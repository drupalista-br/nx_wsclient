<?php
namespace NXWSClient;

use NXWSClient\nx;
use org\bovigo\vfs\vfsStream;
use Zend\Config\Writer\Ini;

class NXTestCase extends nx {
  public $fileSystem,
		 $config = array(
		  'ambiente' => 'producao',
		  'endpoint' => array(
			'sandbox' => 'http://loja.nortaoxsandbox.tk/api',
			'producao' => 'https://loja.nortaox.com/api',
			'dev' => 'http://loja.nortaox.local/api',
		  ),
		  'servicos' => array(
			'login' => 'user/login',
			'produto' => 'produto',
			'pedido' => 'pedido-consultar',
			'cidades' => 'cidades',
		  ),
		  'pastas' => array(
			'dados' => '%app%/dados',
			'tmp' => '%app%/tmp',
		  ),
		  'credenciais' => array(
			'username' => 'Francisco Luz',
			'password' => 'teste',
		  ),
		);

  public function __construct() {
	$this->fileSystem = array(
	  'nx_wsclient' => array(
		'config.ini' => 'empty',
	  ),
	);

	vfsStream::create($this->fileSystem);
	$config_file = vfsStream::url('root/nx_wsclient/config.ini');

	/**
	 * Known issue: https://github.com/mikey179/vfsStream/issues/44
	 * $writer = new Ini();
	 * $writer->toFile($config_file, $this->config);
	 *
	 * So, \Zend\Config\Writer\Ini wont work for know.
	 */
	$config = file_get_contents();
	file_put_contents($config_file, $config);

	$this->bootstrap_root_folder();
  }

  private function bootstrap_root_folder() {
	//$root = $this->root;
	//$this->root_folder = $root::url('root/nx_wsclient');
  }
}
