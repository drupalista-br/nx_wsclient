<?php
namespace NXWSClient;

use NXWSClient\tools,
	Httpful\Request,
    Zend\Config\Writer\Ini as IniWriter,
	Zend\Config\Reader\Ini as IniReader,
	Zend\Mail\Message,
	NXWSClient\Smtp as SmtpTransport,
	Zend\Mail\Transport\SmtpOptions,
	Pimple\Container,
	DateTime,
	FilesystemIterator,
	Exception;

class nx {
  public $root_folder,
		 $container,
		 $internet_connection;

  private $config = array(),
		  // List of subfolders in dados folder. Each subfolder alone
		  // represents a single service at the webservices.
		  // These subfolders will contain file data for synchronization.
		  $sync_services = array('produto'),
		  $config_file,
		  $folders = array(),
		  $log_file,
		  $endpoint,
		  $merchant_login = array('session' => '', 'token' => ''),
		  $response_body_json,
		  $response_code,
		  $response_error_msg;

  /**
   * There is NO internet connection whatsoever.
   */
  const INTERNET_CONNECTION_DOWN = -1;
  /**
   * There is internet connection and NortaoX.com is responsive.
   */
  const INTERNET_CONNECTION_OK = 1;
  /**
   * There is internet connection but NortaoX.com is not responsive.
   */
  const INTERNET_CONNECTION_UP_NORTAOX_DOWN = 2;

  const SYNC_TAG_ACTION_CREATE = -1;
  const SYNC_TAG_ACTION_UPDATE = 1;
  const SYNC_TAG_ACTION_FAIL = 2;
  const SYNC_TAG_ACTION_ITEM_DATA_EMPTY = 3;

  const MOVE_ITEM_DATA_ACTION_FAIL = 'falhas';
  const MOVE_ITEM_DATA_ACTION_SUCCESS = 'sucessos';

  /**
   * Initial Constructor.
   */
  public function __construct() {
	$root_folder = pathinfo(__DIR__);
	$this->root_folder = dirname($root_folder['dirname']);

	$this->bootstrap_container();
  }

  /**
   * Bootstraps the app's essential configurations.
   */
  public function bootstrap() {
	$this->bootstrap_internet_connection();
	// Loads the config.ini and do basic validation on it.
	$this->bootstrap_config();
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

	// Date and Time.
	$container['date_format'] = "Y-m-d";
	$container['date_time'] = function($c) {
	  $date = new DateTime();
	  return $date->format($c['date_format']);
	};

	// Http requests.
	$container['request_method'] = '';
	$container['request_uri'] = '';
	$container['request'] = $container->factory(function ($c) {
	  // GET, POST or PUT.
	  $method = $c['request_method'];
	  // The service full URI.
	  $uri = $c['request_uri'];

	  return Request::$method($uri);
	});

	// Ini file writer.
	$container['ini_reader'] = function($c) {
	  return new IniReader();
	};

	// Ini file writer.
	$container['ini_writer_lock'] = TRUE;
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
	  $message->addFrom($config['servidor_smtp']['username']);

	  foreach($config['notificar'] as $recipient) {
		$message->addTo($recipient['email']);
	  }
	  return $message;
	};

	$container['email_options'] = function($c) {
	  $config = $c['config'];
	  $options = new SmtpOptions(array(
		'name' => $c['email_domain'],
		'host' => $c['email_host'],
		'connection_class'  => 'login',
		'port' => $c['email_port'],
		'connection_config' => array(
		  'ssl' => 'ssl',
		  'username' => $config['servidor_smtp']['username'],
		  'password' => $config['servidor_smtp']['password'],
		),
	  ));
	  return $options;
	};

	$container['email_transport'] = function($c) {
	  // Setup SMTP transport using LOGIN authentication.
	  $transport = new SmtpTransport();
	  $options = $c['email_options'];

	  $transport->setOptions($options);
	  return $transport;
	};

	// Directory and file listing.
	$container['scan_folder_path'] = '';
	$container['scan_folder'] = function($c) {
	  return new FilesystemIterator($c['scan_folder_path']);
	};
	
