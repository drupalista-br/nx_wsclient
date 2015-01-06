<?php
namespace NXWSClient;

use \Zend\Config\Writer\Ini;
use \NXWSClient\nx;

class argv {
  private $expected_arguments = array(
	'help' => 'Exemplo: php run.php help config',
	'config' => array(
	  'ambiente' => 'Padrão producao | Exemplo: php run.php config ambiente sandbox',
	  'pastas' => array(
		'dados' => 'Padrão %app%/dados | Exemplo: php run.php config pastas dados "c:\minha pasta\dados"',
		'tmp' => 'Padrão %app%/tmp | Exemplo: php run.php config pastas tmp "c:\minha pasta\tmp"',
	  ),
	  'credenciais' => array(
		'username' => 'Exemplo: php run.php config credenciais username "Francisco Luz"',
		'password' => 'Exemplo: php run.php config credenciais password minhasenha',
	  ),
	  'mostrar' => 'Exemplo: php run.php config mostrar credenciais | Para ver as credenciais atuais.',
	),
	'scaniar' => array(
	  'produto' => 'Exemplo: php run.php scaniar produto',
	),
	'consultar' => array(
	  'produto' => array(
		'product_id' => 'Exemplo: php run.php consultar produto product_id VALOR_DO_PRODUCT_ID DESTINO FORMATO_DE_DADOS',
		'sku' => 'Exemplo: php run.php consultar produto sku VALOR_DO_SKU DESTINO FORMATO_DE_DADOS',
		'cod_produto_erp' => 'Exemplo: php run.php consultar produto cod_produto_erp VALOR_DO_COD_PRODUTO_ERP DESTINO FORMATO_DE_DADOS',
	  ),
	  'pedido' => array(
		'no' => 'Exemplo: php run.php consultar pedido no NUMERO_DO_PEDIDO DESTINO FORMATO_DE_DADOS',
		'entre' => 'Exemplo: php run.php consultar pedido entre TIMESTAMP_INICIO TIMESTAMP_FIM DESTINO FORMATO_DE_DADOS',
	  ),
	  'cidades' => 'Exemplo: php run.php consultar cidades DESTINO',
	),
	'testar' => 'Exemplo: php run.php testar | Verifica se o Webservice está acessível.',
	// Aliases.
	'a' => array(
	  // Create/Update products.
	  'p' => 'Exemplo: php run.php a p | Executará: php run.php scaniar produto',

	  // Retrieve a product by product_id.
	  'cpit' => 'Exemplo: php run.php a cpit VALOR_DO_PRODUCT_ID | Executará: php run.php consultar produto product_id VALOR_DO_PRODUCT_ID tela',
	  'cpiat' => 'Exemplo: php run.php a cpiat VALOR_DO_PRODUCT_ID | Executará: php run.php consultar produto product_id VALOR_DO_PRODUCT_ID arquivo txt',
	  'cpiaj' => 'Exemplo: php run.php a cpiaj VALOR_DO_PRODUCT_ID | Executará: php run.php consultar produto product_id VALOR_DO_PRODUCT_ID arquivo json',
	  // Retrieve a product by SKU.
	  'cpst' => 'Exemplo: php run.php a cpst VALOR_DO_SKU | Executará: php run.php consultar produto sku VALOR_DO_SKU tela',
	  'cpsat' => 'Exemplo: php run.php a cpsat VALOR_DO_SKU | Executará: php run.php consultar produto sku VALOR_DO_SKU arquivo txt',
	  'cpsaj' => 'Exemplo: php run.php a cpsaj VALOR_DO_SKU | Executará: php run.php consultar produto sku VALOR_DO_SKU arquivo json',
	  // Retrieve a product by cod_produto_erp.
	  'cpct' => 'Exemplo: php run.php a cpst VALOR_DO_COD_PRODUTO_ERP | Executará: php run.php consultar produto cod_produto_erp VALOR_DO_COD_PRODUTO_ERP tela',
	  'cpcat' => 'Exemplo: php run.php a cpsat VALOR_DO_COD_PRODUTO_ERP | Executará: php run.php consultar produto cod_produto_erp VALOR_DO_COD_PRODUTO_ERP arquivo txt',
	  'cpcaj' => 'Exemplo: php run.php a cpsaj VALOR_DO_COD_PRODUTO_ERP | Executará: php run.php consultar produto cod_produto_erp VALOR_DO_COD_PRODUTO_ERP arquivo json',

	),
  ),
  $is_dev,
  // nx object.
  $nx,
  /**
   * Command Instruction.
   */
  $command_type,
  $command_string,
  $command_array,
  /**
   * Command Parameters.
   */
  $command_params,
  // Number of parameters sent after the command instructions.
  $command_params_qty_sent;
  
  // Output content formats.
  const JSON = 'json';
  const TXT = 'txt';
  // Output destinations.
  const ONSCREEN = 'tela';
  const FILE = 'arquivo';

