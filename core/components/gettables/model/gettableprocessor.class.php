<?php
/*
'commands'=>[
	'before'=>[
		'check_max_sum'=>[
			'test_data'=>'
				{if $data.td.field == "amount"} 
					{set $ostatok_order_new = $old_rows[0].price - $old_rows[0].sum_amount + $old_rows[0].amount - $data.td.value}
					
					{if $ostatok_order_new < 0}
						return error Сумма остатка заказа меньше введенной суммы! {$ostatok_order_new}
					{/if}
					{set $ostatok_perevod_new = $parent_old_row.sum - $parent_old_row.sum_amount + $old_rows[0].amount - $data.td.value}
					{if $ostatok_perevod_new < 0}
						return error Сумма остатка перевода меньше введенной суммы! {$ostatok_perevod_new}
					{/if}
				{/if}',
		],
	],
	'after'=>[
		'check_sum_close'=>[
			'test_data'=>'
				{if $data.td.field == "amount"} 
					{set $ostatok_order_new = $old_rows[0].price - $old_rows[0].sum_amount + $old_rows[0].amount - $data.td.value}
					{$ostatok_order_new}
				{/if}',
			'sets'=>[
				'informacia_po_oplatam'=>[
					'class'=>'BaseOtchet'
					'set_value'=>'{if $test_data == 0}Оплачено{else}{$test_data}{/if}',
					'query'=>[
						'sk_order_id'=>'current_id'
					],
				],
				'sum_closed'=>[
					'class'=>'BaseOtchet'
					'set_value'=>'{if $test_data == 0}1{else}skip set{/if}',
					'query'=>[
						'sk_order_id'=>'current_id'
					],
				],
				'sum_closed_trans'=>[
					'field'=>'sum_closed',
					'class'=>'gtsBTranslation'
					'set_value'=>'{set $ostatok_perevod_new = $parent_old_row.sum - $parent_old_row.sum_amount + $old_rows[0].amount - $data.td.value}
							{if $ostatok_perevod_new == 0}1{else}skip set{/if}',
					'query'=>[
						'id'=>'parent_id'
					],
				],
			],
		],
	],
],
*/
class getTableProcessor
{
    public $modx;
	/** @var pdoFetch $pdoTools */
    public $pdoTools;
	
