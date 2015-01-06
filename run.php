<?php
use \NXWSClient\nx;
use \Zend\Config\Writer\Ini;

require_once "vendor/autoload.php";

$expected_arguments = array(
  'help' => 'Exemplo: php run.php help config',
  'mostrar' => 'Exemplo: php run.php mostrar config credenciais | Para ver as credenciais atuais.',
  'config' => array(
	'ambiente' => 'Padrão producao | Exemplo: php run.php config ambiente sandbox',
	'pastas' => array(
	  'dados' => 'Padrão %app%/dados | Informe dados=c:\caminho\dados para mudar.',
	  'tmp' => 'Padrão %app%/tmp | Informe tmp=c:\caminho\tmp para mudar.',
	),
	'credenciais' => array(
	  'username' => 'Exemplo: php run.php config credenciais username "Francisco Luz"',
	  'password' => 'Exemplo: php run.php config credenciais password minhasenha',
	),
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
	  'no' => 'Exemplo: php run.php consultar pedido no NUMERO_DO_PEDIDO',
	  'entre' => 'Exemplo: php run.php consultar pedido entre TIMESTAMP_INICIO TIMESTAMP_FIM',
	),
  ),
  'testar' => 'Exemplo: php run.php testar | Verifica se o Webservice está acessível.',
);

$command_string = '';
$command_values = array();
$command_last = FALSE;
$mostrar = FALSE;
$help = FALSE;

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
		// This is the last element on validation array.
		$command_string .= (empty($command_string)) ? $argument_value : ' ' . $argument_value;
		$command_last = $argument_value;
	  }
	}
	else {
	  $command_values[] = trim($argument_value);
	}
  }
}

if (!$command_last) {
  print "INSTRUCAO IMCOMPLETA. INSTRUCOES ESPERADAS SAO:\n";
  print_r($validation);
  exit();
}

exit();
/*$config = parse_ini_file('config.ini', TRUE);

$writer = new Ini();
$writer->toFile('test.ini', $config);*/

$test = new nx(FALSE, TRUE);

$qs = array('campo' => 'sku');

print_r( $test->get_cities() );

