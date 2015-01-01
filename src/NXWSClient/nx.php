<?php
namespace NXWSClient;
use \Httpful\Request;

class nx {
  public $config;
  public $endpoint;

  /**
   * @param String $environment
   *   Expects either producao or sandbox. producao is default.
   *
   * @param Bool $check_endpoint
   *   Checks if endpoint is alive.
   */
  public function __construct($environment = 'producao', $check_endpoint = FALSE) {
	$root_folder = pathinfo(__DIR__);
	$root_folder = dirname($root_folder['dirname']);

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
  }

  function login() {
	$endpoint = $this->endpoint;
	$login_service = $this->config['servicos']['login'];
	$username = $this->config['credenciais']['username'];
	$password = $this->config['credenciais']['password'];

	$uri = "$endpoint/$login_service";

	$request = Request::post($uri)
	  ->body("username=$username&password=$password")
	  ->expectsJson()
	  ->send();
	print_r($request);

	session

	//echo "{$request->body->name} joined GitHub on " . date('M jS', strtotime($request->body->{'created-at'})) ."\n";
  }
}