	public $getTables;
	public $getTable;
	public $debug = [];
	public $old_rows = [];
	public $old_row_ids;
	public $new_values = [];
	public $parent_old_row = [];
	public $parent_old_row_id;
	public $triggers = [
		'gtsBTranslation'=>[
			'before'=>[
				'update,remove'=>[
					'test_child'=>[
						'gets'=>[
							'child'=>[
								'class' => 'gtsBPayment',
								'where' => [
									'translation_id' => 'id',
								],
								'query'=>'count',
							],
							'translation'=>[
								'class' => 'gtsBTranslation',
								'where' => [
									'id' => 'id',
								],
								'query'=>'object',
							],
						],
						//return error 2020-02-02 {strtotime("-6 day") |date_format : "%Y-%m-%d %H:%M:%S"} {strtotime("yesterday 00:00")} {strtotime($translation.date)} {$user_id}
						'test_data'=>'
						
						{if strtotime("-6 day") > strtotime($translation.date) and $user_id != 19}
							return error Можно редактировать данные только за 6 дней!
						{/if}
						{if $child > 0} 
							return error На данный платеж есть оплаты
						{/if}',
					],
				],
			],
		],
		'gtsBPayment'=>[
			'before'=>[
				'update,create'=>[
					'test_sum_sk_order'=>[
						'sensitive'=>[
							'amount'=>[],
						],
						'gets'=>[
							'translation'=>[
								'class' => 'gtsBTranslation',
								'where' => [
									'id' => 'translation_id',
								],
								'query'=>'object',
							],
							'sum_translation'=>[
								'class' => 'gtsBPayment',
								'where' => [
									'translation_id' => 'translation_id',
								],
								'query'=>'sum',
								'field'=>'amount',
							],
							
							'order'=>[
								'switch'=>[
									'zakaz'=>[
										'fields'=>[
											'payment_type_id'=>1,
										],
										'get'=>[
											'class' => 'BaseOtchet',
											'where' => [
												'sk_order_id' => 'order_id',
											],
											'query'=>'object',
										],
									],
								],
								
							],
							'sum_order'=>[
								'class' => 'gtsBPayment',
								'where' => [
									'payment_type_id'=>'payment_type_id',
									'order_id' => 'order_id',
								],
								'query'=>'sum',
								'field'=>'amount',
							],
						],
						'test_data'=>'

								{set $ostatok_perevod_new = $translation.sum - $sum_translation + $object_old.amount - $object_new.amount}
								
								{if $ostatok_perevod_new < 0 and abs($ostatok_perevod_new) > 0.1}
									return error Сумма остатка перевода меньше введенной суммы! {$ostatok_perevod_new}
								{/if}
								{if $object_new.amount < 0} return error Отрицательные суммы запрещены! {/if}
								{if $object_new.amount == 0 and $method == "create"}
									 return error Сумма 0!{/if}
								{/if}
								{if $order}
									{set $ostatok_order_new = $order.price - $sum_order + $object_old.amount - $object_new.amount}
									{if $ostatok_order_new < 0 and abs($ostatok_order_new) > 0.1}
										return error Сумма остатка заказа меньше введенной суммы! {$ostatok_order_new}
									{/if}
								{/if}

							',
					],
				],
			],
			'after'=>[
				'update,create'=>[
					'set_statuses'=>[
						'sensitive'=>[
							'amount'=>[],
						],
						'gets'=>[
							'translation'=>[
								'class' => 'gtsBTranslation',
								'where' => [
									'id' => 'translation_id',
								],
								'query'=>'object',
							],
							'sum_translation'=>[
								'class' => 'gtsBPayment',
								'where' => [
									'translation_id' => 'translation_id',
								],
								'query'=>'sum',
								'field'=>'amount',
							],
							
							/*'order'=>[
								'switch'=>[
									'zakaz'=>[
										'fields'=>[
											'payment_type_id'=>1,
										],
										'get'=>[
											'class' => 'BaseOtchet',
											'where' => [
												'sk_order_id' => 'order_id',
											],
											'query'=>'object',
										],
									],
								],
								
							],
							'sum_order'=>[
								'class' => 'gtsBPayment',
								'where' => [
									'payment_type_id'=>'payment_type_id',
									'order_id' => 'order_id',
								],
								'query'=>'sum',
								'field'=>'amount',
							],*/
						],
						'sets'=>[
							/*'order'=>[
								'switch'=>[
									'zakaz'=>[
										'fields'=>[
											'payment_type_id'=>1,
										],
										'sets'=>[
											'informacia_po_oplatam'=>'
												{set $ostatok_order = $order.price - $sum_order}
												{if $ostatok_order == 0}Оплачено{else}{$ostatok_order}{/if}
											',
											'sum_closed'=>'
												{set $ostatok_order = $order.price - $sum_order}
												{if $ostatok_order == 0}1{else}0{/if}
											',
										],
									],
								],
							],*/
							'translation'=>[
								'sum_closed'=>'
									{set $ostatok = $translation.sum - $sum_translation}
									{if $ostatok == 0}1{else}0{/if}
								',
							],
						],
					],
				],
				
			],
		],
	];
	/**
     * @param modX $modx
     * @param array $config
     */
    function __construct(getTable & $getTable, array $config = [])
    {
        $this->getTable =& $getTable;
		$this->getTables =& $this->getTable->getTables;
		$this->modx =& $this->getTables->modx;
		$this->pdoTools =& $this->getTables->pdoTools;
		
		$this->config = array_merge([
			
		], $config);
		
    }
	public function run_triggers($class, $type, $method, $fields, $object_old, $object_new =[])
	{
		$triggers = $this->triggers;
		if(!isset($triggers[$class][$type])) return $this->success('Не назначено');
		$run_methods = false;
		foreach($triggers[$class][$type] as $methods=>$v){
			if(strpos($methods, $method) !== false){
				$run_methods = $methods;
			}
		}
		if(!$run_methods) return $this->success('Не назначено');
		
		foreach($triggers[$class][$type][$run_methods] as $name=>$trigger){
			
			$sens = false;
			if($trigger['sensitive']){
				foreach($trigger['sensitive'] as $field=>$value){
					if(isset($fields[$field])) $sens = true;
				}
			}else{
				$sens = true;
			}
			if($sens){
				$gets = [];
				foreach($trigger['gets'] as $get_name=>$get){
					if(isset($get['switch'])){
						$switch = false;
						foreach($get['switch'] as $case){
							foreach($case['fields'] as $cf=>$cv){
								if($object_old[$cf] == $cv) $switch = true;
							}
						}
						if($switch) $get = $case['get'];
					}
					if($get['class']){
						foreach($get['where'] as $wf=>&$wv){
							$wv = $object_old[$wv];
						}
						switch($get['query']){
							case 'object':
								if($$get_name = $this->modx->getObject($get['class'], $get['where'])){
									$gets[$get_name] = $$get_name->toArray();
								}else{
									$gets[$get_name] = false;
								}
								break;
							case 'count':
								if($get_count = $this->modx->getCount($get['class'], $get['where'])){
									$gets[$get_name] = $$get_name;
								}
								break;
							case 'sum':
								$c = $this->modx->newQuery($get['class']);
								$c->select('sum('.$get['field'].') as cnt');
								$c->where($get['where']);
								if($object = $this->modx->getObject($get['class'], $c)){
									$gets[$get_name] = $object->get('cnt');
								}
								break;
						}
					}
				}
				//gets получили теперь тест.
				$gets['object_old'] = $object_old;
				$gets['object_new'] = $object_new;
				$gets['method'] = $method;
				$gets['user_id'] = $this->modx->user->id;
				if($trigger['test_data']){
					$test_data = $this->pdoTools->getChunk('@INLINE '.$trigger['test_data'],$gets);
					$test_data = trim($test_data);
					////$this->getTables->addDebug($test_data,"$class $type $method run_triggers $test_data");
					////$this->getTables->addDebug($fields,"run_triggers fields");
					
					//$class, $type, $method, $fields,
					if(strpos($test_data, 'return error') !== false){
						return $this->error(trim(str_replace('return error','',$test_data)));
					}
				}
				//sets
				if($trigger['sets']){
					$sets = [];
					foreach($trigger['sets'] as $set_name=>$set){
						if(isset($set['switch'])){
							$switch = false;
							foreach($set['switch'] as $case){
								foreach($case['fields'] as $cf=>$cv){
									if($object_old[$cf] == $cv) $switch = true;
								}
							}
							if($switch) $sets[$set_name] = $case['sets'];
						}else{
							$sets[$set_name] = $set;
						}
					}
					foreach($sets as $set_name=>$set){
						if($$set_name){
							foreach($set as $field=>$set_value){
								$set_value = $this->pdoTools->getChunk('@INLINE '.$set_value,$gets);
								$set_value = trim($set_value);
								$$set_name->{$field} = $set_value;
							}
							$$set_name->save();
						}
					}
				}
			}
		}
		return $this->success('Выполнено успешно');
	}
	public function run_command($type, $bn, $beforeCommand, $data = array(), $table = [])
	{
		////$this->getTables->addDebug($this->parent_old_row,'run_command $parent_old_row ');
		////$this->getTables->addDebug($this->old_rows,'run_command $old_row ');
		////$this->getTables->addDebug($beforeCommand['test_data'],'run_command $test_data1 ');
		$new_values = [];
		foreach($this->new_values as $nv){
			if($nv['action'] == "create" or $nv['action'] == "update" or $nv['action'] == "autosave"){
				$new_values[$nv['class']][$nv['field']] = [$nv['value']];
			}
		}
		$test_data = $this->pdoTools->getChunk('@INLINE '.$beforeCommand['test_data'],[
								'action'=>$this->action,
								'old_rows' => $this->old_rows,
								'parent_old_row' => $this->parent_old_row,
								'data' => $data,
								'new_values' => $new_values,
								]);
		
		$test_data = trim($test_data);
		////$this->getTables->addDebug($test_data,$type.'run_command $test_data ');
		if(strpos($test_data, 'return error') !== false){
			return $this->error(trim(str_replace('return error','',$test_data)));
		}
		if($beforeCommand['sets']){
			foreach($beforeCommand['sets'] as $ks=>$set){
				if(empty($set['field'])) $set['field'] = $ks;
				if($set['pdoTools']){
					$add_gen = [
							'action'=>$this->action,
							'old_rows' => $this->old_rows,
							'parent_old_row' => $this->parent_old_row,
							'data' => $data,
							'new_values' => $new_values,
							];
					$pdoConfig = $this->gen_pdoConfig($set['pdoTools'],$table['sub_default'],$table['sub_where'], $data, $add_gen);
					$this->pdoTools->config = array_merge($this->config['pdoClear'],$pdoConfig);
					$rows = $this->pdoTools->run();
				}
				$set_value = $this->pdoTools->getChunk('@INLINE '.$set['set_value'],[
							'test_data'=>$test_data,
							'action'=>$this->action,
							'old_rows' => $this->old_rows,
							'parent_old_row' => $this->parent_old_row,
							'data' => $data,
							'new_values' => $new_values,
							'rows' => $rows,
							]);
				$set_value = trim($set_value);
				////$this->getTables->addDebug($set_value,'run_command $set_value ');
				if(strpos($set_value, 'skip set') !== false) continue;
				$query = [];
				foreach($set['query'] as $qfield=>$qv){
					switch($qv){
						case 'current_id':
							$query[$qfield] = $this->old_rows['0']['id'];
							break;
						case 'parent_id':
							$query[$qfield] = $this->parent_old_row['id'];
							break;
					}
				}
				
				if(!empty($query) and !empty($set['class'])){
					////$this->getTables->addDebug($set,'command $set');
					////$this->getTables->addDebug($query,'command $query !'.$set_value);
					if($obj = $this->modx->getObject($set['class'],$query)){
						$obj->{$set['field']} = $set_value;
						if($obj->save()){
							$this->new_values[] = [
								'action'=>$this->action,
								'operation'=>"commands $type",
								'description'=>"commands $type $bn set $ks",
								'class'=>$set['class'],
								'id'=>$obj->id,
								'field'=>$ks,
								'value'=>$set_value,
								];
						}
					}
				}
			}
		}
		return $this->success('Выполнено успешно');
	}
	public function walkFunc(&$item, $key, $sub_default){
        $item = $this->pdoTools->getChunk("@INLINE ".$item, ['sub_default'=>$sub_default]);
    }
	
	
    public function run($action, $table, $data = array())
    {
		
		if(!isset($table['actions'][$action]) and $action !="autosave") return $this->error("Action $action не найдено! ",$table);
		$this->current_action = $table['actions'][$action];
		$this->action = $action;
		$edit_tables = [];
		////$this->getTables->addDebug($table['edits'],'run $table[edits] ');
		foreach($table['edits'] as $edit){
			if($edit['type'] == 'view') continue;
			$edit_tables[$edit['class']][] = $edit;
		}
		
		//проверка что данные можно редактировать. Хз знает зачем. И собираем данные для лога и комманд. 
		//Обновление. Сейчас придумал тригеры и они будут собирать и проверять все. 
		if($table['commands']){
			$resp = $this->check_rows($table, $data);
			if(!$resp['success']) return $resp;
		}
		
		//beforeSave command
		if(isset($table['commands']['before'])){
			foreach($table['commands']['before'] as $bn=>$beforeCommand){
				$resp = $this->run_command('before', $bn, $beforeCommand, $data, $table);
				if(!$resp['success']) return $resp;
			}
		}
		
		switch($action){
			case 'update':
				$response = $this->update($table, $edit_tables, $data);
				break;
			case 'create':
				$response = $this->update($table, $edit_tables, $data, true);
				break;
			case 'toggle':
				$response = $this->sets($table, $edit_tables, $data);
				break;
			case 'set':
				$response = $this->sets($table, $edit_tables, $data);
				break;
			case 'remove':
				$response = $this->remove($table, $edit_tables, $data);
				break;
			case 'autosave':
				$response = $this->autosave($table, $edit_tables, $data);
				break;
			default:
				$response = $this->error("Action $action не найдено! ",$table);
				break;
		}
		
		//afterSave command
		if(isset($table['commands']['after'])){
			foreach($table['commands']['after'] as $an=>$afterCommand){
				$resp = $this->run_command('after', $an, $afterCommand, $data, $table);
				if(!$resp['success']) return $resp;
			}
		}
		return $response;
    }
	public function gen_pdoConfig($pdoConfig, $tsub_default = [], $tsub_where =[], $data = array(), $add_gen = [])
    {
		//$pdoConfig = $table['pdoTools'];
		if(!empty($tsub_default)){
			$sub_default = $add_gen;
			foreach($data['sub_where_current'] as $where_field=>$where_value){
				if($tsub_default[$where_field]){
					$sub_default[$where_field] = $where_value;
				}	
			}
			array_walk_recursive($pdoConfig,array(&$this, 'walkFunc'),$sub_default);
		}else if(!empty($tsub_where)){
			$sub_where = [];
			foreach($data['sub_where_current'] as $where_field=>$where_value){
				if($tsub_where[$where_field]){
					$sub_where[$where_field] = $where_value;
				}	
			}
			$pdoConfig['where'] = $sub_where;
		}
		return $pdoConfig;
	}
	public function check_rows($table, $data = array())
    {
		$trs_data = [];
		if($data['trs_data']){
			$trs_data = $data['trs_data'];
		}else{
			$trs_data[] = $data['tr_data'];
		}
		
		if($this->action != "create"){
			$pdoConfig = $this->gen_pdoConfig($table['pdoTools'],$table['sub_default'],$table['sub_where'], $data);
			////$this->getTables->addDebug($sub_default,'run $sub_default ');
			////$this->getTables->addDebug($pdoConfig,'run $pdoConfig ');
			$ids = [];
			foreach($trs_data as $tr_data){
				$ids[] = $tr_data['id'];
			}
			$pdoConfig['where'][$table['class'].".id:IN"] = $ids;
			$pdoConfig['limit'] = 1;
			$this->old_row_ids = $ids;
			$this->pdoTools->config = array_merge($this->config['pdoClear'],$pdoConfig);
			$rows = $this->pdoTools->run();
			if(count($rows) == 0){
				return $this->error('Строка таблицы не найдена!');
			}else{
				$this->old_rows = $rows;
			}
		}
		if($data['parent_current']){
			
			//$this->getTables->addDebug($data['parent_current'],'run $data  parent_current');
			if($old_table_parent = $this->getTables->getClassCache('getTable',$data['parent_current']['name'])){
				////$this->getTables->addDebug($old_table_parent,'run $old_table_parent ');
				$pdoConfig = $old_table_parent['pdoTools'];
				
				$pdoConfig['where'] = [
					$old_table_parent['class'].".id" => $data['parent_current']['tr_data']['id'],
				];
				$pdoConfig['limit'] = 1;
				$this->parent_old_row_id = $data['parent_current']['tr_data']['id'];
				$this->pdoTools->config=array_merge($this->config['pdoClear'],$pdoConfig);
				$rows = $this->pdoTools->run();
				if(count($rows) == 1){
					$this->parent_old_row = $rows[0];
				}
			}
		}
		return $this->success('');
	}
	public function autosave($table, $edit_tables, $data = array())
    {
		if(empty($data['tr_data'])) return $this->error('tr_data пусто');

		if(!(int)$data['tr_data']['id']){
			return $this->error('$tr_data[id] пусто');
		}
		$set_data['id'] = (int)$data['tr_data']['id'];
		$set_data[$data['td']['field']] = $data['td']['value'];
		return $this->update($table, $edit_tables, $set_data, false, $data['tr_data']);
	}
	public function sets($table, $edit_tables, $data = array())
    {
		
		$saved = [];
		if(empty($data['trs_data'])) return $this->error('trs_data пусто');
		foreach($data['trs_data'] as $tr_data){
			if(!(int)$tr_data['id']){
				$saved[] = $this->error('$tr_data[id] пусто'); continue;
			}
			$set_data['id'] = (int)$tr_data['id'];
			$value = 0;
			if(isset($data['button_data']['toggle'])){
				if($data['button_data']['toggle'] == 'enable') $value = 1;
			}
			$set_data[$this->current_action['field']] = $value;
			$saved[] = $this->update($table, $edit_tables, $set_data);
		}
		
		$error = '';
		foreach($saved as $s){
			if(!$s['success']) $error = "Object {$s['class']} {$s['field']} не сохранен sets \r\n";
		}
		if(!$error){
			return $this->success('Сохранено успешно',$saved);
		}else{
			return $this->error($error,$saved);
		}
	}
	
