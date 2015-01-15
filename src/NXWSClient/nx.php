<?php
namespace NXWSClient;

use Httpful\Request,
    Zend\Config\Writer\Ini as IniWriter,
	Zend\Config\Reader\Ini as IniReader,
	Zend\Mail\Message,
	Zend\Mail\Transport\Smtp as SmtpTransport,
	Zend\Mail\Transport\SmtpOptions,
	Pimple\Container,
	DateTime,
	FilesystemIterator,
	Exception;

class nx {
  // Holds external dependencies.
  public $container;

  private $root_folder,
		  $config = array(),
		  $config_file,
		  $folders = array(),
		  $log_file,
		  $endpoint,
		  $merchant_login = array('session' => '', 'token' => ''),
		  $response_body_json,
		  $response_code,
		  $response_error_msg;

  /**
   * Initial Constructor.
   */
  public function __construct() {
	// External dependencies container.
	$this->bootstrap_container();
  }

  /**
   * Bootstraps the app's essential configurations.
   * 
   * @param Bool $is_dev
   *   Whether or not this is a dev machine which has a local webservices
   *   server.
   */
  public function bootstrap($is_dev = FALSE) {
	// Defines this app's root folder.
	$this->bootstrap_root_folder();
	// Loads the config.ini and do basic validation on it.
	$this->bootstrap_config($is_dev);
	// Loads the merchant session credentials and token for webservice
	// request authentication.
	$this->bootstrap_merchant_login();
  }

  /**
   * Defines external dependencies making it easier for mocking those
   * dependencies at unit testing.
   */
  private function bootstrap_container() {
	$container = new Container();

	$root_folder = pathinfo(__DIR__);
	$container['root_folder'] = dirname($root_folder['dirname']);

	$container['config_preset'] = array(
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
	);
	$container['config'] = function($c) {
	  $config = $c['config_preset'];
	  $config_file = $c['root_folder'] . "/config.ini";

	  if (!file_exists($config_file)) {
		throw new Exception("O arquivo $config_file nao existe." . PHP_EOL);
	  }

	  $config_file = $c['ini_reader']
		->fromFile($config_file);

	  $config['ambiente'] = (isset($config_file['ambiente'])) ? $config_file['ambiente'] : $config['ambiente'];

	  if (isset($config_file['pastas']['dados']) && is_dir($config_file['pastas']['dados'])) {
		$config['pastas']['dados'] = $config_file['pastas']['dados'];
	  }

	  if (isset($config_file['pastas']['tmp']) && is_dir($config_file['pastas']['tmp'])) {
		$config['pastas']['tmp'] = $config_file['pastas']['tmp'];
	  }

	  if (!isset($config_file['servidor_smtp']) ||
		  !isset($config_file['notificar']) ||
		  !isset($config_file['credenciais'])) {
		throw new Exception("Tem algo errado no arquivo de configuracao $config_file." . PHP_EOL);
	  }
	  $config['servidor_smtp'] = $config_file['servidor_smtp'];
	  $config['notificar'] = $config_file['notificar'];
	  $config['credenciais'] = $config_file['credenciais'];

	  return $config;
	};

	// Date and Time.
	$container['date_time_ymd'] = function($c) {
	  $date = new DateTime();
	  return $date->format("Y-m-d");
	};
	$container['date_time_gis'] = function($c) {
	  $date = new DateTime();
	  return $date->format("G:i:s");
	};
	$container['date_time_ymd-his'] = function($c) {
	  $date = new DateTime();
	  return $date->format("Y-m-d H:i:s");
	};

	// Http requests.
	$container['request_method'] = '';
	$container['request_uri'] = '';
	$container['request'] = function($c) {
	  // GET, POST or PUT.
	  $method = $c['request_method'];
	  // The service full URI.
	  $uri = $c['request_uri'];

	  return Request::$method($uri);
	};

	// Ini file writer.
	$container['ini_reader'] = function($c) {
	  return new IniReader();
	};

	// Ini file writer.
	$container['ini_writer'] = function($c) {
	  return new IniWriter();
	};

	// Email.
	$container['email_host'] = 'smtp.gmail.com';
	$container['email_domain'] = 'gmail.com';
	$container['email_port'] = 465;
	$container['email_message'] = function($c) {
	  $config = $c['config'];

	  $message = new Message();
	  $message->addFrom($config['servidor_smtp']['From']);

	  foreach($config['notificar'] as $recipient) {
		$message->addTo($recipient['email']);
	  }
	  return $message;
	};

	$container['email_transport'] = function($c) {
	  $config = $c['config'];

	  // Setup SMTP transport using LOGIN authentication
	  $transport = new SmtpTransport();
	  $options = new SmtpOptions(array(
		'name' => $c['email_domain'],
		'host' => $c['email_host'],
		'connection_class'  => 'login',
		'port' => $c['email_port'],
		'connection_config' => array(
		  'ssl' => 'ssl',
		  'username' => $config['servidor_smtp']['Username'],
		  'password' => $config['servidor_smtp']['Password'],
		),
	  ));
	  $transport->setOptions($options);
	  return $transport;
	};

	// Directory and file listing.
	$container['scan_folder_path'] = '';
	$container['scan_folder'] = function($c) {
	  return new FilesystemIterator($c['scan_folder_path']);
	};

	$this->container = $container;
  }

