<?php 

require_once('codebird.php');

class CCodebird extends CApplicationComponent {
	
	// codebird instance
	protected static $_cb = NULL;
	
	// credentials
	protected static $_config = array(
			"consumerkey" => "PbU7bUAKJraOvlSf0ovQ",
			"consumersecret" => "TiH2ZNZ7Sh52jb3lwHCdxAFBr7hxX55CNXC2wLF0",
			"bearertoken" => "AAAAAAAAAAAAAAAAAAAAAAaTQAAAAAAArCRANEDm%2FAMXDj%2B%2F4ZWuHXXWdP0%3DDriwKbWjKjb0vUpnQsH5L1uczJRdgnxw1XJf2v4ZpE",
			"appauth" => true, // flag shows whether to use Application authentication with Twitter API. Set to false if User auth should be used
	);
	
	public function getCodebird() {
		if (self::$_cb == NULL) {
			Codebird::setConsumerKey(self::$_config["consumerkey"], self::$_config["consumersecret"]);
			if (self::$_config["appauth"]) {
				Codebird::setBearerToken(self::$_config["bearertoken"]);
			}			
			self::$_cb = Codebird::getInstance();
		}
		return self::$_cb;
	}
	
	public function getMultipleUserInfo($ids) {
		
		$result = array('success' => false, 'message' => 'Unknown error');
		
		$cb = self::getCodebird();
		
		$user_ids = implode(',', $ids);
		
		$userinfo = $cb->users_lookup(array("user_id" => $user_ids, "include_entities" => false), self::$_config["appauth"]);
		
		if ($userinfo->httpstatus == 200) {
			
			$result['success'] = true;
			$result['message'] = 'OK';
			
		} else {
			
			$result['message'] = 'Codebird can\'t process the users_lookup request: ' . $userinfo->errors[0]->code . ' - ' . $userinfo->errors[0]->message; 
				
		}
		
		$result['response'] = $userinfo;
		
		return $result;
		
	}
	
	public function getHandleInfo($handle, $findFriends = true) {
	
		$result = array('handle' => $handle, 'success' => false, 'message' => 'Unknown error', 'userinfo' => array());
		
		$cb = self::getCodebird();
		
		$userinfo = $cb->users_show(array("screen_name" => $handle, "include_entities" => false), self::$_config["appauth"]);
	
		if ($userinfo->httpstatus == 200) {
			
			if ($findFriends) {
				$friends = $cb->friends_ids(array("user_id" => $userinfo->id), self::$_config["appauth"]);
					
				if ($friends->httpstatus == 200) {
		
					$userinfo->friends_list = $friends->ids;
		
					while ($friends->httpstatus == 200 && $friends->next_cursor > 0) {
							
						$friends = $cb->friends_ids(array("user_id" => $userinfo->id, "cursor" => $friends->next_cursor), self::$_config["appauth"]);
						
						if ($friends->httpstatus == 200) {
							
							if (count($friends->ids) > 0) {
								array_push($userinfo->friends_list, $friends->ids);
							}							
							
						} else {
							
							$result['message'] = 'Codebird can\'t process the friends_ids subsequent request: ' . $friends->errors[0]->code . ' - ' . $friends->errors[0]->message;
							
						}						
							
					}
					
					$result['message'] = 'OK';
					$result['success'] = true;
		
				} else {
		
					$result['message'] = 'Codebird can\'t process the friends_ids request: ' . $friends->errors[0]->code . ' - ' . $friends->errors[0]->message;
		
				}
				
			} else {
				
				$userinfo->friends_list = array();
				$result['message'] = 'OK';
				$result['success'] = true;
				
			}
				
			$result['userinfo'] = $userinfo;
				
		} else {
				
			$result['message'] = 'Codebird can\'t process the users_show request: ' . $userinfo->errors[0]->code . ' - ' . $userinfo->errors[0]->message;
	
		}
	
		return $result;
	}
	
}

?>