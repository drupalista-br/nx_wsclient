<?php
namespace NXWSClient;
use \Httpful\Request;

class nx {
  public $session,
		 $config,
		 $endpoint,
		 $root_folder,
		 $folders;

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
	  exit("O arquivo $config_file nao existe\n");
	}

	$this->config = $config = parse_ini_file($config_file, true);
	$this->endpoint = $uri = $config['endpoints'][$environment];

	if (!isset($config['endpoints'][$environment])) {
	  exit("O arquivo config.ini nao contem a instrucao:\n[endpoints]\n$environment = URI\n");
	}

	if ($check_endpoint) {
	  $request = Request::get($uri)
		->send();
  
	  $code = $request->code;
	  if ($code != 200) {
		exit("Endpoint $uri NAO esta acessivel. Retornou o codigo de erro $code.\n");
	  }
	  exit("Endpoint $uri esta acessivel.\n");
	}

	$this->set_folder_locations();
	$this->login();
  }

  private function set_folder_locations() {
	foreach($this->config['pastas'] as $folder_user => $folder_path) {
	  $path = str_replace('%app%', $this->root_folder, $folder_path);
	  $this->folders[$folder_user] = str_replace('/', DIRECTORY_SEPARATOR, $path);
	}
  }

  private function login() {
	$session_file = $this->folders['tmp'] . DIRECTORY_SEPARATOR . ".session";
	$token_file = $this->folders['tmp'] . DIRECTORY_SEPARATOR . ".token";

	if (file_exists($session_file)) {
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

	  $sessid = $request->body->sessid;
	  $session_name = $request->body->session_name;

	  $this->session = "$session_name=$sessid";
	  $this->token = $request->body->token;
	  file_put_contents($session_file, $this->session);
	  file_put_contents($token_file, $this->token);
	}
  }

  private function request($service, $body, $method) {
	$endpoint = $this->endpoint;
	$service = $this->config['servicos'][$service];

	$uri = "$endpoint/$service";

	$request = Request::$method($uri)
	  ->sendsJson()
	  ->addHeader('Cookie', $this->session)
	  ->addHeader('X-CSRF-Token', $this->token)
	  ->body($body)
	  ->send();
	print_r($request->body);
  }

  public function create($service = 'produto') {
	$body = array(
	  'nome' => 'my new product 2',
	  'preco' => '5068',
	  'preco_velho' => '5068',
	  'qtde_em_estoque' => '99885.00',
	  'cod_cidade' => 35,
	  // Opcional.
	  'localizacao_fisica' => 'prateleira',
	  // Opcional.
	  'cod_produto_erp' => '123',
	);

	$this->request($service, $body, 'post');
  }

  public function update() {

  }

  public function retrieve() {

  }
}