	public function remove($table, $edit_tables, $data = array())
    {
		$saved = [];
		if(empty($data['trs_data'])) return $this->error('trs_data пусто');
		foreach($data['trs_data'] as $tr_data){
			if(!(int)$tr_data['id']){
				$saved[] = $this->error('$tr_data[id] пусто'); continue;
			}
			if(!$obj = $this->modx->getObject($table['class'],(int)$tr_data['id'])){
				$saved[] = $this->error('Объект не найден');
			}
			
			$object_old = $obj->toArray();
			$resp = $this->run_triggers($table['class'], 'before', 'remove', [], $object_old);
			if(!$resp['success']) return $resp;
			
			$id = $obj->id;
			if($obj->remove()){
				$resp = $this->run_triggers($table['class'], 'after', 'remove', [], $object_old);
				if(!$resp['success']) return $resp;
				
				$this->new_values[] = [
					'action'=>$this->action,
					'operation'=>"remove",
					'class'=>$table['class'],
					'id'=>$id,
					//'field'=>$ks,
					//'value'=>$set_value,
					];
				$saved[] = $this->success('Удалено успешно',$saved);
			} 
		}
		
		$error = '';
		foreach($saved as $s){
			if(!$s['success']) $error = "Удаление запрещено или возникла ошибка \r\n";
		}
		if(!$error){
			return $this->success('Удалено успешно',$saved);
		}else{
			return $this->error($error,$saved);
		}
	}
	/*public function checkUpdateAccess($id,$table)
    {
		////$this->getTables->addDebug($table['pdoTools'],'checkUpdateAccess $table[pdoTools] ');
		$pdoConfig = $table['pdoTools'];
		////$this->getTables->addDebug($table['pdoTools'],'$table[pdoTools] ');
		$pdoConfig['limit'] = 1;
		$pdoConfig['return'] = 'ids';
		$pdoConfig['where']['id'] = $id;
		
		$this->pdoTools->config = array_merge($this->config['pdoClear'],$pdoConfig);
		
		$ids = $this->pdoTools->run();
		////$this->getTables->addDebug($this->pdoTools->config,$ids.' checkUpdateAccess $this->pdoTools->config ');
		if($ids == $id) return true;
		return false;		
	}*/
	
