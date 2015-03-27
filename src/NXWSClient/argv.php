<?php
namespace NXWSClient;

use NXWSClient\nx,
	NXWSClient\tools,
	Pimple\Container,
	Zend\Config\Writer\Ini as IniWriter,
	Zend\Config\Reader\Ini as IniReader,
	Exception;

class argv {
  public $container;

  private $expected_parameters = array(
	'config' => array(
	  'ambiente' => 'Padrão producao | Exemplo: php cli.php config ambiente sandbox',
	  'pastas' => array(
		'dados' => 'Padrão %app%/dados | Exemplo: php cli.php config pastas dados "c:\minha pasta\dados"',
		'tmp' => 'Padrão %app%/tmp | Exemplo: php cli.php config pastas tmp "c:\minha pasta\tmp"',
	  ),
	  'credenciais' => array(
		'username' => 'Exemplo: php cli.php config credenciais username "Francisco Luz"',
		'password' => 'Exemplo: php cli.php config credenciais password minhasenha',
	  ),
	  'smtp' => array(
		'username' => 'Exemplo: php cli.php config smtp username meuenderecodeemail@gmail.com',
		'password' => 'Exemplo: php cli.php config smtp password minhasenha',
	  ),
	  'notificar' => 'Exemplo: php cli.php config notificar NOME destinario@provedor.com',
	  'mostrar' => 'Exemplo: php cli.php config mostrar credenciais | Para ver as credenciais atuais.',
	),
	'sincronizar' => 'Exemplo: php cli.php sincronizar',
	'consultar' => array(
	  'produto' => array(
		'product_id' => 'Exemplo: php cli.php consultar produto product_id VALOR_DO_PRODUCT_ID',
		'sku' => 'Exemplo: php cli.php consultar produto sku VALOR_DO_SKU',
		'cod_produto_erp' => 'Exemplo: php cli.php consultar produto cod_produto_erp VALOR_DO_COD_PRODUTO_ERP',
	  ),
	  'pedido' => array(
		'no' => 'Exemplo: php cli.php consultar pedido no NUMERO_DO_PEDIDO',
	  ),
	  'cidades' => 'Exemplo: php cli.php consultar cidades',
	),
	'resetar' => array(
	  'login' => 'Exemplo: php cli.php resetar login | Gera novo token para authenticação do usuário lojista junto a NortaoX.com',
	),
	'testar' => 'Exemplo: php cli.php testar | Verifica se o Webservice está responsivo e se as pastas dados e tmp são acessíveis.',
  ),

  /**
   * @property String
   *   It's the first level key from $expected_parameters.
   */
  $command_type,

  /**
   * @property String
   *   A sequence of parameters on a single string.
   */
  $command_parameters,

  /**
   * @property Array
   * 	A sequence of arguments sent after the parameters.
   */
  $command_arguments,

  /**
   * @property Integer
   *   The number of arguments sent after the parameters.
   */
  $command_arguments_qty_sent;
  
  public function __construct($argv) {
	$this->bootstrap_container();
	$this->bootstrap($argv);
  }

  /**
   * Defines external dependencies making it easier for mocking those
   * dependencies at unit testing.
   */
  private function bootstrap_container() {
	$container = new Container();
	$container['nx'] = $container->factory(function($c) {
	  $nx =  new nx();
	  //$nx->container['config_producao_uri'] = 'loja.nortaox.local/api';
	  $nx->bootstrap();
	  return $nx;
	});

	// Ini file reader.
	$container['ini_reader'] = function($c) {
	  return new IniReader();
	};

	// Ini file writer.
	$container['ini_writer_lock'] = TRUE;
	$container['ini_writer'] = function($c) {
	  return new IniWriter();
	};

	$this->container = $container;
  }

