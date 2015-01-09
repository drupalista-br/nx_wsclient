<?php
namespace NXWSClient;
use \Httpful\Request;
use \Zend\Config\Writer\Ini;

//@TODO Implement exceptions.

class nx {
  private $session,
		  $config,
		  $endpoint,
		  $root_folder,
		  $folders,
		  $log_file,
		  $merchant_uid,
		  $response_body_json,
		  $response_code,
		  $response_error_msg;

  /**
   * @param String $environment
   *   Expects either producao or sandbox. producao is default.
   *
   * @param Bool $check_endpoint
   *   Checks if endpoint is alive.
   */
  public function __construct($is_dev = FALSE) {
	$root_folder = pathinfo(__DIR__);
	$root_folder = $this->root_folder = dirname($root_folder['dirname']);

	$config_file = "$root_folder/config.ini";
	if (!file_exists($config_file)) {
	  $this->response_code = 500;
	  $this->response_error_msg = $msg = "O arquivo $config_file nao existe." . PHP_EOL;
	  exit($msg);
	}

	$this->config = $config = parse_ini_file($config_file, TRUE);
	$environment = ($is_dev) ? 'dev' : $config['ambiente'];

	$this->endpoint = $config['endpoint'][$environment];
	$this->set_folder_locations();

	$current_date = date("Y-m-d");
	$this->log_file = $log_file = $this->folders['tmp'] . "/logs/$current_date.log";

	if (!isset($config['endpoint'][$environment])) {
	  $this->response_code = 500;
	  $time = date('G:i:s');
	  $log = "----$time----" . PHP_EOL;

	  $eol = PHP_EOL;
	  print $print = "O arquivo config.ini nao contem a instrucao:$eol [endpoint] $eol $environment = URI$eol";
	  $this->response_error_msg = $print;
	  $log .= $print;

	  file_put_contents($log_file, $log, FILE_APPEND);
	  exit();
	}

	$this->login();
  }
  