  public function __construct($is_dev = FALSE) {
	$command_type = 'nx';
	$command_string = '';
	$command_array = array();
	$command_params = array();
	$command_last = FALSE;

	$validation = $this->expected_arguments;
	
	foreach ($argv as $delta => $argument_value) {
	  if ($delta !== 0) {
		$argument_value = strtolower(trim($argument_value));
	
		if (isset($validation[$argument_value])) {
		  if ($delta === 1) {
			switch($argument_value) {
			  case 'help':
			  case 'config':
			  case 'consultar':
			  case 'a':
				$command_type = $argument_value;
			  break;
			}
		  }
		  
		  if (is_array($validation[$argument_value])) {
			if ($delta !== 1) {
			  $command_array[] = $argument_value;
			}
			$command_string .= (empty($command_string)) ? $argument_value : ' ' . $argument_value;
	
			// Jump one level down.
			$validation = $validation[$argument_value];
		  }
		  else {
			// This is the last element on the validation array.
			$command_string .= (empty($command_string)) ? $argument_value : ' ' . $argument_value;
			$command_last = $argument_value;
		  }
		}
		else {
		  // This value is not a command instruction but a parameter value.
		  $command_params[] = $argument_value;
		}
	  }
	}

	if (!$command_last) {
	  print "INSTRUCAO IMCOMPLETA. INSTRUCOES ESPERADAS SAO:" . PHP_EOL;
	  print_r($validation);
	  exit();
	}
	$this->is_dev = $is_dev;

	$this->command_type = $command_type;
	$this->command_array = $command_array;
	$this->command_string = $command_string;

	$this->command_params = $command_params;
	$this->command_params_qty_sent = count($command_params);
  }

  public function run() {
	$command_type = $this->command_type;
	$command_array = $this->command_array;

	switch($command_type) {
	  case 'a':
		// Aliases.
		$this->run_a();
	  break;
	  case 'help':
		$this->run_help();
	  break;
	  case 'config':
		$this->run_config();
	  break;
	  case 'nx':
	  case 'consultar':
		$this->nx = new nx($this->is_dev);
		$this->run_nx();
	  break;
	}
  }

  /**
   * Converts command aliases into full command instructions.
   */
  private function run_a() {
	$this->run_help();
	$this->run_config();
	$this->run_nx();
  }

  /**
   * Shows command line help.
   */
  private function run_help() {
	// Validation.
	$this->check_command_params();
	$param_value_sent = $this->command_params[0];

	$help = $this->expected_arguments;

	print "COMANDO(S) PARA $param_value_sent EH/SAO:";
	print_r($help[$param_value_sent]);
	exit(PHP_EOL);
  }

  /**
   * Reads and Updates config.ini.
   */
  private function run_config() {
	// Validation.
	$this->check_command_params();
	// Set param value.
	$param_value_sent = $this->command_params[0];

	if ($command_array[0] == 'pastas') {
	  // Check if folder exists. If not, try to create it.
	  if (!is_dir($param_value_sent)) {
		$mkdir = mkdir($param_value_sent, 0777, TRUE);
		if (!$mkdir) {
		  exit("O caminho $param_value_sent nao existe e nao foi possivel cria-lo. Verifique se o usuario rodando esta aplicacao tem permissao para criar pastas." . PHP_EOL);
		}
	  }
	}

	// Load config.ini.
	$root_folder = pathinfo(__DIR__);
	$root_folder = $this->root_folder = dirname($root_folder['dirname']);
	$config_file = $root_folder . DIRECTORY_SEPARATOR . "config.ini";
	$config = parse_ini_file($config_file, TRUE);

	if ($param_value_sent == 'mostrar') {
	  print "O(S) VALOR(ES) PARA $param_value_sent EH/SAO:";
	  print_r($config[$param_value_sent]);
	  exit(PHP_EOL);
	}
	else {
	  // Modify config.
	  // The value of $depth_pointer will end up being something
	  // like: [credenciais][username]
	  $depth_pointer = '';
	  foreach ($command_array as $param) {
		$depth_pointer .= "['$param']";
	  }
	  eval("\$config" . $depth_pointer . " = \$param_value_sent;");

	  // Write back into config.ini.
	  $writer = new Ini();
	  $writer->toFile($config_file, $config);
	  exit("Nova configuracao salva com sucesso." . PHP_EOL);
	}
  }

  /**
   * Creates, Updates and Retrieves service's items.
   */
  private function run_nx() {
	switch($this->command_string) {
	  case 'scaniar produto':
		$this->check_command_params(0);

	  break;
	  case 'consultar produto product_id':
		$this->check_command_params(1);

	  break;
	  case 'consultar produto sku':
		$this->check_command_params(1);

	  break;
	  case 'consultar produto cod_produto_erp':
		$this->check_command_params(1);

	  break;
	  case 'consultar pedido no':
		$this->check_command_params(1);

	  break;
	  case 'consultar pedido entre':
		$this->check_command_params(2);

	  break;
	  case 'consultar cidades':
		$location = $this->check_command_params(0);
		$content = $this->nx->get_cities();
		$this->output($content, $location);
	  break;
	  case 'testar':
		$this->check_command_params(0);
		$this->nx->check_endpoint();
	  break;
	}
  }

  /**
   * Performs validation on passed paramenters.
   *
   * @param Integer $command_params_qty_expected
   *   Number of parameter values the user is expected to give.
   */
  private function check_command_params($command_params_qty_expected = 1) {
	$command_type = $this->command_type;
	$command_params = $this->command_params;
	$command_params_qty_sent = $this->command_params_qty_sent;

  }

  /**
   * Outputs the content resulted from a service retrieve.
   *
   * @param String $content
   *   The service retrieve result.
   *
   * @param String $location
   *   Possible values are either 'tela' for printing out the content onscreen
   *   or 'arquivo' for saving the content into .../dados/consulta/filename.ext
   */
  private function output($content, $location, $format = self::TXT) {

  }
}