  /**
   * Initial Validation and Loading.
   */
  private function bootstrap($argv) {
	$command_type = '';
	$command_parameters = '';
	$command_arguments = array();

	$expected_parameters = $this->expected_parameters;

	if (!isset($argv[1]) || !isset($expected_parameters[$argv[1]])) {
	  $msg = "Comando ausente. Veja instruções acima.";
	  if (isset($argv[1])) {
		$command_type = $argv[1];
		$msg = "O comando '$command_type' não foi reconhecido. Veja instruções acima.";
	  }
	  print_r($expected_parameters);
	  throw new Exception(tools::print_red($msg));
	}
	$command_type = $argv[1];
	$expected_parameters = $expected_parameters[$command_type];

	unset($argv[0]);
	unset($argv[1]);
	foreach ($argv as $argv_value) {
	  $argv_value = strtolower(trim($argv_value));

	  if (isset($expected_parameters[$argv_value])) {
		// $argv_value is a parameter.
		$command_parameters .= empty($command_parameters) ? $argv_value : ' ' . $argv_value;

		// Jump one level down.
		$expected_parameters = $expected_parameters[$argv_value];
	  }
	  else {
		// $argv_value is an argument.
		$command_arguments[] = $argv_value;
	  }
	}

	$this->command_type = $command_type;
	$this->command_parameters = $command_parameters;
	$this->command_arguments = $command_arguments;
	$this->command_arguments_qty_sent = count($command_arguments);
  }

