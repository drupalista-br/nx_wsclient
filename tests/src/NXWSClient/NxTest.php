<?php
namespace NXWSClient\Test;

use NXWSClient\nx;
use org\bovigo\vfs\vfsStream;
use Pimple\Container;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends \PHPUnit_Framework_TestCase {

  function testBootstrapConfigMethodConfigIniDoesNotExist() {
	/*//$nx = new nx();
	$nx = $this->getMockBuilder('NXWSClient\nx')
	  //->setMethods(array('bootstrap_config'))
	  ->getMock();

	$nx->method('halt')
	  ->willReturn('foo');*/

	$nx = new nx();
	$this->setExpectedException('\Exception');
	$method_unlock = array(
	  'type' => 'method',
	  'memberName' => 'bootstrap_config',
	);
	$this->unlock($nx, $method_unlock);
  }

  function testRootFolderPropertyIsSetByBootstrapRootFolderMethod() {
	$nx = new nx();

	$root_folder_unlock = array(
	  'type' => 'property',
	  'memberName' => 'root_folder',
	  'property_action' => 'return',
	);
	$root_folder = $this->unlock($nx, $root_folder_unlock);
	$this->assertFalse(is_dir($root_folder));

	$method_unlock = array(
	  'type' => 'method',
	  'memberName' => 'bootstrap_root_folder',
	);
	$this->unlock($nx, $method_unlock);

	$root_folder = $this->unlock($nx, $root_folder_unlock);

	$this->assertTrue(is_dir($root_folder));
  }

  function testBootstrapConfigMethodLoadsTheConfigFile() {
	$nx = new nx();

	$method_unlock = array(
	  'type' => 'method',
	  'memberName' => 'bootstrap_root_folder',
	);
	$this->unlock($nx, $method_unlock);

	$config_unlock = array(
	  'type' => 'property',
	  'memberName' => 'config',
	  'property_action' => 'return',
	);
	$config = $this->unlock($nx, $config_unlock);

	$this->assertTrue(empty($config));

	$method_unlock['memberName'] = 'bootstrap_config';

	$this->unlock($nx, $method_unlock);
	$config = $this->unlock($nx, $config_unlock);

	$this->assertTrue(isset($config['ambiente']));
	$this->assertTrue(is_array($config['endpoint']));
	$this->assertTrue(is_array($config['servicos']));
	$this->assertTrue(is_array($config['pastas']));
	$this->assertTrue(is_array($config['credenciais']));
  }

  
  
  /**
   * Accesses private members of an instantiated object.
   *
   * @param Object $obj
   *   The instantiated object.
   *
   * @param Array $settings
   *   Possible array keys are:
   *     type: (required) Expects either method or property.
   *     memberName: (required) The name of the property or the method.
   *     method_args: An array of arguments which will be passed down to the
   *                  method.
   *     property_action: Expects either set or return.
   *                      set will set a new value for the property.
   *                      return will just return the current value of the property.
   *     property_newValue: Required when the value of property_action is set.
   */
  public function unlock(&$obj, $settings = array()) {
	foreach($settings as $setting_name => $setting_value) {
	  ${$setting_name} = $setting_value;
	}

	if (empty($type)) {
	  throw new \Exception('You must send a value for type. Expects either method or property.');
	}

	if (empty($memberName)) {
	  throw new \Exception('You must send a value for memberName. That is the name of the method or property.');
	}

	$method_args = (isset($method_args)) ? $method_args : array();
	$property_newValue = (isset($property_newValue)) ? $property_newValue : '';

	switch($type) {
	  case 'method':
		$unlock = function(&$obj, $memberName, $method_args) {
		  if (!empty($method_args)) {
			return call_user_func_array(array($obj, $memberName), $method_args);
		  }

		  $obj->{$memberName}();
		};

		$unlock = \Closure::bind($unlock, null, $obj);
		$unlock($obj, $memberName, $method_args);
	  break;
	  case 'property':
		if (empty($property_action) &&
			($property_action != 'return' ||
			$property_action != 'set')) {
		  throw new \Exception('You must send a value for property_action. Expects either return or set.');
		}

		$unlock = function($obj, $memberName, $property_action, $property_newValue) {
		  switch($property_action) {
			case 'return':
			  return $obj->{$memberName};
			break;
			case 'set':
			  if (empty($property_newValue)) {
				throw new \Exception('You must send a value for property_newValue');
			  }
			  $obj->{$memberName} = $property_newValue;
			break;
		  }
		};
	
		$unlock = \Closure::bind($unlock, null, $obj);
		return $unlock($obj, $memberName, $property_action, $property_newValue);
	  break;
	}
  }
}