  /**
   * Sends a test request to the endpoint.
   * Checks if dados and tmp folders are reachable.
   */
  public function check() {
	$uri = $this->endpoint;

	$request = Request::get($uri)
	  ->send();

	$endpoint_ok = $this->response_code($request, $uri);

	if ($endpoint_ok) {
	  print "Endpoint $uri esta acessivel." . PHP_EOL;
	}

	$this->set_folder_locations(TRUE);
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
  private function response_code($response, $uri) {
	$this->response_code = $code = $response->code;

	if ($code != 200) {
	  $this->response_error_msg = $body = $response->raw_body;
	  $time = date('G:i:s');
	  $log = "----$time----" . PHP_EOL;
	  $log_file = $this->log_file;

	  print $print = "A chamada ao Endpoint $uri FALHOU. Retornou o Codigo de Status HTTP $code." . PHP_EOL;
	  $log .= $print;
	  if (!empty($body)) {
		print $print = "O Webservice respondeu o seguinte:" . PHP_EOL;
		$log .= $print;
		print $print = $body . PHP_EOL;
		$log .= $print;
	  }
	  file_put_contents($log_file, $log, FILE_APPEND);
	  return FALSE;
	}
	return TRUE;
  }

  /**
   * Loads folder's paths from config.ini and creates all the app's subfolders.
   */
  private function set_folder_locations($check = FALSE) {
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

		foreach(${$folder}  . "_subfolders" as $subfolder_name) {
		  $full_subfolder_path = "$folder_path/$subfolder_name";
		  $mkdir = mkdir($full_subfolder_path, 0777, TRUE);

		  if (!$mkdir) {
			$error_msgs[] = $full_subfolder_path;
		  }
		}
	  }

	  if ($error_msgs) {
		print "Nao foi possivel criar as seguintes pastas:" . PHP_EOL;
		foreach ($error_msgs as $line_number => $msg) {
		  print $line_number + 1 . ". $msg" . PHP_EOL;
		}
		exit("--Verifique as permissoes do usuario--" . PHP_EOL);
	  }
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
  private function login($reset = FALSE) {
	$session_file = $this->folders['tmp'] . "/.session";
	$token_file = $this->folders['tmp'] . "/.token";

	if ((file_exists($session_file) && file_exists($token_file)) && !$reset) {
	  $this->session = file_get_contents($session_file);
	  $this->token = file_get_contents($token_file);

	  return TRUE;
	}
	else {
	  // Request user authentication.
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
  
		$this->session = "$session_name=$sessid";
		$this->token = $request->body->token;
		file_put_contents($session_file, $this->session);
		file_put_contents($token_file, $this->token);

	  }

	  if ($reset && $response_ok) {
		print "Novo token foi salvo com sucesso." . PHP_EOL;
	  }
	  else {
		$http_code = $this->response_code;
		print "Algo saiu errado. Codigo HTTP: $http_code" . PHP_EOL;
		print $this->response_error_msg . PHP_EOL;
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
  private function request($service, $body, $http_method, $service_method = '') {
	$endpoint = $this->endpoint;
	if (!isset($this->config['servicos'][$service])) {
	  $time = date('G:i:s');
	  $log = "----$time----" . PHP_EOL;
	  $log_file = $this->log_file;

	  print $log = "Nao existe o servico $service no config.ini" . PHP_EOL;
	  file_put_contents($log_file, $log, FILE_APPEND);

	  return FALSE;
	}
	$service = $this->config['servicos'][$service];

	$uri = "$endpoint/$service" . $service_method;

	try {
	  $request = Request::$http_method($uri)
		->sendsJson()
		->expectsJson()
		->addHeader('Cookie', $this->session)
		->addHeader('X-CSRF-Token', $this->token)
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
  public function create($service = 'produto') {
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
  public function update($service = 'produto', $service_method = 'atualizar') {
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
   * Checks dados/$subfolder_name for new files. It then reads them, calls either
   * create or update method.
   * If create or update returns TRUE ( success ) it moves the data file to
   * tmp/sucesso/$subfolder_name, otherwise moves data file o tmp/falhas/$subfolder_name.
   *
   * @param String $subfolder_name
   *   The subfolder name where there will be one or more files contaning
   *   items data for either creating or updating them to the NortaoX.com.
   *
   * @param String $prime_id_field_name
   *   The item id field name at the NortaoX.com.
   *
   * @param Array $secondary_id_field_names
   *   Field names that, apart from the prime id field name, also hold an
   *   unique identification for sorting out a single item.
   * 
   */
  public function scan_dados_folder($subfolder_name = 'produto', $prime_id_field_name = 'product_id', $secondary_id_field_names = array('sku', 'cod_produto_erp')) {
	$dados = $this->folders['dados'];
	$item_data_folder = "$dados/$subfolder_name";

	try{
	  $files = new FilesystemIterator($item_data_folder);
	}
	catch(Exception $e) {
	  // @TODO: Handle exceptions.
	}
	foreach ($files as $file_object) {
	  if ($file_object->isFile()) {
		$file_name = $file_object->getFilename();
		$file_full_path = "$item_data_folder/$file_name";

		if (filesize($file_full_path) === 0) {
		  // File is empty. So, it goes straight into the fail's bin.
		  $error_msg = "";
		  $this->scan_dados_fail($file_name, $subfolder_name, $error_msg);
		  break;
		}

		$item_data = parse_ini_file($file_full_path, TRUE);


	  }
	}
  }

  private function scan_dados_fail() {
	$tmp = $this->folders['tmp'];
	$falhas_folder = "$tmp/falhas/$subfolder_name";
	
  }

  private function scan_dados_success() {
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
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_order_by_number($order_number) {
	$qs = array('no' => $order_number);
	$request = $this->retrieve_service_item($qs, 'pedido', '');

	if ($request) {
	  return $this->save_retrieved_result("pedido_no_$order_number");
	}

	return $request;
  }

  /**
   * Retrieves a single product based on its product_id field.
   *
   * @param String $product_id
   *   The Product ID value set at the NortaoX application.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_product_by_product_id($product_id) {
	$qs = array('product_id' => $product_id);
	$request = $this->retrieve_service_item($qs);
	if ($request) {
	  return $this->save_retrieved_result("produto_product_id_$product_id");
	}

	return $request;
  }

  /**
   * Retrieves a single product based on its sku field.
   *
   * @param String $sku
   *   The SKU value set at the NortaoX application.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_product_by_sku($sku) {
	$qs = array('sku' => $sku);
	$request = $this->retrieve_service_item($qs);

	if ($request) {
	  return $this->save_retrieved_result("produto_sku_$sku");
	}

	return $request;
  }

  /**
   * Retrieves a single product based on its cod_produto_erp field.
   *
   * @param String $cod_produto_erp
   *   The product id value set at the ERP application.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_product_by_cod_produto_erp($cod_produto_erp) {
	$qs = array('cod_produto_erp' => $cod_produto_erp);
	$request = $this->retrieve_service_item($qs);

	if ($request) {
	  return $this->save_retrieved_result("produto_cod_produto_erp_$cod_produto_erp");
	}

	return $request;
  }

  /**
   * Retrieves a list of cities which NortaoX is or will trade in.
   *
   * @return Bool
   *   Whether or not the request was successful.
   */
  public function get_cities() {
	$request = $this->request('cidades', '', 'get');

	if ($request) {
	  return $this->save_retrieved_result('cidades');
	}

	return $request;
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
	  $writer = new Ini();
	  $writer->toFile($file_full_path, (array) $item);

	  print_r($item);
	  print "Consulta foi salva em $file_full_path" . PHP_EOL;
	}
	catch(Exception $e) {
	  // @TODO: Handle exceptions.
	}
  }
  
}
