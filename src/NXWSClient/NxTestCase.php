<?php
namespace NXWSClient;

use PHPUnit_Framework_TestCase,
    Exception,
	stdclass;

class NxTestCase extends PHPUnit_Framework_TestCase {
  public $unlockObjSettings = array(),
		 $unlockObj;

  public function unlockObj($obj) {
	$this->unlockObj = $obj;
  }

  public function unlockSetMethod($methodName) {
	$this->unlockObjSettings['type'] = 'method';
	$this->unlockObjSettings['memberName'] = $methodName;
  }

  public function unlockSetMethodArgs($args = array()) {
	$this->unlockObjSettings['method_args'] = $args;
  }

  public function unlockSetProperty($propertyName) {
	$this->unlockObjSettings['type'] = 'property';
	$this->unlockObjSettings['memberName'] = $propertyName;
  }

  public function unlockSetPropertyAction($action) {
	$this->unlockObjSettings['property_action'] = $action;
  }

  public function unlockSetPropertyNewValue($newValue) {
	$this->unlockObjSettings['property_newValue'] = $newValue;
  }

  /**
   * Accesses private members of an instantiated object.
   *
   * @param Object $obj
   *   The instantiated object.
   *
   * @param Array $settings
   *   Possible array key|value pairs are:
   *     type: (required) Expects either "method" or "property".
   *     memberName: (required) The name of the property or the method.
   *     method_args: An array of arguments which will be passed down to the
   *                  method.
   *     property_action: Expects either set or return.
   *                      set will set a new value for the property.
   *                      return will just return the current value of the property.
   *     property_newValue: Required when the value of property_action is "set".
   */
  public function unlock() {
	$obj = $this->unlockObj;
	$settings = $this->unlockObjSettings;

	foreach($settings as $setting_name => $setting_value) {
	  ${$setting_name} = $setting_value;
	}

	if (empty($type)) {
	  throw new Exception('You must send a value for type. Expects either method or property.');
	}

	if (empty($memberName)) {
	  throw new Exception('You must send a value for memberName. That is the name of the method or property.');
	}

	$method_args = (isset($method_args)) ? $method_args : array();
	$property_newValue = (isset($property_newValue)) ? $property_newValue : '';

	switch($type) {
	  case 'method':
		if (is_callable(array($obj, $memberName))) {
		  throw new Exception("$memberName is a public method. You can call it directly.");
		}

		$unlock = function(&$obj, $memberName, $method_args) {
		  if (!empty($method_args)) {
			call_user_func_array(array($obj, $memberName), $method_args);
		  }
		  else {
			$obj->{$memberName}();
		  }
		};

		$unlock = \Closure::bind($unlock, null, $obj);
		$unlock($obj, $memberName, $method_args);
	  break;
	  case 'property':
		if (empty($property_action) &&
			($property_action != 'return' ||
			$property_action != 'set')) {
		  throw new Exception('You must send a value for property_action. Expects either return or set.');
		}

		$unlock = function(&$obj, $memberName, $property_action, $property_newValue) {
		  switch($property_action) {
			case 'return':
			  return $obj->{$memberName};
			break;
			case 'set':
			  if (empty($property_newValue)) {
				throw new Exception('You must send a value for property_newValue');
			  }
			  $obj->{$memberName} = $property_newValue;
			break;
		  }
		};
	
		$unlock = \Closure::bind($unlock, null, $obj);
		switch($property_action) {
		  case 'return':
			$obj = $unlock($obj, $memberName, $property_action, $property_newValue);
		  break;
		  case 'set':
			$unlock($obj, $memberName, $property_action, $property_newValue);
		  break;
		}
	  break;
	}
	$this->unlockObj = $obj;
  }

  public function nx_product_create_success_response($nx) {
	$nx->container['request'] = $nx->container->factory(function ($c) {
	  $response = new stdclass();
	  switch($c['request_method']) {
		case 'get':
		  $response->code = 404;
		  $response->raw_body = '["Product not found"]';
		break;
		case 'post':
		  $response->code = 200;
		  $response->raw_body = '{"sku":"87-35-73","nome":"product test new product 1","status":"1","product_id":"73","created":"1421736406","changed":"1421848787","preco":"10760","preco_velho":"11000","qtde_em_estoque":"9999999.99","cod_cidade":"35","cod_produto_erp":"520","localizacao_fisica":"prateleira","preco_formatado":"R$107,60","preco_velho_formatado":"R$110,00"}';
		break;
	  }

	  return $response;
	});
  }

  public function nx_product_create_fail_response($nx) {
	$nx->container['request'] = $nx->container->factory(function ($c) {
	  $response = new stdclass();
	  switch($c['request_method']) {
		case 'get':
		  $response->code = 404;
		  $response->raw_body = '["Product not found"]';
		break;
		case 'post':
		  $response->code = 400;
		  $response->raw_body = '["Foo issue."]';
		break;
	  }

	  return $response;
	});
  }

  public function nx_product_update_success_response($nx) {
	$nx->container['request'] = $nx->container->factory(function ($c) {
	  $response = new stdclass();
	  switch($c['request_method']) {
		case 'get':
		  $response->code = 200;
		  $response->raw_body = '{"sku":"87-35-73","nome":"product test new product 1","status":"1","product_id":"73","created":"1421736406","changed":"1421848787","preco":"10760","preco_velho":"11000","qtde_em_estoque":"9999999.99","cod_cidade":"35","cod_produto_erp":"520","localizacao_fisica":"prateleira","preco_formatado":"R$107,60","preco_velho_formatado":"R$110,00"}';
		break;
		case 'put':
		  $response->code = 200;
		  $response->raw_body = '{"sku":"87-35-73","nome":"product test new product 1","status":"1","product_id":"73","created":"1421736406","changed":"1421848787","preco":"10760","preco_velho":"11000","qtde_em_estoque":"9999999.99","cod_cidade":"35","cod_produto_erp":"520","localizacao_fisica":"prateleira","preco_formatado":"R$107,60","preco_velho_formatado":"R$110,00"}';
		break;
	  }

	  return $response;
	});
  }
}