  /**
   * Defines this app root folder.
   */
  private function bootstrap_root_folder() {
	$root_folder = pathinfo(__DIR__);
	$this->root_folder = dirname($root_folder['dirname']);
  }

  function test() {
	$test = $this->container['config'];
	print_r($test);
  }
  /**
   * Load the config.ini and do basic validation on it.
   */
  private function bootstrap_config($is_dev) {
	$current_date = $this->container['date_time_ymd'];

	$this->config = $config = $this->container['config'];
	$this->folders = $config['pastas'];
	$this->set_folders();
	$this->log_file = $this->folders['tmp'] . "/logs/$current_date.log";

	if ($is_dev) {
	  $env = 'dev';
	}
	else {
	  $valid_envs = array('producao', 'sandbox');
	  $env = $config['ambiente'];
  
	  if (!in_array($env, $valid_envs)) {
		print $print = "O valor $env para ambiente eh invalido." . PHP_EOL;
		$this->log($print);
		throw new Exception($print);
	  }
	}

	$this->endpoint = $config['endpoint'][$env];

	if ($config['credenciais']['username'] == 'Francisco Luz' ||
		$config['credenciais']['password'] == 'teste') {
	  print $print = "O username ou a password do usuario lojista nao foram definidas." . PHP_EOL;
	  $this->log($print);
	  throw new Exception($print);
	}	
  }

  /**
   * Logs errors and webservices failures into a log file.
   *
   * @param String $msg
   *   Message to log.
   */
  private function log($msg) {
	if (!empty($this->log_file)) {
	  $log_file = $this->log_file;

	  $this->response_code = 500;
	  $time = $this->container['date_time_gis'];

	  $log = "----$time----" . PHP_EOL;
	  $log .= $msg;

	  $this->response_error_msg = $msg;
	  file_put_contents($log_file, $log, FILE_APPEND);
	}
  }

  /**
   * Sends out email notifications.
   *
   * @param String $msg
   *   The message to be sent.
   *
   * @param String $subject
   *   The email subject.
   */
  public function notify($msg, $subject = 'NortaoX | Cliente Webservice') {
	$message = $this->container['email_message']
	  ->setSubject($subject)
	  ->setBody($msg);

	$transport = $this->container['email_transport'];

	try {
	  $transport->send($message);
	  print "Email enviado com sucesso." . PHP_EOL;
	}
	catch(Exception $e) {
	  $msg = $e->getMessage();
	  print $print = "Algo deu errado. $msg" . PHP_EOL;
	  $this->log($print);
	}
  }