  public function run() {
	$command_type = $this->command_type;
	$paramenters = $this->command_parameters;
	$arguments = $this->command_arguments;
	$throw_exception = FALSE;

	switch($command_type) {
	  case 'config':
		// Load config.ini.
		$root_folder = pathinfo(__DIR__);
		$root_folder = $this->root_folder = dirname($root_folder['dirname']);
		$config_file = "$root_folder/config.ini";
		$config = $this->container['ini_reader']
		  ->fromFile($config_file);

		switch($paramenters) {
		  case 'ambiente':
			$this->check_command_arguments();
			$valid_values = array('sandbox', 'producao');

			$argument = $arguments[0];
			if (!in_array($argument, $valid_values)) {
			  throw new Exception(tools::print_yellow("$argument é inválido. Os valores esperados são producao ou sandbox."));
			}
			$config['ambiente'] = $argument;
		  break;
		  case 'pastas dados':
		  case 'pastas tmp':
			$this->check_command_arguments();
			$argument = $arguments[0];
			if (!is_dir($argument)) {
			  throw new Exception(tools::print_yellow("A pasta $argument não existe."));
			}
			if (!is_writable($argument)) {
			  throw new Exception(tools::print_yellow("O usuário atual não tem permissão para gravar na pasta $argument."));
			}
			$field_name = explode('pastas ', $paramenters);
			$field_name = $field_name[1];
			$config['pastas'][$field_name] = $argument;
		  break;
		  case 'credenciais username':
		  case 'credenciais password':
			$this->check_command_arguments();
			$argument = $arguments[0];
			$field_name = explode('credenciais ', $paramenters);
			$field_name = $field_name[1];
			$config['credenciais'][$field_name] = $argument;
		  break;
		  case 'smtp username':
		  case 'smtp password':
			$this->check_command_arguments();
			$argument = $arguments[0];
			$field_name = explode('smtp ', $paramenters);
			$field_name = $field_name[1];

			if ($field_name == 'username' && !filter_var($argument, FILTER_VALIDATE_EMAIL)) {
			  throw new Exception(tools::print_red("$argument precisa ser um endereço de email válido da gmail."));
			}

			if (strpos($argument, '@gmail') === FALSE && $paramenters == 'smtp username') {
			  throw new Exception(tools::print_red("O $argument é um email válido mas NÃO é um email da gmail."));
			}

			$config['smtp'][$field_name] = $argument;
		  break;
		  case 'notificar':
			$this->check_command_arguments(2);

			if (!filter_var($arguments[1], FILTER_VALIDATE_EMAIL)) {
			  $email = $arguments[1];
			  throw new Exception(tools::print_red("$email é inválido."));
			}

			$config['notificar'][$arguments[0]]['email'] = $arguments[1];
			// Remove receiver.
			if (empty($arguments[1])) {
			  unset($config['notificar'][$arguments[0]]);
			}
		  break;
		  case 'mostrar':
			$this->check_command_arguments();
			$expected_parameters = $this->expected_parameters;
			$argument = $arguments[0];
			if (!isset($config[$argument])) {
			  throw new Exception(tools::print_yellow("'$argument' não foi reconhecido."));
			}
			if (is_array($config[$argument])) {
			  print_r($config[$argument]);
			}
			else {
			  print $config[$argument] . PHP_EOL;
			}

			// Halt the execution.
			throw new Exception();
		  break;
		  default:
			$throw_exception = TRUE;
		  break;
		}
		if (!$throw_exception) {
		  try {
			$writer = $this->container['ini_writer'];
			$writer->toFile($config_file, $config, $this->container['ini_writer_lock']);
			tools::print_green("O novo valor para %paramenters foi atualizado com sucesso.", array('%paramenters' => $paramenters));
		  }
		  catch(Exception $e) {
			tools::print_red($e->getMessage);
		  }
		}
	  break;
	  case 'sincronizar':
		$this->check_command_arguments(0);
		$nx = $this->container['nx'];
		$nx->scan_dados_folder();
	  break;
	  case 'consultar':
		switch($paramenters) {
		  case 'produto product_id':
		  case 'produto sku':
		  case 'produto cod_produto_erp':
			$this->check_command_arguments();
			$field_name = explode('produto ', $paramenters);
			$field_name = $field_name[1];
			$method_name = "get_produto_by_$field_name";

			$nx = $this->container['nx'];
			$nx->{$method_name}($arguments[0]);
		  break;
		  case 'pedido no':
			$this->check_command_arguments();
			$nx = $this->container['nx'];
			$nx->get_pedido_by_number($arguments[0]);
		  break;
		  case 'cidades':
			$this->check_command_arguments(0);
			$nx = $this->container['nx'];
			$nx->get_cities();
		  break;
		  default:
			$throw_exception = TRUE;
		  break;
		}
	  break;
	  case 'resetar':
		switch($paramenters) {
		  case 'login':
			$this->check_command_arguments(0);
			$nx = $this->container['nx'];
			$nx->bootstrap_merchant_login(TRUE);
		  break;
		  default:
			$throw_exception = TRUE;
		  break;
		}
	  break;
	  case 'testar':
		$this->check_command_arguments(0);
		$nx = $this->container['nx'];
		$nx->check();
	  break;
	}

	if ($throw_exception) {
	  $msg = "O comando '$command_type' é válido mas o(s) parâmetro(s) é(são) inválido(s) ou incompleto(s).";
	  if (!empty($paramenters)) {
		$msg = "O comando '$command_type' é válido mas o(s) parâmetro(s) '$paramenters' está(ão) incompleto(s).";
	  }
	  else {
		if (isset($arguments[0])) {
		  $paramenter = $arguments[0];
		  $msg = "O comando '$command_type' é válido mas o parametro '$paramenter' não foi reconhecido.";
		}
	  }
	  throw new Exception(tools::print_yellow($msg));
	}
  }

  /**
   * Performs validation on passed arguments.
   *
   * @param Integer $command_arguments_qty_expected
   *   Number of arguments expected after the expected parameters.
   */
  private function check_command_arguments($command_arguments_qty_expected = 1) {
	$command_type = $this->command_type;
	$command_parameters = $this->command_parameters;
	$command_arguments_qty_sent = $this->command_arguments_qty_sent;

	if ($command_arguments_qty_expected !== $command_arguments_qty_sent) {
	  throw new Exception(tools::print_yellow("'$command_type $command_parameters' requer $command_arguments_qty_expected argumento(s). Foi(ram) enviado(s) $command_arguments_qty_sent."));
	}
  }
}
