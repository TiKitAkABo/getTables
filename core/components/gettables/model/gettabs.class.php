<?php

class getTabs
{
    public $modx;
	/** @var pdoFetch $pdoTools */
    public $pdoTools;
	
	public $getTables;
	
	/**
     * @param modX $modx
     * @param array $config
     */
    function __construct(getTables & $getTables, array $config = [])
    {
        $this->getTables =& $getTables;
		$this->modx =& $this->getTables->modx;
		$this->pdoTools =& $this->getTables->pdoTools;
		
		//$tab_config => $this->modx->getOption('gettables_default_tab_config',null,'bootstrap_v3'),
		
		$this->config = array_merge([
			
		], $config);
		
    }
	
	public function getCSS_JS()
    {
		return [
			'frontend_gettabs_css' => '',//$this->modx->getOption('gettables_frontend_message_css',null,'[[+cssUrl]]gettables.gettabs.css')
			'frontend_gettabs_js' => '',//$this->modx->getOption('gettables_frontend_gettabs_js',null,'[[+jsUrl]]gettables.gettabs.js'),
		];
	}

    public function handleRequest($action, $data = array())
    {
		if(method_exists($this,$action)){
			return $this->$action($data);
		}else{
			return $this->error("Метод $action в классе $class не найден!");
		}
    }

	public function fetch()
    {
        if(!$this->config['isAjax']){
			$this->getStyleChunks();
		}

		if(!empty($this->config['tabs'])){
			$html = $this->pdoTools->getChunk($this->config['getTabsTpl'], $this->generateData());
			return $this->success('',array('html'=>$html));
		}else{
			return $this->error("Нет конфига tabs!");
		}
    }
	
	public function generateData()
    {
		$name = $this->config['name'] ? $this->config['name'] : 'getTablesTabs';
		$cls = $this->config['cls'] ? $this->config['cls'] : '';
		$tabs = [];
		if (is_string($this->config['tabs']) and strpos(ltrim($this->config['tabs']), '{') === 0) {
            $this->config['tabs'] = json_decode($this->config['tabs'], true);
		}
		$idx = 1;
		foreach($this->config['tabs'] as $n => $tab){
			if(!empty($tab['permission'])){
				if (!$this->modx->hasPermission($tab['permission'])) continue;
			}
			
			$tab['name'] = $tab['name'] ? $tab['name'] : $n;
			$tab['label'] = $tab['label'] ? $tab['label'] : 'Панель '.$idx;
			$tab['idx'] = $idx;
			if($idx == 1) $tab['active'] = 'active';
			$idx++;
			
			if(isset($tab['chunk'])) $tab['content'] = $this->pdoTools->getChunk($tab['chunk']);
			
			$tabs[$n] = $tab;
		}
		//echo "<pre>generateData ".print_r(['name'=>$name,'class'=>$class,'tabs'=>$tabs],1)."</pre>";
		return ['name'=>$name,'cls'=>$cls,'tabs'=>$tabs];
    }
	
	public function getStyleChunks()
    {
		if($this->config['frontend_framework_style'] != 'bootstrap_v3' and $this->config['getTabsTpl'] == 'getTabs.tpl'){
			if($propSet = $this->modx->getObject('modPropertySet',array('name'=>'getTables_'.$this->config['frontend_framework_style']))){
				if($chunk = $this->modx->getObject('modChunk', array('name' => $propSet->getTabsTpl))){
					$this->config['getTabsTpl'] = $propSet->getTabsTpl;
				}
			}
		}
    }
	
    public function error($message = '', $data = array())
    {
        if(is_array($message)) $message = $this->modx->lexicon($message['lexicon'], $message['data']);
		$response = array(
            'success' => false,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
	
    public function success($message = '', $data = array())
    {
        if(is_array($message)) $message = $this->modx->lexicon($message['lexicon'], $message['data']);
		$response = array(
            'success' => true,
            'message' => $message,
            'data' => $data,
        );

        return $response;
    }
}