  /**
   * Sends a test request to the endpoint.
   * Checks if dados and tmp folders are reachable.
   */
  public function check($is_dev = FALSE) {
	$this->bootstrap_root_folder();
	$this->bootstrap_config($is_dev);

	$uri = $this->endpoint;

	$this->container['request_method'] = 'get';
	$this->container['request_uri'] = $uri;
	$request = $this->container['request']
	  ->send();

	$endpoint_ok = $this->response_code($request, $uri);

	if ($endpoint_ok) {
	  print "Endpoint $uri esta acessivel." . PHP_EOL;
	}

	$this->set_folders(TRUE);
  }

  /**
   * Loads folder's paths from config.ini and creates all the app's subfolders.
   */
  private function set_folders($check = FALSE) {
	foreach($this->folders as $folder => &$folder_path) {
	  // Make sure forward slash directory separator is gonna be in place.
	  $folder_path = str_replace('\\', '/', $folder_path);

	  // Set this app's root path as default for this $folder.
	  $folder_path = str_replace('%app%', $this->root_folder, $folder_path);

	  // Check if folders exist. If not, try to create them.
	  $error_msgs = FALSE;
	  if (!is_dir($folder_path) || $check) {
		$dados_subfolders = array('produto', 'consulta');
		$tmp_subfolders = array(
		  "falhas/produto",
		  "sucessos/produto",
		  "logs",
		);

		foreach(${$folder . "_subfolders"} as $subfolder_name) {
		  $full_subfolder_path = "$folder_path/$subfolder_name";
		  $mkdir = TRUE;

		  if (!file_exists($full_subfolder_path)) {
			$mkdir = mkdir($full_subfolder_path, 0777, TRUE);
		  }

		  if (!$mkdir || !is_writable($full_subfolder_path)) {
			$error_msgs[] = $full_subfolder_path;
		  }
		}
	  }

	  if ($error_msgs) {
		print "Nao foi possivel criar ou nao eh possivel gravar arquivos dentro das seguintes pastas:" . PHP_EOL;
		foreach ($error_msgs as $error_number => $msg) {
		  print $error_number + 1 . ". $msg" . PHP_EOL;
		}
		throw new Exception("--Verifique as permissoes do usuario--" . PHP_EOL);
	  }
	}
	if ($check) {
	  print "As pastas dados, tmp e suas subpastas foram criadas com sucesso." . PHP_EOL;
	}
  }

  /**
   * Authenticates the merchant user at the NortaoX.com webservice.
   *
   * @param Bool $reset
   *   Whether or not the session and the token should be renewed even when
   *   there already is one set in file.
   *
   * @return Bool
   *   Whether or not the login was successful.
   */
  private function bootstrap_merchant_login($reset = FALSE) {
	$username = $this->config['credenciais']['username'];
	$session_file = $this->folders['tmp'] . "/.session";

	if (file_exists($session_file) && !$reset) {
	  $session = $this->container['ini_reader']
		->fromFile($session_file);

	  $this->merchant_login['session'] = $session['session'];
	  $this->merchant_login['token'] = $session['token'];

	  print "Credenciais para o usuario $username foram carregadas a partir de arquivo de sessao." . PHP_EOL;
	}
	else {
	  // Request merchant authentication.
	  $endpoint = $this->endpoint;
	  $service = $this->config['servicos']['login'];
	  $password = $this->config['credenciais']['password'];

	  $uri = "$endpoint/$service";

	  try{
		$this->container['request_method'] = 'post';
		$this->container['request_uri'] = $uri;
		$request = $this->container['request']
		  ->body("username=$username&password=$password")
		  ->expectsJson()
		  ->send();
		$response_ok = $this->response_code($request, $uri);
	  }
	  catch(Exception $e) {
		$this->response_error_msg = $e->getMessage();
		$this->response_code = 500;
		$response_ok = FALSE;
	  }

	  if ($response_ok) {
		$sessid = $request->body->sessid;
		$session_name = $request->body->session_name;
  
		$session = array();
		$this->merchant_login['session'] = $session['session'] = "$session_name=$sessid";
		$this->merchant_login['token'] = $session['token'] = $request->body->token;

		$writer = $this->container['ini_writer'];
		$writer->toFile($session_file, $session);

		print "Login do usuario $username foi bem sucessido." . PHP_EOL;
		if ($reset) {
		  print "Novo token foi salvo com sucesso." . PHP_EOL;
		}
	  }
	  else {
		$http_code = $this->response_code;
		print $print = "Algo saiu errado. Codigo HTTP: $http_code" . PHP_EOL;

		$print .= $this->response_error_msg;
		$this->log($print);
	  }
	}
  }

