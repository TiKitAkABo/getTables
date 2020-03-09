<?php

class getTables
{
    public $version = '1.0.0-pl';
	/** @var modX $modx */
    public $modx;
	/** @var pdoFetch $pdoTools */
    public $pdoTools;
	
	public $models;
	
	public $debugs = [];
	
	public $config = array();
	
	public $registryAppName = [];
    
	/**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
        $corePath = MODX_CORE_PATH . 'components/gettables/';
        $assetsUrl = MODX_ASSETS_URL . 'components/gettables/';
		
		
		$this->config = array_merge([
			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'processorsPath' => $corePath . 'processors/',
			'connectorUrl' => $assetsUrl . 'connector.php',
			'actionUrl' => $assetsUrl . 'action.php',
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
			'ctx' => 'web',
			
			'frontend_framework_style' => $this->modx->getOption('gettables_frontend_framework_style',null,'bootstrap_v3'),
			'getTableNavTpl' => 'getTable.nav.tpl',
			'getTableEditRowTpl' => 'getTable.EditRow.tpl',
		], $config);
		
		$this->models['getTabs']['class'] = 'gettabs.class.php';
		$this->models['getTable']['class'] = 'gettable.class.php';
		$this->models['getModal']['class'] = 'getmodal.class.php';
		$this->models['getSelect']['class'] = 'getselect.class.php';
		
		
        //$this->modx->addPackage('gettables', $this->config['modelPath']);
        $this->modx->lexicon->load('gettables:default');
		
		$this->getModels();
		
		if ($this->pdoTools = $this->modx->getService('pdoFetch')) {
			if(isset($this->config['pdoTools'])){
				$this->pdoTools->setConfig($this->config['pdoTools']);
			}else{
				$pdoConfig = ['return'=>'data',];
				if (!empty($this->config['loadModels'])) {
					$pdoConfig['loadModels'] = $this->config['loadModels'];
				}
				$this->pdoTools->setConfig($pdoConfig);
				
			}
			$this->config['pdoClear'] = $this->pdoTools->config;
        }
		
		$this->config['hash'] = sha1(json_encode($this->config));
    }
	
	public function addDebug($debug = [],$mes = '')
    {
		if($this->config['debug']) $this->debugs[] = ['mes'=>$mes,'debug'=>$debug];
	}
	public function getModels()
    {
		
		
		if (empty($this->config['loadModels'])) {
            return;
        }
		
		
        $time = microtime(true);
        $models = array();
        if (strpos(ltrim($this->config['loadModels']), '{') === 0) {
            $tmp = json_decode($this->config['loadModels'], true);
            foreach ($tmp as $k => $v) {
                if (!is_array($v)) {
                    $v = array(
                        'path' => trim($v),
                    );
                }
                $v = array_merge(array(
                    'path' => MODX_CORE_PATH . 'components/' . strtolower($k) . '/model/',
                    'prefix' => null,
                ), $v);
                if (strpos($v['path'], MODX_CORE_PATH) === false) {
                    $v['path'] = MODX_CORE_PATH . ltrim($v['path'], '/');
                }
                $models[$k] = $v;
            }
        } else {
            $tmp = array_map('trim', explode(',', $this->config['loadModels']));
            foreach ($tmp as $v) {
                $parts = explode(':', $v, 2);
                $models[$parts[0]] = array(
                    'path' => MODX_CORE_PATH . 'components/' . strtolower($parts[0]) . '/model/',
                    'prefix' => count($parts) > 1 ? $parts[1] : null,
                );
            }
        }
		
		$this->models = array_merge($this->models,$models);
	}
	
	public function initialize()
    {
		if(!$this->config['isAjax']){
			$this->saveCache();
			$this->registerCSS_JS();
		}
	}
	public function getClassCache($gts_class,$gts_name)
    {
		if(isset($_SESSION['getTables'][$this->config['hash']][$gts_class][$gts_name]))
			return $_SESSION['getTables'][$this->config['hash']][$gts_class][$gts_name];
		return false;
	}
	public function clearCache()
    {
		unset($_SESSION['getTables']);
	}
	public function setClassCache($gts_class,$gts_name, $gts_config)
    {
		$_SESSION['getTables'][$this->config['hash']][$gts_class][$gts_name] = $gts_config;
		return true;
	}
	public function setClassConfig($gts_class, $gts_name, $gts_config)
    {
		if(!$this->config[$gts_class][$gts_name]) $this->config[$gts_class][$gts_name] = [];
		$this->config[$gts_class][$gts_name] = array_merge($this->config[$gts_class][$gts_name], $gts_config);
		$this->setClassCache($gts_class,$gts_name, $this->config[$gts_class][$gts_name]);
		//$this->registryAppName[$gts_class][] = $gts_name;
	}
	
	public function getRegistryAppName($gts_class, $gts_name)
    {
		$i = 1; $gts_name_temp = $gts_name;
		if(empty($this->registryAppName[$gts_class])){
			//$this->pdoTools->addTime("getRegistryAppName1 gts_name=$gts_name gts_name_temp=$gts_name_temp");
			$this->registryAppName[$gts_class][] = $gts_name_temp;
			return $gts_name_temp;
		}
		do {
			//$this->pdoTools->addTime("getRegistryAppName2 gts_name=$gts_name gts_name_temp=$gts_name_temp ".print_r($this->registryAppName[$gts_class],1));
			if(in_array($gts_name_temp,$this->registryAppName[$gts_class])){
				//$this->pdoTools->addTime("getRegistryAppName3 gts_name=$gts_name gts_name_temp=$gts_name_temp ".print_r($this->registryAppName[$gts_class],1));
				$gts_name_temp = $gts_name.'_'.$i;
			}else{
				//$this->pdoTools->addTime("getRegistryAppName4 gts_name=$gts_name gts_name_temp=$gts_name_temp ".print_r($this->registryAppName[$gts_class],1));
				$this->registryAppName[$gts_class][] = $gts_name_temp;
				return $gts_name_temp;
			}
			$i++;
		} while ($i < 10000);
	}
	
	public function loadFromCache($hash)
    {
		if(!empty($_SESSION['getTables'][$hash]))
			$this->config = $_SESSION['getTables'][$hash];
	}
	public function saveCache()
    {
		if(!empty($this->config['hash']))
			$_SESSION['getTables'][$this->config['hash']] = $this->config;
	}
	public function initFromCache()
    {
		if(!empty($_SESSION['getTables'][$this->config['hash']]))
			$this->config = $_SESSION['getTables'][$this->config['hash']];
	}
	
	public function getCSS_JS()
    {
		
		return [
			'jquery_js' => $this->modx->getOption('gettables_load_jquery',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/jquery.min.js'),
			'load_jquery' => $this->modx->getOption('gettables_load_jquery','',0),
			'frontend_framework_js' => $this->modx->getOption('gettables_frontend_framework_js',null,'[[+jsUrl]]gettables.js'),
			
			'load_frontend_framework_style' => $this->modx->getOption('gettables_load_frontend_framework_style',null,0),
			
			'frontend_framework_style_css' => $this->modx->getOption('gettables_frontend_framework_style_css',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/css/bootstrap.min.css'),
			'frontend_framework_style_js' => $this->modx->getOption('gettables_frontend_framework_style_js',null,'[[+assetsUrl]]vendor/bootstrap_v3_3_6/js/bootstrap.min.js'),
			
			'frontend_message_css' => $this->modx->getOption('gettables_frontend_message_css',null,'[[+cssUrl]]gettables.message.css'),
			'frontend_message_js' => $this->modx->getOption('gettables_frontend_message_js',null,'[[+jsUrl]]gettables.message.js'),
		];
	}
	public function makePlaceholders($config)
    {
		$placeholders = [];
		foreach($config as $k=>$v){
			$placeholders['pl'][] = "[[+$k]]";
			$placeholders['vl'][] = $v;
		}
		return $placeholders;
	}
	public function registerCSS_JS()
    {
		$config = $this->config;
		$placeholders = $this->makePlaceholders($config);
		//$this->modx->log(1,"<pre>".print_r($placeholders,1)."</pre>");
		
		$CSS_JS = $this->getCSS_JS();
		if(isset($config['tabs'])){
			$getTabs = $this->getService('getTabs');
			$CSS_JS = array_merge($CSS_JS, $getTabs->getCSS_JS());
		}
		foreach($CSS_JS as $k=>$v){
			if(isset($config[$k])) $CSS_JS[$k] = $config[$k];
		}
		
		
		// Register CSS
		$csss = array();
		if($config['load_frontend_framework_style']) $csss[] = $CSS_JS['frontend_framework_style_css'];
		$csss[] = $CSS_JS['frontend_message_css']; 
		if(!empty($CSS_JS['frontend_gettabs_css'])) $csss[] = $CSS_JS['frontend_gettabs_css'];
		
		if($config['add_css']){
			foreach(explode(",",$config['add_css']) as $acss){
				$csss[] = $acss;
			}
		}
		
		
		foreach($csss as $css){
			if (!empty($css) && preg_match('/\.css/i', $css)) {
				if (preg_match('/\.css$/i', $css)) {
					$css .= '?v=' . substr(md5($this->version.$config['frontend_framework_style']), 0, 10);
				}
				$this->modx->regClientCSS(str_replace($placeholders['pl'], $placeholders['vl'], $css));
			}
		}
		// Register JS
        $jss = array();
		if($CSS_JS['load_jquery']) $jss[] = $CSS_JS['jquery_js'];
		if($config['load_frontend_framework_style']) $jss[] = $CSS_JS['frontend_framework_style_js'];
		$jss[] = $CSS_JS['frontend_framework_js'];
		$jss[] = $CSS_JS['frontend_message_js'];
		if(!empty($CSS_JS['frontend_gettabs_js'])) $jss[] = $CSS_JS['frontend_gettabs_js'];
		
		if($config['add_js']){
			foreach(explode(",",$config['add_js']) as $ajs){
				$jss[] = $ajs;
			}
		}
		
		foreach($jss as $js){
            if (!empty($js) && preg_match('/\.js/i', $js)) {
				if (preg_match('/\.js$/i', $js)) {
                    $js .= '?v=' . substr(md5($this->version.$config['frontend_framework_style']), 0, 10);
                }
                $this->modx->regClientScript(str_replace($placeholders['pl'], $placeholders['vl'], $js));
			}
		}
		$data = array(
			'cssUrl' => $this->config['cssUrl'],
			'jsUrl' => $this->config['jsUrl'],
			'actionUrl' => $this->config['actionUrl'],
			'close_all_message' => $this->modx->lexicon('gettables_message_close_all'),
			'showLog' => (boolean)$this->config['showLog'],
		);
		//if(isset($this->config['hash'])){
			//if(is_boolean($this->config['hash'])) 
			
			$data['hash'] = $this->config['hash'];
		//}
		
		$data = json_encode($data, true);
		
		$this->modx->regClientStartupScript(
			'<script type="text/javascript">getTablesConfig = ' . $data . ';</script>', true
		);
		//$this->config['registerCSS_JS'] = true;
	}
	
    /**
     * Handle frontend requests with actions
     *
     * @param $action
     * @param array $data
     *
     * @return array|bool|string
     */
    public function handleRequest($action, $data = array())
    {
        //$this->pdoTools->addTime("getTables handleRequest $action");
		
		
		$ctx = !empty($data['ctx'])
            ? (string)$data['ctx']
            : 'web';
        if ($ctx != 'web') {
            $this->modx->switchContext($ctx);
        }
		if(isset($this->config['permission'][$action]))
			if(!$this->modx->hasPermission($this->config['permission'][$action])) return $this->error(['lexicon'=>'access_denied']);
		
        
		
		
		
		if(isset($data['hash'])){
			$this->loadFromCache($data['hash']);
		}
		$this->config['isAjax'] = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
		//$this->pdoTools->addTime("getTables handleRequest action $action data {ignore}".print_r($data,1)."{/ignore}");
		
        $this->initialize();

		$actions = explode("/",$action);
		$class = $actions[0];
		if($actions[0] == "processor"){
				/*unset($actions[0]);
				$action = implode("/",$actions);
				$otherProps = array(
					'processors_path' => $this->config['corePath'].'processors/'
				);
				$response =  $this->modx->runProcessor($action, $data, $otherProps);*/
				$response = $this->error("Доступ запрешен processor $action");
		}else if(count($actions) == 1){
			$response = $this->error("Доступ запрешен method_exists $action");
			/*$class = get_class($this);
			if(method_exists($this,$action)){
				$response = $this->$action($data);
			}else{
				$response = $this->error("Метод $action в классе $class не найден!");
			}*/
		}else if(isset($this->models[$actions[0]])){
			$response = $this->loadService($class);
			if(!is_array($response) or !$response['success']){
				$response = $response;
			}else{
				$service = $this->models[$class]['service'];
				
				unset($actions[0]); 
				$class_action = implode("/",$actions);
				if(method_exists($service,'handleRequest')){ // and $service->checkAccsess($class_action)){
					//$response =  $this->error(['lexicon'=>'access_denied'],$data);
					$response =  $service->handleRequest($class_action, $data);
				}else{
					//$response = $this->error("Доступ запрешен $class_action");
					$class = get_class($service);
					$response = $this->error("Не найден $class/$class_action");
				}
			}
		}else{
			
		}
		
		if(!$response) {
			$response = $this->error("Ошибка {get_class($this)} handleRequest!");
		}
        
		$response['log'] = '<pre class="getTablesLog">' . print_r($this->pdoTools->getTime(), 1) . '</pre>';
		if($this->config['debug']) $response['debugs'] = $this->debugs;
		
		$response = $this->config['isAjax'] ? json_encode($response) : $response;
		return $response;
    }
	
