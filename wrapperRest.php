<?php 
class Rest {

	private $rest_url;

	private $username;

	private $password;

	private $session;

	private $logged_in;

	private $error = FALSE;

	public function setUrl($url=null)
	{
		$this->rest_url = $url;
	}

	public function setUsername($username=null)
	{
		$this->username = $username;
	}

	public function setPassword($password=null)
	{
		$this->password = $password;
	}

	
	function connect($rest_url=null,$username=null,$password=null,$md5_password=true)
	{
		if (!is_null($rest_url))
		{
			$this->rest_url = $rest_url;
		}

		if (!is_null($username))
		{
			$this->username = $username;
		}

		if (!is_null($password))
		{
			$this->password = $password;
		}

		if($this->login($md5_password)) {
			$this->logged_in = TRUE;
			$data['session'] = $this->session;

			return true;

		} else {
			$this->logged_in = FALSE;

			return false;

		}
	}


	public function get_error() {
		if(isset($this->error['name'])) {
			$error = $this->error;
			$this->error = FALSE;
		 //	print_r($error['description']);
			return $error;
		} else if(is_bool($this->error)) {
			$error = $this->error;
			$this->error = FALSE;
			return $error;
		} else {
			return TRUE;
		}
	}

	
	private function login($md5_password=true) {

		$password = $this->password;
		if ($md5_password)
		{
			$password = md5($this->password);
		}

		$result = $this->rest_request(
				'login',
				array(
						'user_auth' => array('user_name'=>$this->username,'password'=>$password),
						'name_value_list' => array(array('name' => 'notifyonsave', 'value' => 'true'))
				)
		);
		if(isset($result['id'])) {
			$this->session = $result['id'];

			return TRUE;
		} else {
			$this->error = $result;
			if(isset($this->error['name']) && isset($this->error['number']) && isset($this->error['description'])) {
				$this->error = $result;
			} else {
				$this->error['name'] = "Unknown Error";
				$this->error['number'] = -1;
				$this->error['description'] = "Error while login please check username and password";
				return TRUE;
			}

			return FALSE;
		}
	}

	
	private function rest_request($call_name, $call_arguments) {

		$ch = curl_init();

		$post_data = array(
				'method' => $call_name,
				'input_type' => 'JSON',
				'response_type' => 'JSON',
				'rest_data' => json_encode($call_arguments)
		);

		curl_setopt($ch, CURLOPT_URL, $this->rest_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$output = curl_exec($ch);

		$response_data = json_decode($output,true);

		return $response_data;
	}

	
	public function is_valid_id($id) {
		if(!is_string($id)) return FALSE;
		return preg_match("/[0-9a-z\-]+/",$id);
	}

	public function count_records($module, $query) {
		$call_arguments = array(
				'session' => $this->session,
				'module_name' => $module,
				'query' => $query,
				'deleted' => 0
		);

		$result = $this->rest_request(
				'get_entries_count',
				$call_arguments
		);

		if(isset($result['result_count'])) {
			return $result['result_count'];
		} else {
			return FALSE;
		}
	}

	
	public function get_with_related($module,$fields,$options=Null) {

		if(sizeof($fields) < 1) {
			return FALSE;
		}

		//Set the defaults for the options
		if(!isset($options['limit'])) {
			$options['limit'] = 4;
		}
		if(!isset($options['offset'])) {
			$options['offset'] = 0;
		}
		if(!isset($options['where'])) {
			$options['where'] = null;
		}
		if(!isset($options['order_by'])) {
			$options['order_by'] = null;
		}
		if(!isset($fields[$module])) {
			return FALSE;
		}

		$base_fields = $fields[$module];
		unset($fields[$module]);

		$relationships = array();
		foreach($fields as $related_module => $fields_list) {
			$relationships[] = array('name' => strtolower($related_module), 'value' => $fields_list);
		}

		$call_arguments = array(
				'session' => $this->session,
				'module_name' => $module,
				'query' => $options['where'],
				'order_by' => $options['order_by'],
				'offset' => $options['offset'],
				'select_fields' => $base_fields,
				'link_name_to_fields_array' => $relationships,
				'max_results' => $options['limit'],
				'deleted' => false
		);


		$result = $this->rest_request(
				'get_entry_list',
				$call_arguments
		);

		return $result;
	}

	
	public function get($module,$fields,$options=null) {

		$results = $this->get_with_related($module,array($module => $fields),$options);
		$records = array();
		if ($results) {
			foreach($results['entry_list'] as $entry) {
				$record = array();
				foreach($entry['name_value_list'] as $field) {
					$record[$field['name']] = $field['value'];
				}
				$records[] = $record;
			}
		}
		return $records;
	}

	
	public function set($module,$values) {
		$call_arguments = array(
				'session' => $this->session,
				'module_name' => $module,
				'name_value_list' => $values,
		);

		$result = $this->rest_request(
				'set_entry',
				$call_arguments
		);

		return $result;
	}

 public function print_results($results) {
  	
  	//echo '<pre>';print_r($results); echo '</pre>';

        if(isset($results['entry_list'][0]['module_name'])) {
            
        	$module_name = $results['entry_list'][0]['module_name'];
            
            echo "<h1>".$module_name."</h1>";
            
            foreach($results['entry_list'] as $i => $entry) {

            	echo "<div class='first'>";
                
               		 foreach($entry['name_value_list'] as $field) {

                			echo "<div class='second'>".$field['name']." = ".$field['value']."</div>";

                					}
                if(isset($results['relationship_list'][$i])) {
                    foreach($results['relationship_list'][$i] as $keymodule=>$module) {
                	    	$j=0;
                    		echo "<div class='second'><b>related ".$results['relationship_list'][$j]['link_list'][$j]['name']."</b><br/><pre>";
			
                        //	print_r($module[$i]['link_list'][$i]);
                        //	echo 'Key===>'.$keymodule.'<br />';
                        //	print_r($results['relationship_list'][$i]);
                        //	echo '<br />';
                        //	print_r($module[$i]['records'][$i]['link_value']['assigned_user_id']['value']);
                        
                        //	print_r($results['relationship_list'][$j]['link_list']);
                        	
                        //	echo '<br/>';
                        	
                        	
                        	
                        	
                       foreach ($results['relationship_list'][$j]['link_list'][$j]['records'] as $submodule => $subsubmodule) 
                       {  

                       echo 'Assigned_user_id==>'.$results['relationship_list'][$j]['link_list'][$j]['records'][$submodule]['link_value']['assigned_user_id']['value'].'<br /> '; 

                       echo 'first_name==>'.$module[$j]['records'][$submodule]['link_value']['first_name']['value'].'<br /> ';
                       	 
                       echo 'last_name==>'.$module[$j]['records'][$submodule]['link_value']['last_name']['value'].'<br /> ';
                       	 
                       echo 'title==>'.$module[$j]['records'][$submodule]['link_value']['title']['value'].'<br /> ';
						
                      // 	echo '<br />' ;print_r($module[$i]);
                      // 	foreach($subsubmodule['records'] as $x=>$record) {
				
                       		//print_r($subsubmodule['records']);
                       		//echo "<div class='third'>".$submodule['records'][$x]['link_value']['first_name']['value'];

                          //		foreach($subsubmodule[$i] as $field) {
                                
                          	//		foreach ($field['link_value'] as $fields) {
                          		//	echo "<div class='fourth'>".$fields['name']." = ".$fields['value']."</div>";
                            	
                        // }
                            echo "</div>";
                        //}
                        echo "</div>";
                    $j++;
                       }
                     }
                }
                echo "</div>";echo "</pre>";
            }
        }
    }
	
  /*   public function print_results($results) {
    	if(isset($results['entry_list'][0]['module_name'])) {
    		$module_name = $results['entry_list'][0]['module_name'];
    		echo "<h1>".$module_name."</h1>";
    		foreach($results['entry_list'] as $i => $entry) {
    			echo "<div class='first'>";
    			foreach($entry['name_value_list'] as $field) {
    				echo "<div class='second'>".$field['name']." = ".$field['value']."</div>";
    			}
    			if(isset($results['relationship_list'][$i])) {
    				foreach($results['relationship_list'][$i] as $module) {
    					echo "<div class='second'><b>related ".$module['name']."</b><br/>";
    					foreach($module['records'] as $x=>$record) {
    						echo "<div class='third'>";
    						foreach($record as $field) {
    							echo "<div class='fourth'>".$field['name']." = ".$field['value']."</div>";
    						}
    						echo "</div>";
    					}
    					echo "</div>";
    				}
    			}
    			echo "</div>";
    		}
    	}
    }*/
	public function set_relationship($module_name, $module_id, $link_field_name, $related_ids){
		$call_arguments = array(
				'session' => $this->session,
				'module_name' => $module_name,
				'module_id' => $module_id,
				'link_field_name' => $link_field_name,
				'related_ids' => array($related_ids)
		);

		$result = $this->rest_request(
				'set_relationship',
				$call_arguments
		);

		return $result;
	}

	
	public function get_note_attachment($note_id) {
		if($this->is_valid_id($note_id)) {
			$call_arguments = array(
					'session' => $this->session,
					'id' => $note_id
			);

			$result = $this->rest_request(
					'get_note_attachment',
					$call_arguments
			);
			return $result;
		}
		return FALSE;
	}


	public function set_note_attachment($note_id,$file,$filename) {

		$call_arguments = array(
				'session' => $this->session,
				'note' => array(
						'id'=>$note_id,
						'file' => $file,
						'filename' => $filename,
						'related_module_name' => 'Cases'
				)
		);

		$result = $this->rest_request(
				'set_note_attachment',
				$call_arguments
		);

		return $result;
	}

	
	public function get_available_modules(){

		$call_arguments = array(
				'session' => $this->session
		);

		$result = $this->rest_request(
				'get_available_modules', $call_arguments
		);

		return $result;
	}

	
	public function search_by_module($search_string, $modules, $offset, $max_results){
		$call_arguments = array(
				'session' => $this->session,
				'search_string' => $search_string,
				'modules' => $modules,
				'offset' => $offset,
				'max_results' => $max_results,
		);

		$result = $this->rest_request(
				'search_by_module', $call_arguments
		);

		return $result;
	}


	
	function is_logged_in()
	{
		return $this->logged_in;
	}

	function __destruct() {
		if($this->logged_in) {
			$l = $this->rest_request(
					'logout',
					array(
							'session' => $this->session
					)
			);
		}
	}
}
