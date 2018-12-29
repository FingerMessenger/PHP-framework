<?php

namespace App\Framework;

function run()
{
	$request = new Request();
	Pool::set('app_request', $request);

	$response = new Response();
	Pool::set('app_response', $response);

	$file = __DIR__.'/Module/'.$request->getUri(0, "Index").'/Controller/'.$request->getUri(1, "Index").'Controller.php';
	if ( ! file_exists($file)) {
		exit("page not found");
	}

	require $file;
	$ctlName = "\\App\Module\\".$request->getUri(0, "Index")."\\Controller\\".$request->getUri(1, "Index").'Controller';
	if (!class_exists($ctlName)) {
		exit("page not found");
	}
	$actName = $request->getUri(2, "Index");
	$app = new $ctlName();
				
	if (!method_exists($app, $actName)) {
		exit("page not found");
	}

	$app->request = &$request;
	$app->response = &$response;

	try {
		$app->$actName();
	} catch (Exception $e) {
		$e->solve();
	}
}

class Config
{
	protected $env = 'product';
	protected $config = array();

	public function __construct()
	{
		if (defined("ENV")) {
			$this->env = ENV;
		}
	}
	
	public function get($key = '')
	{
		if (isset($this->config[$this->env][$key])) {
			return $this->config[$this->env][$key];
		}
		return null;
	}
}

class Controller
{
	use Loader;

	public function view($data = array(), $output = true, $layout = 'default')
	{
		$view = Pool::get("app_view");
		if (!$view) {
			$view = new View();
			Pool::set("app_view", $view);
		}
		
		$view->setLayout(__DIR__.'/Module/'.$this->request->getUri(0, "Index").'/View/layout/'.$layout.'.php');
		$view->setView(__DIR__.'/Module/'.$this->request->getUri(0, "Index").'/View/'.$this->request->getUri(1, "Index").'/'.$this->request->getUri(2, "Index").'.php');
		$content = $view->render($data);
		
		$this->response->setHeaders(array(
			"Content-type:text/html;charset=utf-8"
		));
		$this->response->setBody($content);

		if ($output) {
			$this->response->flush();
		}
	}
}

trait  Loader
{
    public function loadModel($modelName = '')
    {
        if (!$modelName) {
            return;
		}
		
		$mName = $modelName . "Model";
		if (isset($this->$mName)) {
			return;
		}

		$file = __DIR__.'/Model/'.$modelName.'Model.php';
		if (!file_exists($file)) {
			return;
		}

		require $file;
        $class = "\\App\\Model\\".$modelName."Model";
        if (!class_exists($class)) {
            return;
        }

        $model = new $class();
        Pool::set('model_'.strtolower($modelName), $model);

        $this->$mName = $model;
	}
	
	public function loadModels($modelNames = array())
	{
		foreach ($modelNames as $modelName) {
			$this->loadModel($modelName);
		}
	}

    public function loadConfig($configName = '')
    {
        if (!$configName) {
            return null;
        }

        $config = Pool::get('config_'.strtolower($configName));
        if ($config) {
            return $config;
		}
		
		$file = __DIR__.'/Config/'.$configName.'.php';
		if (!file_exists($file)) {
			return null;
		}

		require $file;
        $class = "\\App\\Config\\".$configName;
        if (!class_exists($class)) {
            return null;
        }

        $config = new $class();
        Pool::set('config_'.strtolower($configName), $config);

        return $config;
    }
}

class Model
{
	use Loader;
}

class Pool
{
	static $pool = array();
	
	static public function set($name, &$obj)
	{
		if (!$name || !$obj || isset(self::$pool[$name])){
			return false;
		}

		self::$pool[$name] = $obj;
		return true;
	}
	
	static public function get($name)
	{
		if (!isset(self::$pool[$name])){
			return null;
		}
		return self::$pool[$name];
	}
}

class Request
{
	private $uriArr = array();

	public function __construct()
	{
		if ($this->isCli()){
			$uri = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : "";
		}else{
			$uri = trim(str_replace($_SERVER['SCRIPT_NAME'], "", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), "/");
		}

		$this->uriArr = explode("/", $uri);
	}


	public function getUri($index, $default = '')
	{
		return isset($this->uriArr[$index]) && $this->uriArr[$index] ? $this->uriArr[$index] : $default;
	}

	public function isAjax()
	{
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
			return true;
		}else{
			return false;
		}
	}

	public function post($name = null, $default = null)
	{
		if ( ! $name) {
			return $data;
		}

		return isset($_POST[$name]) ? $_POST[$name] : $default;
	}

	public function get($name = null, $default = null)
	{
		if ( ! $name) {
			return $data;
		}

		return isset($_GET[$name]) ? $_GET[$name] : $default;
	}

	public function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	public function is($method)
	{
		if (strcmp($this->getMethod(), $method) == 0){
			return true;
		}
		return false;
	}

	public function getServer($name)
	{
		return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
	}

	public function host()
	{
		return $this->getProtocol()."://".$_SERVER['HTTP_HOST'];
	}

	public function getClientIp()
	{
		return $_SERVER['REMOTE_ADDR'];
	}

	public function isCli()
	{
		return PHP_SAPI == 'cli' ? true : false;
	}

	public function getProtocol()
	{
		return empty($_SERVER['HTTPS']) ? "http" : "https";
	}
}

class Response
{
	private $headers = array();

	private $body = "";

	public function setHeaders($headers = array())
	{
		$this->headers = $headers;
	}

	public function setBody($body = "")
	{
		$this->body = $body;
	}

	public function getHeaders()
	{
		return $this->headers;
	}

	public function getBody()
	{
		return $this->body;
	}

	public function flush()
	{
		foreach ($this->headers as $header) {
			header($header);
		}
		echo $this->body;
	}

	public function json($data = array())
	{
		header('Content-type:text/json');
		
		echo json_encode($data);
		
		exit();
	}
	
	public function xml($data = array())
	{
		header("Content-type:text/xml;charset=utf-8");
		
		$xml = "<xml>";
		foreach ($data as $key=>$val) {
			if (is_numeric($val)) {
				$xml .= "<".$key.">".$val."</".$key.">";
			} else {
				$xml .= "<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml .= "</xml>";
		echo $xml;

		exit();
	}
}

class View
{
	private $layout = '';

	private $view = '';

	public function setLayout($fileName = '')
	{
		$this->layout = $fileName;
	}

	public function setView($fileName = '')
	{
		$this->view = $fileName;
	}

	public function render($data = array())
	{
		$content = $this->parse($this->view, $data);

		if ($this->layout) {
			$layout = $this->parse($this->layout, $data);
			$content = str_replace('{{CONTENT}}', $content, $layout);
		}

		return $content;
	}
	
	private function parse($file = ‘’, $data = array())
	{
		if ($data) {
			extract($data);
		}

		if (!file_exists($file)) {
			return '';
		}
		
		ob_start();
		include($file);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}

abstract class Exception extends \Exception
{
	public function __construct(string $message, int $code)
	{
		parent::__construct($message, $code);
	}

	abstract public function solve();
}