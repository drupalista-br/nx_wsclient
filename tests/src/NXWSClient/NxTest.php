<?php
namespace NXWSClient\Test;

use NXWSClient\tools,
	NXWSClient\nx,
	NXWSClient\NxTestCase,
	Httpful\Request,
	org\bovigo\vfs\vfsStream,
	Pimple\Container,
	Zend\Config\Reader\Ini as IniReader,
	stdclass;

$pathinfo = pathinfo(__DIR__);
$root_folder = dirname(dirname($pathinfo['dirname']));

require_once "$root_folder/vendor/autoload.php";

class NxTest extends NxTestCase {
  public $nx;
  public $response_login;
  
  public function setUp() {
	parent::setUp();
	vfsStream::setup('home');

	$this->nx = new nx();
	$this->nx->root_folder = vfsStream::url('home');
	// See issue at https://github.com/mikey179/vfsStream/issues/44
	$this->nx->container['ini_writer_lock'] = FALSE;

	// Make sure internet is accessible even when there is no internet.
	$this->nx->container['internet_connection_google'] = 'localhost';
	$this->nx->container['internet_connection_nortaox'] = 'localhost';

	$this->nx->container['config_producao_uri'] = 'http://loja.nortaox.local/api';

	$this->response_login = new stdclass();
	$this->response_login->raw_body = '{"sessid":"t8SphFm_tI68qeqJPXzyAaOcxLvsOKV11YGP4W30eLk","session_name":"SESSdd678b24d9cb922c1a48db93fe3ce2e7","token":"kczgr5yuI0JkfFus9MrIWWHMesabJiE6IUQLYwXpFi4","user":{"uid":"87","name":"Francisco Luz","mail":"drupalista.com.br@gmail.com","theme":"","signature":"","signature_format":"filtered_html","created":"1415747279","access":"1421795869","login":1421795893,"status":"1","timezone":"America/Cuiaba","language":"pt-br","picture":{"fid":"286","uid":"0","filename":"picture-87-1415747279.jpg","uri":"public://pictures/picture-87-1415747279.jpg","filemime":"application/octet-stream","filesize":"9007","status":"1","timestamp":"1415747279","type":"default","rdf_mapping":[],"url":"http://loja.nortaox.local/sites/loja.nortaox.com/files/pictures/picture-87-1415747279.jpg"},"data":{"hybridauth":{"identifier":"114344118552170824273","webSiteURL":null,"profileURL":"https://plus.google.com/114344118552170824273","photoURL":"https://lh6.googleusercontent.com/-nlf7IN6DkvY/AAAAAAAAAAI/AAAAAAAAAR0/KEoIJgf_PgI/photo.jpg?sz=200","displayName":"Francisco Luz","description":"","firstName":"Francisco","lastName":"Luz","gender":"other","language":"en","age":"","birthDay":0,"birthMonth":0,"birthYear":0,"email":"drupalista.com.br@gmail.com","emailVerified":"drupalista.com.br@gmail.com","phone":"","address":"Paranagu\u00e1, PR, Brazil","country":"","region":"","city":"Paranagu\u00e1, PR, Brazil","zip":"","provider":"Google"},"contact":1,"ckeditor_default":"t","ckeditor_show_toggle":"t","ckeditor_width":"100%","ckeditor_lang":"en","ckeditor_auto_lang":"t","overlay":1},"roles":{"2":"authenticated user","4":"merchant"},"rdf_mapping":{"rdftype":["sioc:UserAccount"],"name":{"predicates":["foaf:name"]},"homepage":{"predicates":["foaf:page"],"type":"rel"}},"realname":"Francisco Luz"}}';
	$this->response_login->code = 200;
	$this->response_login->body = new stdclass();
	$this->response_login->body->sessid = 't8SphFm_tI68qeqJPXzyAaOcxLvsOKV11YGP4W30eLk';
	$this->response_login->body->session_name = 'SESSdd678b24d9cb922c1a48db93fe3ce2e7';
	$this->response_login->body->token = 'kczgr5yuI0JkfFus9MrIWWHMesabJiE6IUQLYwXpFi4';

  }

