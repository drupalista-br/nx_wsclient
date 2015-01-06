<?php
use \NXWSClient\argv;

require_once "vendor/autoload.php";


exit();

$expected_arguments = array(
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
	  'product_id' => 'Exemplo: php run.php consultar produto product_id VALOR_DO_PRODUCT_ID DESTINO',
	  'sku' => 'Exemplo: php run.php consultar produto sku VALOR_DO_SKU DESTINO',
	  'cod_produto_erp' => 'Exemplo: php run.php consultar produto cod_produto_erp VALOR_DO_COD_PRODUTO_ERP DESTINO',
	),
	'pedido' => array(
	  'no' => 'Exemplo: php run.php consultar pedido no NUMERO_DO_PEDIDO DESTINO',
	  'entre' => 'Exemplo: php run.php consultar pedido entre TIMESTAMP_INICIO TIMESTAMP_FIM DESTINO',
	),
	'cidades' => 'Exemplo: php run.php consultar cidades DESTINO',
  ),
  'testar' => 'Exemplo: php run.php testar | Verifica se o Webservice está acessível.',
);

$command_string = '';
$command_params = array();
$command_last = FALSE;

$validation = $expected_arguments;

foreach ($argv as $delta => $argument_value) {
  if ($delta != 0) {
	$argument_value = strtolower(trim($argument_value));

	if (isset($validation[$argument_value])) {
	  if (is_array($validation[$argument_value])) {
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
  print "INSTRUCAO IMCOMPLETA. INSTRUCOES ESPERADAS SAO:\n";
  print_r($validation);
  exit();
}

$nx = new nx(FALSE, TRUE);

$number_params_sent = count($command_params);
switch($command_string) {
  case 'help':
	$number_params_expected = 1;
	
  break;
  case 'config ambiente':
	$number_params_expected = 1;
	
  break;
  case 'config pastas dados':
	$number_params_expected = 1;
	
  break;
  case 'config pastas tmp':
	$number_params_expected = 1;

  break;
  case 'config credenciais username':
	$number_params_expected = 1;

  break;
  case 'config credenciais password':
	$number_params_expected = 1;

  break;
  case 'config mostrar':
	$number_params_expected = 1;

  break;
  case 'scaniar produto':
	$number_params_expected = 0;

  break;
  case 'consultar produto product_id':
	$number_params_expected = 2;

  break;
  case 'consultar produto sku':
	$number_params_expected = 2;

  break;
  case 'consultar produto cod_produto_erp':
	$number_params_expected = 2;

  break;
  case 'consultar pedido no':
	$number_params_expected = 2;
	
  break;
  case 'consultar pedido entre':
	$number_params_expected = 3;
	
  break;
  case 'consultar cidades':
	$number_params_expected = 1;
	
  break;
  case 'testar':
	$number_params_expected = 0;

  break;
}

function check_command_params($expected) {
  
}

/*$config = parse_ini_file('config.ini', TRUE);

$writer = new Ini();
$writer->toFile('test.ini', $config);*/

$test = new nx(FALSE, TRUE);

$qs = array('campo' => 'sku');

print_r( $test->get_cities() );

