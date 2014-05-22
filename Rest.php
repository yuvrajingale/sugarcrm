<?php
// namespace Rest;
class Rest {
	var $url, 

	$username, 

	$password, 

	$session, 

	$logged_in, 

	$method, 

	$parameters;
	
	private $UserId;
	
	// public function Rest($url, $username, $password){
	// $this->setUrl();
	// $this->setUsername();
	// $this->setPassword();
	// }
	// To set the Url
	public function setUrl($url = null) {
		$this->url = $url;
	}
	// To set the entered username
	public function setUsername($username = null) {
		$this->username = $username;
	}
	// To set the entered password
	public function setPassword($password = null) {
		$this->password = $password;
	}
	public function call($method, $parameters, $url) {
		ob_start ();
		$curl_request = curl_init ();
		
		curl_setopt ( $curl_request, CURLOPT_URL, $url );
		curl_setopt ( $curl_request, CURLOPT_POST, 1 );
		curl_setopt ( $curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
		curl_setopt ( $curl_request, CURLOPT_HEADER, 1 );
		curl_setopt ( $curl_request, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt ( $curl_request, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $curl_request, CURLOPT_FOLLOWLOCATION, 0 );
		
		$jsonEncodedData = json_encode ( $parameters );
		
		$post = array (
				"method" => $method,
				"input_type" => "JSON",
				"response_type" => "JSON",
				"rest_data" => $jsonEncodedData 
		);
		
		curl_setopt ( $curl_request, CURLOPT_POSTFIELDS, $post );
		$result = curl_exec ( $curl_request );
		curl_close ( $curl_request );
		
		$result = explode ( "\r\n\r\n", $result, 2 );
		$response = json_decode ( $result [1] );
		ob_end_flush ();
		
		return $response;
	}
	// Login with rest/sugarcrm
	public function login($username = NULL, $password = NULL) {
		$username = $this->username;
		
		$password = $this->password;
		
		$login_parameters = array (
				"user_auth" => array (
						"user_name" => $username,
						"password" => md5 ( $password ),
						"version" => "1" 
				),
				"application_name" => "VanareClient",
				"name_value_list" => array () 
		);
		
		$data = $this->call ( "login", $login_parameters, $this->url );
		
		if (isset ( $data->id )) {
			$this->session = $data->id;
		}
		return $data;
	}
	// Display the module data
	public function ModuleData($session, $module_name, $query, $order_by, $offset, $select_fields, $link_name_to_fields_array, $max_results, $deleted, $Favorites) {
		$parameters = array (
				
				'session' => $session,
				
				'module_name' => $module_name,
				
				'query' => $query,
				
				'order_by' => $order_by,
				
				'offset' => $offset,
				
				'select_fields' => $select_fields,
				
				'link_name_to_fields_array' => $link_name_to_fields_array,
				
				'max_results' => $max_results,
				
				'deleted' => $deleted,
				
				'Favorites' => $Favorites 
		);
		
		$data = $this->call ( 'get_entry_list', $parameters, $this->url );
		
		return $data;
	}
	
	// IMAP Function for Gmail
	public function HomeGmailImap($Gmailusername, $Gmailpassowrd) {
	}
	
	// Inserting the New Record
	public function ModuleSetEntry($session, $module_name, $name_value_list) {
		$set_contact_parameters = array (
				'session' => $session,
				
				// The name of the module from which to retrieve records.
				'module_name' => $module_name,
				
				// Record attributes
				'name_value_list' => $name_value_list 
		);
		$data = $this->call ( "set_entry", $set_contact_parameters, $this->url );
		
		return $data;
	}
	
	// TO Delete Record
	public function DeleteEntry($session, $module_name, $IdDelete) {
		$set_delete_parameters = array (
				'session' => $session,
				
				// The name of the module from which to retrieve records.
				'module_name' => $module_name,
				
				// Record attributes
				'name_value_list' => array (
						array (
								"name" => "id",
								"value" => $IdDelete 
						),
						array (
								"name" => "deleted",
								"value" => '1' 
						) 
				) 
		);
		$data = $this->call ( "set_entry", $set_delete_parameters, $this->url );
		
		return $data;
	}
	
	// To Update Record
	public function UpdateEntry($session, $module_name, $name_value_list) {
		$set_update_parameters = array (
				'session' => $session,
				
				// The name of the module from which to retrieve records.
				'module_name' => $module_name,
				
				// Record attributes
				'name_value_list' => $name_value_list 
		);
		$data = $this->call ( "set_entry", $set_update_parameters, $this->url );
		
		return $data;
	}
	// Search String in Module
	public function SearchModule($session, $search_string, $modules, $offset, $max_results) {
		$parameters = array (
				'session' => $session,
				'search_string' => $search_string,
				'modules' => $modules,
				'offset' => $offset,
				'max_results' => $max_results,
				/*'assigned_user_id' => $assigned_user_id,
					'unified_search_only' => $unified_search_only,
		'select_fields' => $select_fields,$assigned_user_id,$unified_search_only = FALSE,$select_fields*/
	
		);
		
		$data = $this->call ( "search_by_module", $parameters, $this->url );
		
		return $data;
	}
	/*
	 * public function SearchModule($session, $search_string, $modules, $offset, $max_results,$assigned_user_id,$unified_search_only) { $parameters = array ( 'session' => $session, 'search_string' => $search_string, 'modules' => $modules, 'offset' => $offset, 'max_results' => $max_results ); $data = $this->call ( "search_by_module", $parameters, $this->url ); return $data; }
	 */
	// Set relationship
	public function set_relationship($sesion, $module_name, $module_id, $link_field_name, $related_ids) {
		$paremeters = array (
				'session' => $session,
				'module_name' => $module_name,
				'module_id' => $module_id,
				'link_field_name' => $link_field_name,
				'related_ids' => array (
						$related_ids 
				) 
		);
		
		$data = $this->call ( "set_relationship", $parameters, $this->url );
		
		return $data;
	}
	
	// to set the relationship with attachements get_note_attachment
	public function set_note_attachment($session, $note_id, $file, $filename) {
		$parameters = array (
				'session' => $session,
				'note' => array (
						'id' => $note_id,
						'file' => $file,
						'filename' => $filename,
						'related_module_name' => 'Cases' 
				) 
		);
		
		$data = $this->call ( "set_note_attachment", $parameters, $this->url );
		
		return $data;
	}
	
	// get_note_attachment Download aatachment
	public function get_note_attachment($session, $note_id) {
		$parameters = array (
				'session' => $session,
				'id' => $note_id 
		);
		
		$data = $this->call ( "get_note_attachment", $parameters, $this->url );
		
		return $data;
	}
	// Get last viewed links/modules/references
	public function last_viewed($session, $modules) {
		$parameters = array (
				"session" => $session,
				
				'module_names' => $modules 
		);
		
		$data = $this->call ( "get_last_viewed", $parameters, $this->url );
		
		return $data;
	}
	
	// logout current session
	public function logout($session) {
		if (empty ( $session )) {
			return false;
		} else {
			$logout_parameters = array (
					// session id to expire
					"session" => $session 
			);
			$data = $this->call ( "logout", $logout_parameters, $this->url );
			
			return $data;
		}
	}
}

?>
