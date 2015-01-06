<?php
namespace NXWSClient;
use \Httpful\Request;

class nx {
  private $session,
		  $config,
		  $endpoint,
		  $root_folder,
		  $folders,
		  $log_file,
		  $merchant_uid,
		  $response_body_object,
		  $response_body_json;

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

	$config_file = $root_folder . DIRECTORY_SEPARATOR . "config.ini";
	if (!file_exists($config_file)) {
	  exit("O arquivo $config_file nao existe." . PHP_EOL);
	}

	$this->config = $config = parse_ini_file($config_file, TRUE);
	$environment = ($is_dev) ? 'dev' : $config['ambiente'];

	$this->endpoint = $config['endpoint'][$environment];
	$this->set_folder_locations();

	$current_date = date("Y-m-d");
	$this->log_file = $log_file = $this->folders['tmp'] . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "$current_date.log";

	if (!isset($config['endpoint'][$environment])) {
	  $time = date('G:i:s');
	  $log = "----$time----" . PHP_EOL;

	  $eol = PHP_EOL;
	  print $print = "O arquivo config.ini nao contem a instrucao:$eol [endpoint] $eol $environment = URI$eol";
	  $log .= $print;

	  file_put_contents($log_file, $log, FILE_APPEND);
	  exit();
	}

	$this->login();
  }
  
  /**
   * Sends a test request to the endpoint.
   */
  public function check_endpoint() {
	$uri = $this->endpoint;

	$request = Request::get($uri)
	  ->send();

	$this->response_code($request, $uri, TRUE);
	print "Endpoint $uri esta acessivel." . PHP_EOL;
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
   * @param Bool $print_success_msg
   *   Whether or not this method should print out a success message when
   *   the response is successful.
   */
  private function response_code($response, $uri, $print_success_msg = FALSE) {
	$code = $response->code;

	if ($code == 200) {
	  if ($print_success_msg) {
		print "SUCESSO!!!" . PHP_EOL;
	  }
	}
	else {
	  $body = $response->raw_body;
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

		file_put_contents($log_file, $log, FILE_APPEND);
		exit();
	  }
	  file_put_contents($log_file, $log, FILE_APPEND);
	  exit();
	}
  }

  /**
   * Loads folder paths from config.ini.
   */
  private function set_folder_locations() {
	foreach($this->config['pastas'] as $folder => $folder_path) {
	  // Make sure the correct OS directory separator is gonna be in place.
	  $folder_path = str_replace('/', DIRECTORY_SEPARATOR, $folder_path);

	  // Set this application root path as default root path for this folder.
	  $this->folders[$folder] = str_replace('%app%', $this->root_folder, $folder_path);
	}
  }

  /**
   * Logs in the merchant user.
   */
  private function login() {
	$session_file = $this->folders['tmp'] . DIRECTORY_SEPARATOR . ".session";
	$token_file = $this->folders['tmp'] . DIRECTORY_SEPARATOR . ".token";

	if (file_exists($session_file) && file_exists($token_file)) {
	  $this->session = file_get_contents($session_file);
	  $this->token = file_get_contents($token_file);
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

	  $this->response_code($request, $uri);

	  $sessid = $request->body->sessid;
	  $session_name = $request->body->session_name;

	  // @TODO: Get merchant uid.
	  $merchant_uid = $this->merchant_uid = '';

	  $this->session = "$session_name=$sessid";
	  $this->token = $request->body->token;
	  file_put_contents($session_file, $this->session);
	  file_put_contents($token_file, $this->token);
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
   * @param String $method
   *   Valid values are: get, post and put.
   *
   * @param String $query
   *   URL Item + Query String value. Format /item_value.json?param1=value1&param2=value2
   *
   * @param Bool $return_raw_data
   *  Whether or not to return the response as a PHP object or a Json string.
   *
   * @return String
   *   The Webservice response.
   */
  private function request($service, $body, $method, $query = '', $return_raw_data = FALSE, $print_success_msg = FALSE) {
	$endpoint = $this->endpoint;
	$service = $this->config['servicos'][$service];

	$uri = "$endpoint/$service" . $query;

	$request = Request::$method($uri)
	  ->sendsJson()
	  ->expectsJson()
	  ->addHeader('Cookie', $this->session)
	  ->addHeader('X-CSRF-Token', $this->token)
	  ->body($body)
	  ->send();

	$this->response_code($request, $uri, $print_success_msg);

	$this->response_body_object = $request->body;
	$this->response_body_json = $request->raw_body;

	if ($return_raw_data) {
	  return $request->raw_body . PHP_EOL;
	}

	return $request->body;
  }

  /**
   * Creates a new service item.
   *
   * @param String $service
   *  The service path.
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
   */
  public function update($service = 'produto') {
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

	return $this->request($service, $body, 'put', '/atualizar');
  }

  /**
   * Retrieves a single item from a service.
   *
   * @param String $item
   *   The item id which is gonna be retrieved from the service.
   *
   * @param Array $qs
   *   Key = Paramenter name, Value = Argument value.
   *
   * @param String $service
   *   The service path.
   */
  private function retrieve_service_item($item, $qs = array(), $service = 'produto') {
	$query_string = '';
	foreach ($qs as $param => $argument) {
	  $query_string .= "$param=$argument&";
	}

	if (!empty($query_string)) {
	  $query_string = "?$query_string";
	}

	$query = "/$item.json" . $query_string;

	return $this->request($service, '', 'get', $query);
  }

  /**
   * Retrieves a single product based on its product_id field.
   *
   * @param String $product_id
   *   The Product ID value set at the NortaoX application.
   *
   * @return Json
   *   The product object in Json format.
   */
  public function get_product_by_product_id($product_id) {
	return $this->retrieve_service_item($product_id);
  }

  /**
   * Retrieves a single product based on its sku field.
   *
   * @param String $sku
   *   The SKU value set at the NortaoX application.
   *
   * @return Json
   *   The product object in Json format.
   */
  public function get_product_by_sku($sku) {
	$qs = array('campo' => 'sku');
	return $this->retrieve_service_item($sku, $qs);
  }

  /**
   * Retrieves a single product based on its cod_produto_erp field.
   *
   * @param String $erp_prod_id
   *   The product id value set at the ERP application.
   *
   * @return Json
   *   The product object in Json format.
   */
  public function get_product_by_cod_produto_erp($cod_produto_erp) {
	$qs = array('campo' => 'cod_produto_erp');
	return $this->retrieve_service_item($cod_produto_erp, $qs);
  }

  /**
   * Retrieves a list of cities which NortaoX is or will trade.
   *
   * @param String $erp_prod_id
   *   The product id value set at the ERP application.
   *
   * @return Json
   *   The product object in Json format containg the following values for:
   *   cod_cidade, nome and status.
   */
  public function get_cities() {
	return $this->request('cidades', '', 'get');
  }
}