    $container['internet_connection_google'] = 'www.google.com';
	$container['internet_connection_nortaox'] = 'loja.nortaox.com';

	$container['config_producao_uri'] = 'https://loja.nortaox.com/api';
	$container['config_sandbox_uri'] = 'http://loja.nortaoxsandbox.tk/api';
	$container['config_file_location'] = $this->root_folder . "/config.ini";
	$container['config'] = function($c) {
	  return $this->set_config($c);;
	};

	$this->container = $container;
  }

  /**
   * Loads the settings.
   *
   * @param Array $c
   *   The current container.
   *
   * @return Array
   *   The configuration array.
   */
  private function set_config($c) {
	$config = array(
	  'ambiente' => 'producao',
	  'endpoint' => array(
		'sandbox' => $c['config_sandbox_uri'],
		'producao' => $c['config_producao_uri'],
	  ),
	  'servicos' => array(
		'login' => array(
		  'url' => 'user/login',
		),
		'produto' => array(
		  'url' => 'produto',
		  'prime_id_field' => 'product_id',
		  'secondary_id_fields' => array('sku', 'cod_produto_erp'),
		),
		'pedido' => array(
		  'url' => 'pedido-consultar',
		),
		'cidades' => array(
		  'url' => 'cidades',
		),
	  ),
	  'pastas' => array(
		'dados' => '%app%/dados',
		'tmp' => '%app%/tmp',
	  ),
	);

	$config_file = $c['config_file_location'];

	if (!file_exists($config_file)) {
	  throw new Exception(tools::print_red("O arquivo $config_file nao existe."));
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
	  throw new Exception(tools::print_red("Tem algo errado no arquivo de configuracao $config_file."));
	}
	$config['servidor_smtp'] = $config_file['servidor_smtp'];
	$config['notificar'] = $config_file['notificar'];
	$config['credenciais'] = $config_file['credenciais'];

	return $config;
  }

  /**
   * Load the config.ini and do basic validation on it.
   */
  private function bootstrap_config() {
	$current_date = $this->container['date_time'];

	$this->config = $config = $this->container['config'];
	$this->folders = $config['pastas'];
	$this->set_folders();
	$this->log_file = $this->folders['tmp'] . "/logs/$current_date.log";


	$valid_envs = array('producao', 'sandbox');
	$env = $config['ambiente'];

	if (!in_array($env, $valid_envs)) {
	  $print = "O valor '$env' para ambiente eh invalido.";
	  $this->log($print);
	  throw new Exception(tools::print_red($print));
	}

	$this->endpoint = $config['endpoint'][$env];
  }

  /**
   * Logs errors and webservices failures into a log file.
   *
   * @param String $msg
   *   Message to log.
   *
   * @param String $color
   *   If sent, it will print out the message on terminal console
   *   of the color declared.
   */
  private function log($msg, $color = '') {
	if (!empty($color)) {
	  $method_name = "print_$color";
	  tools::$method_name($msg);
	}

	if (!empty($this->log_file)) {
	  $log_file = $this->log_file;

	  $this->container['date_format'] = "G:i:s";
	  $time = $this->container['date_time'];

	  $log = "----$time----" . PHP_EOL;
	  $log .= $msg . PHP_EOL;

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
  private function notify($msg, $subject = 'NortaoX | Cliente Webservice') {
	$message = $this->container['email_message']
	  ->setSubject($subject)
	  ->setBody($msg);

	$transport = $this->container['email_transport'];

	try {
	  $transport->send($message);
	  tools::print_yellow("Foi enviado uma notificacao por Email ao administrador do sistema.");
	}
	catch(Exception $e) {
	  $msg = $e->getMessage();
	  $this->log("Algo deu errado. $msg", 'red');
	}
  }

  /**
   * Sends a test request to the endpoint.
   * Checks if dados and tmp folders are reachable.
   * Checks the internet connection status.
   * Tries a handshake with Google's SMTP server.
   */
  public function check() {
	$this->bootstrap_internet_connection();
	if ($this->internet_connection === nx::INTERNET_CONNECTION_OK) {
	  $this->bootstrap_config();
	  $uri = $this->endpoint;
  
	  $this->container['request_method'] = 'get';
	  $this->container['request_uri'] = $uri;
	  $request = $this->container['request']
		->send();
  
	  $endpoint_ok = $this->response_code($request, $uri);
  
	  if ($endpoint_ok) {
		tools::print_green("Endpoint %uri esta acessivel.", array('%uri' => $uri));
	  }
  
	  $this->set_folders(TRUE);
  
	  $email = $this->container['config']['servidor_smtp']['username'];
	  try {
		$transport = $this->container['email_transport'];
		$transport->handshake();
		tools::print_green("O servidor do gmail respondeu Ok. As credenciais do email %email sao validas.", array('%email' => $email));
	  }
	  catch(Exception $e){
		$msg = $e->getMessage();
		tools::print_red("Algo deu errado ao tentar verificar as credenciais para o email %email. O Gmail respondeu o seguinte:" . PHP_EOL . $msg, array('%email' => $email));
	  }
	}
  }

  /**
   * Checks the current internet connection status.
   */
  public function bootstrap_internet_connection() {
    $google = $this->container['internet_connection_google'];
	$nortaox = $this->container['internet_connection_nortaox'];

    $google = @fsockopen($google, 80);
	$nortaox = @fsockopen($nortaox, 80);

	if ($google && $nortaox || !$google && $nortaox) {
	  tools::print_green("A internet esta acessivel e o website da %nortaox esta responsivo.", array('%nortaox' => 'NortaoX.com'));
	  $this->internet_connection = nx::INTERNET_CONNECTION_OK;
	  fclose($nortaox);
	  if ($google) {
		fclose($google);
	  }
	}

	if ($google && !$nortaox) {
	  $this->internet_connection = nx::INTERNET_CONNECTION_UP_NORTAOX_DOWN;
	  $this->log("A internet esta acessivel mas o website da NortaoX.com NAO esta responsivo. Tente mais tarde.", 'yellow');
	  fclose($google);
	}

	if (!$google && !$nortaox) {
	  $this->internet_connection = nx::INTERNET_CONNECTION_DOWN;
	  $this->log("NAO ha conexao com a internet.", 'red');
	}
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

		foreach(${$folder . "_subfolders"} as $service) {
		  $full_subfolder_path = "$folder_path/$service";
		  $mkdir = TRUE;

		  if (!file_exists($full_subfolder_path)) {
			$mkdir = mkdir($full_subfolder_path, 0777, TRUE);
		  }

		  if (!$mkdir || !is_writable($folder_path)) {
			$error_msgs[] = $full_subfolder_path;
		  }
		}
	  }

	  if ($error_msgs) {
		tools::print_red("Nao foi possivel criar ou nao eh possivel gravar arquivos dentro das seguintes pastas:");
		foreach ($error_msgs as $error_number => $msg) {
		  print $error_number + 1 . ". $msg" . PHP_EOL;
		}
		throw new Exception(tools::print_yellow("--Verifique as permissoes do usuario--"));
	  }
	}
	if ($check) {
	  tools::print_green("As pastas dados, tmp e suas subpastas foram criadas com sucesso.");
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
	if ($this->internet_connection === nx::INTERNET_CONNECTION_OK) {
	  $username = $this->config['credenciais']['username'];
	  $session_file = $this->folders['tmp'] . "/.session";
  
	  if (file_exists($session_file) && !$reset) {
		$session = $this->container['ini_reader']
		  ->fromFile($session_file);
  
		$this->merchant_login['session'] = $session['session'];
		$this->merchant_login['token'] = $session['token'];
  
		tools::print_green("Credenciais para o usuario %username foram carregadas a partir de arquivo de sessao.", array('%username' => $username));
	  }
	  else {
		// Request merchant authentication.
		$endpoint = $this->endpoint;
		$service = $this->config['servicos']['login']['url'];
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
		  $session_id = $request->body->sessid;
		  $session_name = $request->body->session_name;

		  $session = array();
		  $this->merchant_login['session'] = $session['session'] = "$session_name=$session_id";
		  $this->merchant_login['token'] = $session['token'] = $request->body->token;

		  $writer = $this->container['ini_writer'];
		  $writer->toFile($session_file, $session, $this->container['ini_writer_lock']);

		  tools::print_green("Login do usuario %username foi bem sucessido.", array('%username' => $username));
		  if ($reset) {
			tools::print_green("Novo token foi salvo com sucesso.");
		  }
		}
		else {
		  $http_code = $this->response_code;
		  $print = "Algo saiu errado. Codigo HTTP: $http_code" . PHP_EOL;
		  $print .= $this->response_error_msg;

		  $this->log($print, 'red');
		}
	  }
	}
  }

  /**
   * Performs the POST, PUT and GET requests.
   *
   * @param String $service
   *   The service path.
   *
   * @param Array $item_data
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
  private function request($service, $item_data, $http_method, $service_method = '') {
	if ($this->internet_connection === nx::INTERNET_CONNECTION_OK) {
	  $endpoint = $this->endpoint;
	  $service = $this->config['servicos'][$service]['url'];
  
	  $uri = "$endpoint/$service" . $service_method;
  
	  try {
		$this->container['request_method'] = $http_method;
		$this->container['request_uri'] = $uri;
		$request = $this->container['request']
		  ->sendsJson()
		  ->expectsJson()
		  ->addHeader('Cookie', $this->merchant_login['session'])
		  ->addHeader('X-CSRF-Token', $this->merchant_login['token'])
		  ->body($item_data)
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
   */
  private function response_code(\Httpful\Response $response, $uri) {
	$this->response_code = $code = $response->code;

	if ($code != 200) {
	  $body = $response->raw_body;

	  $print = "A chamada ao Endpoint $uri FALHOU. Retornou o Codigo de Status HTTP $code." . PHP_EOL;
	  if (!empty($body)) {
		$print .= "O Webservice respondeu o seguinte:" . PHP_EOL;
		$print .= $body;
	  }
	  $this->log($print, 'red');

	  return FALSE;
	}
	return TRUE;
  }

  /*private function create($service = 'produto') {
	$item_data = array(
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
  }

  private function update($service = 'produto', $service_method = 'atualizar') {
	$item_data = array(
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
  }*/

  /**
   * Deletes a file. Logs message if it fails.
   *
   * @param String $file_full_path
   *   The full file path.
   */
  private function delete_file($file_full_path) {
	if (file_exists($file_full_path)) {
	  if (!unlink($file_full_path)) {
		$this->log("Nao foi possivel deletar o arquivo $file_full_path.", 'red');
	  }
	}
  }

  /**
   * Checks dados' subfolders for new files and sync their data with the
   * webservice.
   */
  public function scan_dados_folder() {
	// Go on only if there is internet connection.
	if ($this->internet_connection === nx::INTERNET_CONNECTION_OK) {
	  // Will hold the overall reading results of each individual item data.
	  $result = array();

	  $this->container['scan_folder_path'] = $this->folders['dados'];
	  $subfolders = $this->container['scan_folder'];

	  foreach ($subfolders as $subfolder_object) {
		if ($subfolder_object->isDir()) {
		  $item_data_folder = $subfolder_object->getPath();
		  $service = $subfolder_object->getBasename();

		  // Check if current subfolder should be scanned.
		  if (in_array($service, $this->sync_services())) {
			$prime_id_field = $this->config['servicos'][$service]['prime_id_field'];
			$secondary_id_field = $this->config['servicos'][$service]['secondary_id_fields'];

			$this->container['scan_folder_path'] = $item_data_folder;
			$files = $this->container['scan_folder'];
	  
			foreach ($files as $file_object) {
			  if ($file_object->isFile()) {
				$file_name = $file_object->getFilename();
				$file_full_path = "$item_data_folder/$file_name";
		
				if (filesize($file_full_path) === 0) {
				  // File is empty. So, it goes straight into the fail's bin.
				  $item_data = array();
				  $this->set_sync_attempt_tag($item_data, nx::SYNC_TAG_ACTION_ITEM_DATA_EMPTY);
				  $this->move_item_data($item_data, $service, $file_name, nx::MOVE_ITEM_DATA_ACTION_FAIL);
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
					  $this->scan_dados_item_data($item_data, $service, $file_name, $result);
					}
				  }
				  else {
					// There is only one item in the file.
					$this->scan_dados_item_data($item_file_data, $service, $file_name, $result);
				  }
				}
				else {
				  $print = "Nao foi possivel carregar o arquivo $file_full_path.";
				  $this->log($print, 'yellow');
				  $this->notify($print);
		
				  $tmp = $this->folders['tmp'];
				  $move_to = "$tmp/falhas/$service/$file_name";
				  // Move file to fail's bin.
				  if (!rename($file_full_path, $move_to)) {
					$this->log("Nao foi possivel mover o arquivo $file_full_path para $move_to.", 'yellow');
				  }
				}
		
				// We are done with this file. By now its content should either be,
				// partially or entirely, at the fail or success bin.
				$this->delete_file($file_full_path);
			  }
			}
		  }
		}
	  }

	  if (!empty($result['success'])) {
		foreach ($result['success'] as $item) {
		  $item_data = $item['item_data'];
		  $file_name = $item['file_name'];

		  $this->move_item_data($item_data, $service, $file_name, nx::MOVE_ITEM_DATA_ACTION_SUCCESS);
		}
	  }
	  if (!empty($result['fail'])) {
		// Send an email notification to the system admin.
		$tmp_falhas = $this->folders['tmp'] . "/falhas/$service";
		$tmp_logs = $this->folders['tmp'] . "/logs";
		$msg = "A sincronização falhou. Verifique as pastas:" . PHP_EOL;
		$msg .= "a) $tmp_falhas" . PHP_EOL;
		$msg .= "b) $tmp_logs";
		$this->notify($msg);
		$this->log($msg);

		foreach ($result['fail'] as $item) {
		  $item_data = $item['item_data'];
		  $file_name = $item['file_name'];

		  $this->move_item_data($item_data, $service, $file_name, nx::MOVE_ITEM_DATA_ACTION_FAIL);
		}
	  }
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
   * @param Array $result
   *   Holds the overall reading results of each item data.
   */
  private function scan_dados_item_data($item_data, $service, $file_name, &$result) {
	$prime_id_field = $this->config['servicos'][$service]['prime_id_field'];
	$secondary_id_field = $this->config['servicos'][$service]['secondary_id_fields'];
	$result_array = array(
	  'item_data' => $item_data,
	  'file_name' => $file_name,
	);

	$create = TRUE;
	if (isset($item_data[$prime_id_field])) {
	  $create = FALSE;
	}
	else {
	  // Try to retrive the item from the webservice using a
	  // secondary id field.
	  foreach($secondary_id_field as $secondary_field_name) {
		if (isset($item_data[$secondary_field_name])) {
		  $item_id = $item_data[$secondary_field_name];

		  $method_name = "get_$service" . "_by_$secondary_field_name";
		  if (method_exists($this, $method_name)) {
			$item_exists = $this->{$method_name}($item_id, FALSE);

			if ($item_exists) {
			  // We found a secundary id value in the item data.
			  $create = FALSE;
			  // Stop looping and we go on.
			  break;
			}
		  }
		}
	  }
	}

	if ($create) {
	  $sync_ok = $this->request($service, $item_data, 'post');
	}
	else {
	  // Update.
	  $sync_ok = $this->request($service, $item_data, 'put', "/atualizar");
	}

	$this->set_sync_attempt_tag($item_data, nx::SYNC_TAG_ACTION_UPDATE);
	if ($sync_ok) {
	  $result['success'][] = $result_array;
	}
	else {
	  $result['fail'][] = $result_array;
	}
  }

  /**
   * Adds a syncronization attempt tag into the item data array.
   *
   * @param Array $item_data
   *   See scan_dados_item_data().
   *
   * @param Bool $action
   *   Which action has been performed. CREATE/UPDATE or FAIL.
   *
   * @param String $service
   *   The service name.
   */
  private function set_sync_attempt_tag(&$item_data, $action, $service = 'produto') {
	switch($action) {
	  case nx::SYNC_TAG_ACTION_CREATE:
		tools::print_green("Item foi %action com sucesso no servico %service.", array('%action' => 'criado', '%service' => $service));
	  break;
	  case nx::SYNC_TAG_ACTION_UPDATE:
		tools::print_green("Item foi %action com sucesso no servico %service.", array('%action' => 'atualizado', '%service' => $service));
	  break;
	  case nx::SYNC_TAG_ACTION_ITEM_DATA_EMPTY:
		tools::print_yellow("O arquivo dados estava vazio.");
	  break;
	  case nx::SYNC_TAG_ACTION_FAIL:
		tools::print_yellow("Algo saiu errado. O arquivo de dados eh invalido.");
	  break;
	  default:
		$msg = "O valor do parametro \$action eh invalido. Entre em contado com a NortaoX.";
		$this->log($msg);
		throw new \InvalidArgumentException(tools::print_red($msg));
	}

	$attempts = 1;
	if (isset($item_data['-sincronizacao-']['tentativas'])) {
	  $attempts += $item_data['-sincronizacao-']['tentativas'];
	}

	$this->container['date_format'] = "Y-m-d H:i:s";
	$date_time = $this->container['date_time'];

	$item_data['-sincronizacao-'] = array(
	  'tentativas' => $attempts,
	  'hora_ultima_tentativa' => $date_time,
	  'ultima_msg' => $msg,
	);
  }

  /**
   * Moves the item data collected from dados folder to either
   * tmp/falhas or tmp/sucessos folder.
   *
   * @param Array $item_data
   *   The data array.
   *
   * @param String $service
   *   The service name.
   *
   * @param String $file_name
   *   The file name which the data will be saved in.
   *
   * @param String $action
   *   Expects either "falhas" or "sucessos"
   */
  private function move_item_data($item_data, $service, $file_name, $action) {
	$tmp = $this->folders['tmp'];
	$destination_folder = "$tmp/$action/$service";
	$file_full_path = "$destination_folder/$file_name";

	try {
	  $writer = $this->container['ini_writer'];
	  $writer->toFile($file_full_path, (array) $item_data, $this->container['ini_writer_lock']);
	}
	catch(Exception $e) {
	  $this->log("Nao foi possivel salvar o arquivo $file_full_path.", 'red');
	}
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
  public function get_pedido_by_number($order_number, $save_result = TRUE) {
	$qs = array('no' => $order_number);
	$request = $this->retrieve_service_item($qs, 'pedido', '');

	if ($request && $save_result) {
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
  public function get_produto_by_product_id($product_id, $save_result = TRUE) {
	$qs = array('product_id' => $product_id);
	$request = $this->retrieve_service_item($qs);

	if ($request && $save_result) {
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
  public function get_produto_by_sku($sku, $save_result = TRUE) {
	$qs = array('sku' => $sku);
	$request = $this->retrieve_service_item($qs);

	if ($request && $save_result) {
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
  public function get_produto_by_cod_produto_erp($cod_produto_erp, $save_result = TRUE) {
	$qs = array('cod_produto_erp' => $cod_produto_erp);
	$request = $this->retrieve_service_item($qs);

	if ($request && $save_result) {
	  $this->save_retrieved_result("produto_cod_produto_erp_$cod_produto_erp");
	}
	return $request;
  }

  /**
   * Retrieves a list of cities which NortaoX is or will trade in.
   */
  public function get_cities() {
	$request = $this->request('cidades', '', 'get');

	if ($request) {
	  $this->save_retrieved_result('cidades');
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
	  $writer->toFile($file_full_path, (array) $item, $this->container['ini_writer_lock']);

	  tools::print_green("Consulta foi salva em %file_full_path", array('%file_full_path' => $file_full_path));
	}
	catch(Exception $e) {
	  $this->log("Nao foi possivel salvar a consulta em $file_full_path.", 'red');
	}
  }
}