  /**
   * Performs the POST, PUT and GET requests.
   *
   * @param String $service
   *   The service path.
   *
   * @param Array $body
   *   PHP Array of items containing the data to be later converted into Json.
   *
   * @param String $http_method
   *   Valid values are: get, post and put.
   *
   * @param String $service_method
   *   Service methodo + Query String value. Format /method.json?param1=value1&param2=value2
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  private function request($service, $body, $http_method, $service_method = '') {
	$endpoint = $this->endpoint;
	$service = $this->config['servicos'][$service];

	$uri = "$endpoint/$service" . $service_method;

	try {
	  $this->container['request_method'] = $http_method;
	  $this->container['request_uri'] = $uri;
	  $request = $this->container['request']
		->sendsJson()
		->expectsJson()
		->addHeader('Cookie', $this->merchant_login['session'])
		->addHeader('X-CSRF-Token', $this->merchant_login['token'])
		->body($body)
		->send();

	  $response_ok = $this->response_code($request, $uri);
	}
	catch (Exception $e) {
	  $this->response_error_msg = $e->getMessage();
	  $this->response_code = 500;
	  $response_ok = FALSE;
	}

	if ($response_ok) {
	  $this->response_body_json = $request->raw_body;
	}
	return $response_ok;
  }

  /**
   * Prints out an error message plus service response when the service
   * request fails.
   *
   * @param Object $response
   *   The full object response from the webservice.
   *
   * @param String $uri
   *   The service URI which the service has been requested against.
   *
   * @return Bool
   *   Whether or not  the request was successful ( code 200 ).
   *
   */
  private function response_code(\Httpful\Response $response, $uri) {
	$this->response_code = $code = $response->code;

	if ($code != 200) {
	  $body = $response->raw_body;

	  print $print = "A chamada ao Endpoint $uri FALHOU. Retornou o Codigo de Status HTTP $code." . PHP_EOL;
	  if (!empty($body)) {
		print $print = "O Webservice respondeu o seguinte:" . PHP_EOL;
		print $print = $body . PHP_EOL;
	  }
	  $this->log($print);

	  return FALSE;
	}
	return TRUE;
  }

  /**
   * Creates a new service item.
   *
   * @param String $service
   *  The service path.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  private function create($service = 'produto') {
	$body = array(
	  //'nome' => 'my new product 200',
	  'preco' => 5068,
	  'preco_velho' => 7068,
	  'qtde_em_estoque' => 88885.01,
	  'cod_cidade' => 35,
	  // Opcional.
	  'localizacao_fisica' => 'prateleira',
	  // Opcional.
	  'cod_produto_erp' => '998',
	);

	$this->request($service, $body, 'post');
  }

  /**
   * Updates service item.
   *
   * @param String $service
   *  The service path.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  private function update($service = 'produto', $service_method = 'atualizar') {
	$body = array(
	  //'nome' => 'update test 55',
	  //'product_id' => 64,
	  'sku' => '87-35-55',
	  //'preco' => 1583,
	  //'preco_velho' => 7068,
	  //'qtde_em_estoque' => 4255,
	  //'cod_cidade' => 35,
	  // Opcional.
	  //'localizacao_fisica' => 'prateleira',
	  // Opcional.
	  //'cod_produto_erp' => '1111',
	  'status' => 0,
	);

	return $this->request($service, $body, 'put', "/$service_method");
  }

  /**
   * Deletes a file. Logs message if it fails.
   *
   * @param String $file_full_path
   *   The full file path.
   */
  private function delete_file($file_full_path) {
	if (file_exists($file_full_path)) {
	  if (!unlink($file_full_path)) {
		print $print = "Nao foi possivel deletar o arquivo $file_full_path." . PHP_EOL;
		$this->log($print);
	  }
	}
  }

