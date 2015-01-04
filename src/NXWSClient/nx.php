<?php
namespace NXWSClient;
use \Httpful\Request;

class nx {
  public $session,
		 $config,
		 $endpoint,
		 $root_folder,
		 $folders,
		 $log_file,
		 $merchant_uid;

  /**
   * @param String $environment
   *   Expects either producao or sandbox. producao is default.
   *
   * @param Bool $check_endpoint
   *   Checks if endpoint is alive.
   */
  public function __construct($environment = 'producao', $check_endpoint = FALSE) {
	$root_folder = pathinfo(__DIR__);
	$root_folder = $this->root_folder = dirname($root_folder['dirname']);

	$config_file = $root_folder . DIRECTORY_SEPARATOR . "config.ini";
	if (!file_exists($config_file)) {
	  exit("O arquivo $config_file nao existe." . PHP_EOL);
	}

	$this->config = $config = parse_ini_file($config_file, TRUE);
	$this->endpoint = $uri = $config['endpoints'][$environment];
	$this->set_folder_locations();

	$current_date = date("Y-m-d");
	$this->log_file = $log_file = $this->folders['tmp'] . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "$current_date.log";

	if (!isset($config['endpoints'][$environment])) {
	  $time = date('G:i:s');
	  $log = "----$time----" . PHP_EOL;

	  $eol = PHP_EOL;
	  print $print = "O arquivo config.ini nao contem a instrucao:$eol [endpoints] $eol $environment = URI$eol";
	  $log .= $print;

	  file_put_contents($log_file, $log, FILE_APPEND);
	  exit();
	}

	if ($check_endpoint) {
	  $request = Request::get($uri)
		->send();

	  $this->response_code($request, $uri);
	  exit("Endpoint $uri esta acessivel." . PHP_EOL);
	}

	$this->login();
  }

  private function response_code($response, $uri, $quiet = FALSE) {
	$code = $response->code;

	if ($code == 200) {
	  if (!$quiet) {
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

  private function set_folder_locations() {
	foreach($this->config['pastas'] as $folder => $folder_path) {
	  // Make sure the correct OS directory separator is gonna be in place.
	  $folder_path = str_replace('/', DIRECTORY_SEPARATOR, $folder_path);

	  // Set this application root path as default root path for this folder.
	  $this->folders[$folder] = str_replace('%app%', $this->root_folder, $folder_path);
	}
  }

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
  private function request($service, $body, $method, $query = '', $return_raw_data = FALSE) {
	$endpoint = $this->endpoint;
	$service = $this->config['servicos'][$service];

	$uri = "$endpoint/$service" . $query;

	$request = Request::$method($uri)
	  ->sendsJson()
	  ->addHeader('Cookie', $this->session)
	  ->addHeader('X-CSRF-Token', $this->token)
	  ->body($body)
	  ->send();

	$quiet = FALSE;
	if ($method == 'get') {
	  // Don't return anything if request is succesful.
	  $quiet = TRUE;
	}
	$this->response_code($request, $uri, $quiet);

	if ($return_raw_data) {
	  return $request->raw_body . PHP_EOL;
	}

	return $request->body;
  }

  public function create($service = 'produto') {
	$body = array(
	  'nome' => 'my new product 15',
	  'preco' => 5068,
	  'preco_velho' => 7068,
	  'qtde_em_estoque' => 88885.01,
	  'cod_cidade' => 135,
	  // Opcional.
	  'localizacao_fisica' => 'prateleira',
	  // Opcional.
	  'cod_produto_erp' => '125',
	);

	return $this->request($service, $body, 'post');
  }

  public function update($service = 'produto') {

  }

  public function retrieve($item, $qs = array(), $service = 'produto') {
	$query_string = '';
	foreach ($qs as $param => $argument) {
	  $query_string .= "$param=$argument&";
	}

	if (!empty($query_string)) {
	  $query_string = "?$query_string";
	}

	$query = "/$item.json" . $query_string;

	return $this->request($service, '', 'get', $query, true);
  }
}