	public function getService($class)
    {
        $response = $this->loadService($class);
		if(is_array($response) and $response['success'])
			return $this->models[$class]['service'];
		return false;
    }
	public function loadService($class)
    {
        if(!$this->models[$class]['service']){
			if($this->models[$class]['class']){
				require_once($this->models[$class]['class']);
				$this->models[$class]['service'] = new $class($this, $this->config);
			}else{
				if(!$this->models[$class]['service'] = $this->modx->getService($class,$class,$this->models[$class]['path'],$this->config)) {
					return $this->error("Компонент $class не найден!");
				}
			}
		}
		if(!$this->models[$class]['service']){
			return $this->error("Компонент или класс $class не найден!");
		}
		return array('success'=> true);
    }

    public function error($message = '', $data = array())
    {
        if(is_array($message)){
			if(isset($message['data'])){
				$message = $this->modx->lexicon($message['lexicon'], $message['data']);
			}else{
				$message = $this->modx->lexicon($message['lexicon']);
			}
		}
		$response = array(
            'success' => false,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
	
    public function success($message = '', $data = array())
    {
        if(is_array($message)){
			if(isset($message['data'])){
				$message = $this->modx->lexicon($message['lexicon'], $message['data']);
			}else{
				$message = $this->modx->lexicon($message['lexicon']);
			}
		}
		$response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
}