  /**
   * Checks dados/$subfolder_name for new files. It then reads them, calls either
   * create or update method.
   * If create or update returns TRUE ( success ) it moves the data file to
   * tmp/sucesso/$subfolder_name, otherwise adds an error tag into the file
   * before moving it to tmp/falhas/$subfolder_name.
   *
   * @param String $subfolder_name
   *   The subfolder name where there will be one or more files contaning
   *   items data for either creating or updating them to the NortaoX.com
   *   Webservice.
   *
   * @param String $prime_id_field_name
   *   The item id field name at the NortaoX.com.
   *
   * @param Array $secondary_id_field_names
   *   Field names that, apart from the prime id field name, also hold an
   *   unique identification for sorting out a single item.
   */
  public function scan_dados_folder($subfolder_name = 'produto', $prime_id_field_name = 'product_id', $secondary_id_field_names = array('sku', 'cod_produto_erp')) {
	$dados = $this->folders['dados'];
	$item_data_folder = "$dados/$subfolder_name";
	$this->container['scan_folder_path'] = $item_data_folder;

	$files = $this->container['scan_folder'];

	$result = array();
	foreach ($files as $file_object) {
	  if ($file_object->isFile()) {
		$file_name = $file_object->getFilename();
		$file_full_path = "$item_data_folder/$file_name";

		if (filesize($file_full_path) === 0) {
		  // File is empty. So, it goes straight into the fail's bin.
		  $print = 'O arquivo tah vazio.' . PHP_EOL;

		  $item_data = array();
		  $this->set_sync_attempt_tag($item_data, $print);

		  $this->scan_dados_fail($item_data, $subfolder_name, $file_name);
		  print $print;

		  $this->delete_file($file_full_path);

		  // Move on to next file.
		  break;
		}

		$item_file_data = $this->container['ini_reader']
		  ->fromFile($file_full_path);
		if ($item_file_data) {
		  $first_key = current(array_keys($item_file_data));
  
		  if (is_array($item_file_data[$first_key])) {
			// There are more than one item in the file.
			foreach ($item_file_data as $item => $item_data) {
			  $this->scan_dados_item_data($item_data, $subfolder_name, $file_name, $prime_id_field_name, $secondary_id_field_names, $result);
			}
		  }
		  else {
			// There is only one item in the file.
			$this->scan_dados_item_data($item_file_data, $subfolder_name, $file_name, $prime_id_field_name, $secondary_id_field_names, $result);
		  }
		}
		else {
		  print $print = "Nao foi possivel carregar o arquivo $file_full_path." . PHP_EOL;
		  $this->log($print);

		  $tmp = $this->folders['tmp'];
		  $move_to = "$tmp/falhas/$subfolder_name/$file_name";
		  // Move file to fail's bin.
		  if (!rename($file_full_path, $move_to)) {
			print $print = "Nao foi possivel mover o arquivo $file_full_path para $move_to." . PHP_EOL;
			$this->log($print);
		  }
		}

		// We are done with this file. By now its content should either be,
		// partially or entirely, at the fail or success bin.
		$this->delete_file($file_full_path);
	  }
	}

	if (!empty($result)) {
	  // 
	}
  }

