<?php
namespace NXWSClient;

use \Zend\Config\Writer\Ini;
use \NXWSClient\nx;

class argv {
  private $expected_arguments = array(
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
  ),
  $command_string,
  $command_params,
  $command_params_qty;

  public function __construct() {
	$command_string = '';
	$command_params = array();
	$command_last = FALSE;

	$validation = $this->expected_arguments;
	
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
	$this->command_string = $command_string;
	$this->command_params = $command_params;
	$this->command_params_qty = count($command_params);
  }

  public function run() {
	$nx = new nx(FALSE, TRUE);

	switch($this->command_string) {
	  case 'help':
		$this->check_command_params();
		
	  break;
	  case 'config ambiente':
		$this->check_command_params();
		
	  break;
	  case 'config pastas dados':
		$this->check_command_params();
		
	  break;
	  case 'config pastas tmp':
		$this->check_command_params();
	
	  break;
	  case 'config credenciais username':
		$this->check_command_params();
	
	  break;
	  case 'config credenciais password':
		$this->check_command_params();
	
	  break;
	  case 'config mostrar':
		$this->check_command_params();
	
	  break;
	  case 'scaniar produto':
		$this->check_command_params(0);
	
	  break;
	  case 'consultar produto product_id':
		$this->check_command_params(1, TRUE);
	
	  break;
	  case 'consultar produto sku':
		$this->check_command_params(1, TRUE);
	
	  break;
	  case 'consultar produto cod_produto_erp':
		$this->check_command_params(1, TRUE);
	
	  break;
	  case 'consultar pedido no':
		$this->check_command_params(1, TRUE);
		
	  break;
	  case 'consultar pedido entre':
		$this->check_command_params(2, TRUE);
		
	  break;
	  case 'consultar cidades':
		$this->check_command_params(0, TRUE);
		
	  break;
	  case 'testar':
		$this->check_command_params(0);
	
	  break;
	}
  }

  private function check_command_params($expected = 1, $consulta = FALSE) {
	$command_params = $this->command_params;
	$command_params_qty = $this->command_params_qty;
	
  }
}