  public function testBootstrapMethodNoSessionFileSetAndInternetConnectionOk() {
	$nx = $this->nx;

	$this->unlockObj = $nx;
	$this->unlockSetProperty('merchant_login');
	$this->unlockSetPropertyAction('return');
	$this->unlock();
	
	$this->assertTrue(empty($this->unlockObj['session']));
	$this->assertTrue(empty($this->unlockObj['token']));


	$nx->container['request_login'] = function ($c) {
	  return $this->response_login;
	};
	$nx->bootstrap();

	$this->unlockObj = $nx;
	$this->unlockSetProperty('merchant_login');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$this->assertFalse(empty($this->unlockObj['session']));
	$this->assertFalse(empty($this->unlockObj['token']));
  }

  public function testBootstrapMethodFromSessionFileSetAndInternetConnectionOk() {
	// Create the session file.
	$nx = $this->nx;
	$nx->bootstrap();

	$this->unlockObj = $nx;
	$this->unlockSetProperty('merchant_login');
	$this->unlockSetPropertyAction('return');
	$this->unlock();

	$session_before = $this->unlockObj['session'];
	$token_before = $this->unlockObj['token'];

	$this->assertFalse(empty($this->unlockObj['session']));
	$this->assertFalse(empty($this->unlockObj['token']));

	// Use session file.
	$nx->bootstrap();

	$this->assertTrue($session_before == $this->unlockObj['session']);
	$this->assertTrue($token_before == $this->unlockObj['token']);
  }

  /*function testLogMethod() {
	$output = "A internet esta acessivel e o website da NortaoX.com esta responsivo." . PHP_EOL;
	$output .= "Endpoint http://loja.nortaox.local/api esta acessivel." . PHP_EOL;
	$output .= "As pastas dados, tmp e suas subpastas foram criadas com sucesso." . PHP_EOL;
	$output .= "O servidor do gmail respondeu Ok. As credencais do email nortaox.webservice.client@gmail.com sao validas." . PHP_EOL;

	$this->expectOutPutString($output);

	$root_folder = $this->root_folder;

	$date = new \DateTime();
	$date_gis = $date->format('G:i:s');
	$date_ymd = "logtest";
	$log_file = "$root_folder/tmp/logs/$date_ymd.log";

	$this->assertFalse(file_exists($log_file));

	$nx = new nx();	
	$nx->container['date_gis'] = $date_gis;
	$nx->container['date_time_gis'] = function($c) {
	  return $c['date_gis'];
	};

	$container['date_format'] = "Y-m-d";
	$container['date_time'] = function($c) {
	  $date = new DateTime();
	  return $date->format($c['date_format']);
	};

	$nx->container['date_ymd'] = $date_ymd;
	$nx->container['date_time_ymd'] = function($c) {
	  return $c['date_ymd'];
	};
	$nx->check(TRUE);

	$msg_1 = "1. PHPUnit." . PHP_EOL;
	$msg_2 = "2. PHPUnit." . PHP_EOL;

	$this->unlockObj = $nx;
	$this->unlockSetMethod('log');
	$this->unlockSetMethodArgs(array($msg_1));
	$this->unlock();

	$this->unlockObj = $nx;
	$this->unlockSetMethod('log');
	$this->unlockSetMethodArgs(array($msg_2));
	$this->unlock();

	$file_content_prediction = "----$date_gis----" . PHP_EOL;
	$file_content_prediction .= $msg_1;
	$file_content_prediction .= "----$date_gis----" . PHP_EOL;
	$file_content_prediction .= $msg_2;

	$file_content = file_get_contents($log_file);

	$this->assertTrue(file_exists($log_file));
	$this->assertTrue($file_content_prediction == $file_content);
	unlink($log_file);
  }*/

  /*function testGetCitiesMethod() {
	$root_folder = $this->root_folder;

	if (file_exists("$root_folder/dados/consulta/cidades.txt")) {
	  unlink("$root_folder/dados/consulta/cidades.txt");
	}

	$this->assertFalse(file_exists("$root_folder/dados/consulta/cidades.txt"));

	$nx = new nx();
	$nx->bootstrap(TRUE);
	$nx->get_cities();

	$this->assertTrue(file_exists("$root_folder/dados/consulta/cidades.txt"));

	$reader = new IniReader();
	$cities = $reader->fromFile("$root_folder/dados/consulta/cidades.txt");

	$this->assertTrue(isset($cities[0]['cod_cidade']));
	$this->assertTrue(isset($cities[0]['nome']));
	$this->assertTrue(isset($cities[0]['status']));
  }*/
}