  /**
   * Processes a single item data and decides:
   *  - Whether to call create or update methods.
   *  - Whether the item data should ultimately be dumped into either fail or
   *    success bin.
   *
   * @param Array $item_data
   *   Holds the data of a single item.
   *
   * @param String $service
   *   The service name.
   *
   * @param String $file_name
   *   The original file name.
   *
   * @param String $prime_id_field_name
   *   See scan_dados_folder().
   *
   * @param Array $secondary_id_field_names
   *   See scan_dados_folder().
   *
   */
  private function scan_dados_item_data($item_data, $service, $file_name, $prime_id_field_name, $secondary_id_field_names, &$result) {
	$create = TRUE;
	if (!isset($item_data[$prime_id_field_name])) {
	  // Try to retrive the item from the webservice using the
	  // $secondary_id_field_names.
	  foreach($secondary_id_field_names as $secondary_field_name) {
		if (isset($item_data[$secondary_field_name])) {
		  $item_id = $item_data[$secondary_field_name];

		  $method_name = "get_$service" . "_by_$secondary_field_name";
		  if (method_exists($this, $method_name)) {
			$item_exists = $this->{$method_name}($item_id, FALSE);

			if ($item_exists) {
			  $create = FALSE;

			  // This item already exists at the webservice, so lets update it.
			  $update_ok = $this->update($item_data, $service);
			  $this->set_sync_attempt_tag($item_data, $service, $create);

			  if ($update_ok) {
				$result['success'][] = array(
				  'item_data' => $item_data,
				  'file_name' => $file_name,
				);
				return TRUE;
			  }

			  $result['fail'][] = array(
				'item_data' => $item_data,
				'file_name' => $file_name,
			  );
			  return FALSE;
			}
		  }
		  else {
			print $print = "O metodo $method_name NAO existe." . PHP_EOL;
			$this->log($print);
		  }
		}
	  }
	}

	if ($create) {
	  $create_ok = $this->create($item_data, $service);
	  $this->set_sync_attempt_tag($item_data, $service, $create);

	  if ($create_ok) {
		$result['success'][] = array(
		  'item_data' => $item_data,
		  'file_name' => $file_name,
		);
		return TRUE;
	  }

	  $result['fail'][] = $item_data;
	}
  }

  /**
   * Adds a syncronization attempt tag into the item data array.
   *
   * @param Array $item_data
   *   See scan_dados_item_data().
   *
   * @param String $service
   *   The service name.
   *
   * @param Bool $create
   *   Whether the item was created or updated.
   */
  private function set_sync_attempt_tag(&$item_data, $service = FALSE, $create = null) {
	if ($service) {
	  $msg = 'atualizado';
	  if ($create) {
		$msg = 'criado';
	  }
	  $msg = "Item foi $msg com sucesso no servico $service.";
	}
	else {
	  $msg = $service;
	}

	$attempts = 1;
	if (isset($item_data['-sincronizacao-']['tentativas'])) {
	  $attempts += $item_data['-sincronizacao-']['tentativas'];
	}

	$date_time = $this->container['date_time_ymd-his'];

	$item_data['-sincronizacao-'] = array(
	  'tentativas' => $attempts,
	  'hora_ultima_tentativa' => $date_time,
	  'ultima_msg' => $msg,
	);
  }

  /**
   *
   */
  private function scan_dados_fail($file_data, $subfolder_name, $file_name) {
	$tmp = $this->folders['tmp'];
	$falhas_folder = "$tmp/falhas/$subfolder_name";
	$file_full_path = "$falhas_folder/$file_name";

	
  }

  /**
   *
   */
  private function scan_dados_success($file_data, $subfolder_name, $file_name) {
	$tmp = $this->folders['tmp'];
	$sucessos_folder = "$tmp/sucessos/$subfolder_name";
	
  }