	public function update($table, $edit_tables, $data = array(), $create = false, $tr_data = [])
    {
		$saved = [];
		
		////$this->getTables->addDebug($edit_tables,'update $edit_tables ');
		
		$class = $table['class'];
		if($edit_tables[$class]){
			$set_data = [];
			foreach($edit_tables[$class] as $edit){
				if($data[$edit['field']] !==null)
					$set_data[$edit['field']] = $data[$edit['field']];
			}
			foreach($table['defaultFieldSet'] as $df=>$dfv){
				if($dfv['class'] == $class)
					$set_data[$df] = $dfv['value'];
			}
			
			
			
			if(isset($this->current_action['processors'][$class])){
				if(empty($set_data['context_key'])) $set_data['context_key'] = 'web';
				//$saved[] = $this->error('runProcessor ',$set_data);
				$modx_response = $this->modx->runProcessor($this->current_action['processors'][$class], $set_data);
				if ($modx_response->isError()) {
					$saved[] = $this->error('runProcessor ',$this->modx->error->failure($modx_response->getMessage()));
					$data['id'] = false;
				}else{
					foreach($set_data as $set_field=>$set_value){
						$this->new_values[] = [
							'action'=>$this->action,
							'operation'=>"update",
							'class'=>$class,
							'id'=>$obj->id,
							'field'=>$set_field,
							'value'=>$set_value,
						];
					}
					$saved[] = $this->success('runProcessor ',$modx_response->response);
					$data['id'] = $modx_response->response['object']['id'];
					$object_new = $modx_response->response['object'];
					$type = 'update';
					$resp = $this->run_triggers($class, 'after', $type, $set_data, $object_new,$object_new);
					if(!$resp['success']) return $resp;
				}
			}else{
				$saveobj = ['success'=>false,'class'=>$class];
				//$saved[] = $data;
				$this->getTables->addDebug($set_data,'$set_data update');
				if($create){
					$obj = $this->modx->newObject($class);
					$data['id'] = false;
					$type = 'create';
				}else{
					$obj = $this->modx->getObject($class,(int)$data['id']);
					$type = 'update';
				}
				if($obj){
					//$saved[] = $obj->toArray();
					$object_old = $obj->toArray();
					
					$obj->fromArray($set_data);
					$object_new = $obj->toArray();
					
					$resp = $this->run_triggers($class, 'before', $type, $set_data, $object_old,$object_new);
					if(!$resp['success']) return $resp;
					
					//$saved[] = $this->success('Сохранено успешно',$set_data);
					if($obj->save()){
						
						$object_new = $obj->toArray();
						$resp = $this->run_triggers($class, 'after', $type, $set_data, $object_old,$object_new);
						if(!$resp['success']) return $resp;
						
						foreach($set_data as $set_field=>$set_value){
							$this->new_values[] = [
								'action'=>$this->action,
								'operation'=>"update",
								'class'=>$class,
								'id'=>$obj->id,
								'field'=>$set_field,
								'value'=>$set_value,
							];
						}
						$saveobj['success'] = true;
						$data['id'] = $obj->id;
					}
				}
				$saved[] = $saveobj;
			}
			unset($edit_tables[$class]);
		}
		if($create and !$data['id']) return $this->error("Не удалось создать объект $class",$saved);
		////$this->getTables->addDebug($edit_tables,'update 2 $edit_tables ');
		
		foreach($edit_tables as $class=>$edits){
			foreach($edits as $edit){
				//$this->getTables->addDebug($edit,'$edit update '.$edit['field']);
				
				
				if(!empty($edit['search_fields'])){
					$saveobj = ['success'=>false,'class'=>$class,'field'=>$edit['field']];
					//$this->getTables->addDebug($edit,'$edit update search_fields '.$edit['field']);
					////$this->getTables->addDebug($data,'$data');
					//$this->getTables->addDebug($edit['search_fields'],'111 update $edit[search_fields]');
					$search_fields = [];
					foreach($edit['search_fields'] as $k=>$v){
						$search_fields[$k] = $v;
						//$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 1 k update $$search_fields');
						foreach($tr_data as $tr_field=>$tr_value){
							if($tr_field == $k){
								$search_fields[$k] = $tr_value;
							}
						}
						////$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 2 k update $$search_fields');
						if($v === 'id'){
							$search_fields[$k] = (int)$data['id'];
						}
						////$this->getTables->addDebug($search_fields[$k],$v." ".$k.' 3 k update $$search_fields');
					}
					////$this->getTables->addDebug($search_fields,'222 update $$search_fields');
					////$this->getTables->addDebug($search_fields,'$search_fields');
					if($edit['multiple']){
						$cols = $this->modx->getIterator($class,$search_fields);
						foreach($cols as $obj1){
							$object_old = $obj1->toArray();
							$resp = $this->run_triggers($class, 'before', 'remove', [], $object_old);
							if(!$resp['success']) return $resp;
							
							$id = $obj1->id;
							if($obj1->remove()){
								$resp = $this->run_triggers($class, 'after', 'remove', [], $object_old);
								if(!$resp['success']) return $resp;
								
								$this->new_values[] = [
									'action'=>$this->action,
									'operation'=>"remove",
									'description'=>"multiple",
									'class'=>$class,
									'id'=>$id,
									//'field'=>$ks,
									//'value'=>$set_value,
								];
							}
							
						}
						if(isset($data[$edit['field']])){
							foreach($data[$edit['field']] as $v){
								$search_fields2 = $search_fields;
								$search_fields2[$edit['field']] = $v;
								////$this->getTables->addDebug($search_fields2,'multiple update $search_fields2');
								if($obj2 = $this->modx->newObject($class,$search_fields2)){
									if($obj2->save()){
										$object_old = $obj2->toArray();
										$resp = $this->run_triggers($class, 'after', 'create', [$edit['field']=>1], $object_old, $object_old);
										if(!$resp['success']) return $resp;
										
										foreach($search_fields2 as $set_field=>$set_value){
											$this->new_values[] = [
												'action'=>$this->action,
												'operation'=>"update",
												'description'=>"multiple",
												'class'=>$class,
												'id'=>$obj2->id,
												'field'=>$set_field,
												'value'=>$set_value,
											];
										}
										$saveobj['success'] = true;
									}
								}
							}
						}else{
							/*if($obj2->save()){ //кажется не нужно
										
								$saveobj['success'] = true;
							}*/
						}
					}else{
						//$this->getTables->addDebug($search_fields,"search object $class {$edit['field']} {$edit['value_field']} {$data[$edit['field']]} search_fields");
						if(!$obj2 = $this->modx->getObject($class,$search_fields)){
							$obj2 = $this->modx->newObject($class,$search_fields);
							$type = 'create';
						}else{
							$type = 'update';
						}
						if($obj2){
							
							if($edit['default'] and empty($data[$edit['field']]) and empty($obj2->{$edit['value_field']})){
								$edit['force'] = $edit['default'];
							}
							if($edit['force']){
								switch($edit['type']){
									case 'date':
										$edit['force'] = date('Y-m-d',strtotime($edit['force']));
										break;
								}
								switch($edit['force']){
									case 'user_id':
										$edit['force'] = $this->modx->user->id;
										break;
								}
								$data[$edit['field']] = $edit['force'];
							}
							//$this->getTables->addDebug($edit,"$class  edit");
							//$this->getTables->addDebug($search_fields,"$class {$edit['field']} {$edit['value_field']} {$data[$edit['field']]} search_fields");
							//продумать для удаления пустых записей в БВ. Наверно тригером.
							if(!isset($data[$edit['field']])){
								$saveobj['success'] = true;
								continue;
							}
							
							/*if(isset($table['defaultFieldSet'][$edit['field']])){
								$data[$edit['field']] == $table['defaultFieldSet'][$edit['field']];
							}*/
							
							$object_old = $obj2->toArray();
							$obj2->{$edit['value_field']} = $data[$edit['field']];
							$object_new = $obj2->toArray();
							$resp = $this->run_triggers($class, 'before', $type, [$edit['field']=>1], $object_old,$object_new);
							if(!$resp['success']) return $resp;
							
							if($obj2->save()){
								$object_new = $obj2->toArray();
								$resp = $this->run_triggers($class, 'after', $type, [$edit['field']=>1], $object_old,$object_new);
								if(!$resp['success']) return $resp;
								
								$this->new_values[] = [
									'action'=>$this->action,
									'operation'=>"update",
									'class'=>$class,
									'id'=>$obj2->id,
									'field'=>$edit['value_field'],
									'value'=>$data[$edit['field']],
								];
								$saveobj['success'] = true;
							}
						}
					}
					$saved[] = $saveobj;
				}
				
			}
		}
		$error = '';
		foreach($saved as $s){
			if(!$s['success']) $error = "Object {$s['class']} {$s['field']} не сохранен update \r\n";
		}
		if(!$error){
			return $this->success('Сохранено успешно',$saved);
		}else{
			return $this->error($error,$saved);
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