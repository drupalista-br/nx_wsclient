<?php
namespace NXWSClient;
use Httpful\Request;
use Zend\Config\Writer\Ini;

class nx {
  private $root_folder,
		  // Type array.
		  $config,
		  // Type array.
		  $folders,
		  $log_file,
		  $endpoint,
		  // Type array.
		  $merchant_login,
		  $response_body_json,
		  $response_code,
		  $response_error_msg;

  /**
   * Initial construction.
   */
  public function __construct($is_dev = FALSE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($is_dev), false)) !== __AM_CONTINUE__) return $__am_res; 
	$this->bootstrap_root_folder();
	$this->bootstrap_config();
	$this->bootstrap_folders();
	$this->bootstrap_log_file();
	$this->bootstrap_validate_config();
	$this->bootstrap_endpoint($is_dev);
	$this->bootstrap_merchant_login();
  }
  
  private function bootstrap_root_folder() { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
	$root_folder = pathinfo(__DIR__);
	$this->root_folder = dirname($root_folder['dirname']);
  }

  private function bootstrap_config() { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
	$root_folder = $this->root_folder;
	$config_file = "$root_folder/config.ini";

	if (!file_exists($config_file)) {
	  $this->response_code = 500;
	  $this->response_error_msg = $msg = "O arquivo $config_file nao existe." . PHP_EOL;
	  exit($msg);
	}
	$this->config = $config = parse_ini_file($config_file, TRUE);

	if (!$config) {
	  // Cant open config.ini.
	  $this->response_code = 500;
	  $this->response_error_msg = $print = "Nao foi possivel abrir o arquivo $config_file." . PHP_EOL;
	  exit($print);
	}

	// config.ini must have [pastas] section to start off. Further validation
	// will be done by bootstrap_validate_config().
	if (empty($config['pastas'])) {
	  $this->response_code = 500;
	  $this->response_error_msg = $print = "A secao [pastas] nao existe ou nao tem atributos definidos. Arquivo $config_file." . PHP_EOL;
	  exit($print);
	}

	if (empty($config['pastas']['dados']) || empty($config['pastas']['tmp'])) {
	  $this->response_code = 500;
	  $this->response_error_msg = $print = "Os valores para dados ou tmp nao estao definidos. Arquivo $config_file." . PHP_EOL;
	  exit($print);
	}
  }

  private function bootstrap_log_file() { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
	$current_date = date("Y-m-d");
	$this->log_file = $this->folders['tmp'] . "/logs/$current_date.log";
  }

  private function bootstrap_validate_config() { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
	$config = $this->config;

	if (empty($config['ambiente'])) {
	  $this->response_code = 500;
	  $this->response_error_msg = $print = "O valor para ambiente nao esta definido. Arquivo $config_file." . PHP_EOL;
	  $this->log($print);
	  exit($print);
	}

	if (empty($config['endpoint']['sandbox']) ||
		empty($config['endpoint']['producao']) ||
		empty($config['endpoint']['dev'])) {
	  $this->response_code = 500;
	  $this->response_error_msg = $print = "Os valores para producao ou sandbox ou dev nao estao definidos. Arquivo $config_file." . PHP_EOL;
	  $this->log($print);
	  exit($print);
	}

	if (empty($config['servicos']['login']) ||
		empty($config['servicos']['produto']) ||
		empty($config['servicos']['pedido']) ||
		empty($config['servicos']['cidades'])) {
	  $this->response_code = 500;
	  $this->response_error_msg = $print = "Os valores para login ou produto ou pedido ou cidades nao estao definidos. Arquivo $config_file." . PHP_EOL;
	  $this->log($print);
	  exit($print);
	}

	if (empty($config['credenciais']['username']) || empty($config['credenciais']['password'])) {
	  $this->response_code = 500;
	  $this->response_error_msg = $print = "Os valores para username ou password nao estao definidos. Arquivo $config_file." . PHP_EOL;
	  $this->log($print);
	  exit($print);
	}
  }

  private function bootstrap_endpoint($is_dev) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($is_dev), false)) !== __AM_CONTINUE__) return $__am_res; 
	$environment = ($is_dev) ? 'dev' : $this->config['ambiente'];

	if (!isset($config['endpoint'][$environment])) {
	  $eol = PHP_EOL;
	  print $print = "O arquivo config.ini nao contem a instrucao:$eol [endpoint] $eol $environment = URI$eol";
	  $this->log($print);
	  exit();
	}

	$this->endpoint = $this->config['endpoint'][$environment];
  }

  /**
   * Logs failures.
   *
   * @param String $msg
   *   Message to log.
   */
  private function log($msg) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($msg), false)) !== __AM_CONTINUE__) return $__am_res; 
	if (!empty($this->log_file)) {
	  $log_file = $this->log_file;

	  $this->response_code = 500;
	  $time = date('G:i:s');
	  $log = "----$time----" . PHP_EOL;
	  $log .= $msg;

	  $this->response_error_msg = $msg;
	  file_put_contents($log_file, $log, FILE_APPEND);
	}
  }
  
  /**
   * Sends a test request to the endpoint.
   * Checks if dados and tmp folders are reachable.
   */
  public function check() { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(), false)) !== __AM_CONTINUE__) return $__am_res; 
	$uri = $this->endpoint;

	$request = Request::get($uri)
	  ->send();

	$endpoint_ok = $this->response_code($request, $uri);

	if ($endpoint_ok) {
	  print "Endpoint $uri esta acessivel." . PHP_EOL;
	}

	$this->bootstrap_folders(TRUE);
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
  private function response_code($response, $uri) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($response, $uri), false)) !== __AM_CONTINUE__) return $__am_res; 
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
   * Loads folder's paths from config.ini and creates all the app's subfolders.
   */
  private function bootstrap_folders($check = FALSE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($check), false)) !== __AM_CONTINUE__) return $__am_res; 
	foreach($this->config['pastas'] as $folder => $folder_path) {
	  // Make sure forward slash directory separator is gonna be in place.
	  $folder_path = str_replace('\\', '/', $folder_path);

	  // Set this app's root path as default for this $folder.
	  $this->folders[$folder] = $folder_path = str_replace('%app%', $this->root_folder, $folder_path);

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
		foreach ($error_msgs as $line_number => $msg) {
		  print $line_number + 1 . ". $msg" . PHP_EOL;
		}
		exit("--Verifique as permissoes do usuario--" . PHP_EOL);
	  }
	}
	if ($check) {
	  exit("As pastas dados, tmp e suas subpastas foram criadas com sucesso." . PHP_EOL);
	}
  }

  /**
   * Authenticates the merchant user at the NortaoX.com.
   *
   * @param Bool $reset
   *   Whether or not the session and the token should be renewed even when
   *   there already is one set in file.
   *
   * @return Bool
   *   Whether or not the login was successful.
   */
  private function bootstrap_merchant_login($reset = FALSE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($reset), false)) !== __AM_CONTINUE__) return $__am_res; 
	$session_file = $this->folders['tmp'] . "/.session";

	if (file_exists($session_file) && !$reset) {
	  $session = parse_ini_file($session_file, TRUE);

	  $this->merchant_login['session'] = $session['session'];
	  $this->merchant_login['token'] = $session['token'];

	  return TRUE;
	}
	else {
	  // Request merchant authentication.
	  $endpoint = $this->endpoint;
	  $service = $this->config['servicos']['login'];
	  $username = $this->config['credenciais']['username'];
	  $password = $this->config['credenciais']['password'];

	  $uri = "$endpoint/$service";

	  $request = Request::post($uri)
		->body("username=$username&password=$password")
		->expectsJson()
		->send();

	  $response_ok = $this->response_code($request, $uri);

	  if ($response_ok) {
		$sessid = $request->body->sessid;
		$session_name = $request->body->session_name;
  
		$session = array();
		$this->merchant_login['session'] = $session['session'] = "$session_name=$sessid";
		$this->merchant_login['token'] = $session['token'] = $request->body->token;

		$writer = new Ini();
		$writer->toFile($session_file, $session);
	  }
	  else {
		$http_code = $this->response_code;
		print $print = "Algo saiu errado. Codigo HTTP: $http_code" . PHP_EOL;
		print $this->response_error_msg . PHP_EOL;

		$print .= $this->response_error_msg;
		$this->log($print);
	  }

	  if ($reset && $response_ok) {
		print "Novo token foi salvo com sucesso." . PHP_EOL;
	  }

	  return $response_ok;
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
  private function request($service, $body, $http_method, $service_method = '') { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($service, $body, $http_method, $service_method), false)) !== __AM_CONTINUE__) return $__am_res; 
	$endpoint = $this->endpoint;
	$service = $this->config['servicos'][$service];

	$uri = "$endpoint/$service" . $service_method;

	try {
	  $request = Request::$http_method($uri)
		->sendsJson()
		->expectsJson()
		->addHeader('Cookie', $this->merchant_login['session'])
		->addHeader('X-CSRF-Token', $this->merchant_login['token'])
		->body($body)
		->send();
	}
	catch (Exception $e) {
	  print $e->getMessage();
	}

	$reponse_ok = $this->response_code($request, $uri);

	if ($reponse_ok) {
	  $this->response_body_json = $request->raw_body;
	}

	return $reponse_ok;
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
  public function create($service = 'produto') { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($service), false)) !== __AM_CONTINUE__) return $__am_res; 
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

	return $this->request($service, $body, 'post');
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
  public function update($service = 'produto', $service_method = 'atualizar') { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($service, $service_method), false)) !== __AM_CONTINUE__) return $__am_res; 
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
  private function delete_file($file_full_path) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($file_full_path), false)) !== __AM_CONTINUE__) return $__am_res; 
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
   * 
   */
  public function scan_dados_folder($subfolder_name = 'produto', $prime_id_field_name = 'product_id', $secondary_id_field_names = array('sku', 'cod_produto_erp')) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($subfolder_name, $prime_id_field_name, $secondary_id_field_names), false)) !== __AM_CONTINUE__) return $__am_res; 
	$dados = $this->folders['dados'];
	$item_data_folder = "$dados/$subfolder_name";

	try{
	  $files = new FilesystemIterator($item_data_folder);
	}
	catch(Exception $e) {
	  // @TODO: Handle exceptions.
	}

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

		$item_file_data = parse_ini_file($file_full_path, TRUE);
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
  private function scan_dados_item_data($item_data, $service, $file_name, $prime_id_field_name, $secondary_id_field_names, &$result) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($item_data, $service, $file_name, $prime_id_field_name, $secondary_id_field_names, &$result), false)) !== __AM_CONTINUE__) return $__am_res; 
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

			  // This item already exists and gotta be updated.
			  $update_ok = $this->update($item_data, $service);
			  $this->set_sync_attempt_tag($item_data, $service, $create);

			  if ($update_ok) {
				$result['success'][] = array(
				  'item_data' => $item_data,
				  'file_name' => $file_name,
				);
				return;
			  }

			  $result['fail'][] = array(
				'item_data' => $item_data,
				'file_name' => $file_name,
			  );
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
		return;
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
   *
   * @return Array
   *   The syncronization tag.
   */
  private function set_sync_attempt_tag(&$item_data, $service = FALSE, $create = null) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array(&$item_data, $service, $create), false)) !== __AM_CONTINUE__) return $__am_res; 
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

	$item_data['-sincronizacao-'] = array(
	  'tentativas' => $attempts,
	  'hora_ultima_tentativa' => date("Y-m-d H:i:s"),
	  'ultima_msg' => $msg,
	);
  }

  /**
   *
   */
  private function scan_dados_fail($file_data, $subfolder_name, $file_name) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($file_data, $subfolder_name, $file_name), false)) !== __AM_CONTINUE__) return $__am_res; 
	$tmp = $this->folders['tmp'];
	$falhas_folder = "$tmp/falhas/$subfolder_name";
	$file_full_path = "$falhas_folder/$file_name";

	
  }

  /**
   *
   */
  private function scan_dados_success($file_data, $subfolder_name, $file_name) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($file_data, $subfolder_name, $file_name), false)) !== __AM_CONTINUE__) return $__am_res; 
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
  public function retrieve_service_item($qs = array(), $service = 'produto', $service_method = '/consultar') { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($qs, $service, $service_method), false)) !== __AM_CONTINUE__) return $__am_res; 
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
  public function get_pedido_by_number($order_number, $save_result = TRUE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($order_number, $save_result), false)) !== __AM_CONTINUE__) return $__am_res; 
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
  public function get_produto_by_product_id($product_id, $save_result = TRUE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($product_id, $save_result), false)) !== __AM_CONTINUE__) return $__am_res; 
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
  public function get_produto_by_sku($sku, $save_result = TRUE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($sku, $save_result), false)) !== __AM_CONTINUE__) return $__am_res; 
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
  public function get_produto_by_cod_produto_erp($cod_produto_erp, $save_result = TRUE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($cod_produto_erp, $save_result), false)) !== __AM_CONTINUE__) return $__am_res; 
	$qs = array('cod_produto_erp' => $cod_produto_erp);
	$request = $this->retrieve_service_item($qs);

	if ($request && $save_result) {
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
  public function get_cities($save_result = TRUE) { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($save_result), false)) !== __AM_CONTINUE__) return $__am_res; 
	$request = $this->request('cidades', '', 'get');

	if ($request && $save_result) {
	  $this->save_retrieved_result('cidades');
	}

	return $request;
  }

  /**
   * Saves a item object into a txt file. The file content has a ini structure.
   *
   * @param String $file_name
   *   The name of the file which the retrieved content will be saved into.
   */
  private function save_retrieved_result($file_name, $file_extension = 'txt') { if (($__am_res = __amock_before($this, __CLASS__, __FUNCTION__, array($file_name, $file_extension), false)) !== __AM_CONTINUE__) return $__am_res; 
	$file_full_path = $this->folders['dados'] . "/consulta/$file_name.$file_extension";

	$item = json_decode($this->response_body_json, true);

	try {
	  $writer = new Ini();
	  $writer->toFile($file_full_path, (array) $item);

	  print_r($item);
	  print "Consulta foi salva em $file_full_path" . PHP_EOL;
	}
	catch(Exception $e) {
	  $print = "Nao foi possivel salvar a consulta em $file_full_path." . PHP_EOL;
	  $this->log($print);
	}
  }
}