  /**
   * Retrieves a single item from a service.
   *
   * @param Array $qs
   *   Key = Paramenter name, Value = Argument value.
   *
   * @param String $service
   *   The service path.
   *   
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function retrieve_service_item($qs = array(), $service = 'produto', $service_method = '/consultar') {
	$query_string = '';
	foreach ($qs as $param => $argument) {
	  $query_string .= "$param=$argument&";
	}

	if (!empty($query_string)) {
	  $query_string = "?$query_string";
	}

	$service_method = "$service_method.json" . $query_string;

	return $this->request($service, '', 'get', $service_method);
  }

  /**
   * Retrieves a single order based on its number.
   *
   * @param String $order_number
   *   The order identification number.
   *
   * @param Bool $save_result
   *   Whether or not the result content should be saved into
   *   dados/consulta folder.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_pedido_by_number($order_number) {
	$qs = array('no' => $order_number);
	$request = $this->retrieve_service_item($qs, 'pedido', '');

	if ($request) {
	  $this->save_retrieved_result("pedido_no_$order_number");
	}

	return $request;
  }

  /**
   * Retrieves a single product based on its product_id field.
   *
   * @param String $product_id
   *   The Product ID value set at the NortaoX application.
   *
   * @param Bool $save_result
   *   Whether or not the result content should be saved into
   *   dados/consulta folder.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_produto_by_product_id($product_id) {
	$qs = array('product_id' => $product_id);
	$request = $this->retrieve_service_item($qs);

	if ($request) {
	  $this->save_retrieved_result("produto_product_id_$product_id");
	}

	return $request;
  }

  /**
   * Retrieves a single product based on its sku field.
   *
   * @param String $sku
   *   The SKU value set at the NortaoX application.
   *
   * @param Bool $save_result
   *   Whether or not the result content should be saved into
   *   dados/consulta folder.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_produto_by_sku($sku) {
	$qs = array('sku' => $sku);
	$request = $this->retrieve_service_item($qs);

	if ($request) {
	  $this->save_retrieved_result("produto_sku_$sku");
	}

	return $request;
  }

  /**
   * Retrieves a single product based on its cod_produto_erp field.
   *
   * @param String $cod_produto_erp
   *   The product id value set at the ERP application.
   *
   * @param Bool $save_result
   *   Whether or not the result content should be saved into
   *   dados/consulta folder.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_produto_by_cod_produto_erp($cod_produto_erp) {
	$qs = array('cod_produto_erp' => $cod_produto_erp);
	$request = $this->retrieve_service_item($qs);

	if ($request) {
	  $this->save_retrieved_result("produto_cod_produto_erp_$cod_produto_erp");
	}

	return $request;
  }

  /**
   * Retrieves a list of cities which NortaoX is or will trade in.
   *
   * @param Bool $save_result
   *   Whether or not the result content should be saved into
   *   dados/consulta folder.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_cities() {
	$request = $this->request('cidades', '', 'get');

	if ($request) {
	  $this->save_retrieved_result('cidades');
	}
	else {
	  print "Algo saiu errado." . PHP_EOL;
	}
  }

  /**
   * Saves a item object into a txt file. The file content has a ini structure.
   *
   * @param String $file_name
   *   The name of the file which the retrieved content will be saved into.
   */
  private function save_retrieved_result($file_name, $file_extension = 'txt') {
	$file_full_path = $this->folders['dados'] . "/consulta/$file_name.$file_extension";

	$item = json_decode($this->response_body_json, true);

	try {
	  $writer = $this->container['ini_writer'];
	  $writer->toFile($file_full_path, (array) $item);

	  print "Consulta foi salva em $file_full_path" . PHP_EOL;
	}
	catch(Exception $e) {
	  $print = "Nao foi possivel salvar a consulta em $file_full_path." . PHP_EOL;
	  $this->log($print);
	}
  }
}
