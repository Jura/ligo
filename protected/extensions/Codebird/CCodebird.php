<?php 

require_once('codebird.php');

class CCodebird extends CApplicationComponent {
	
	// codebird instance
	protected static $_cb = NULL;

    protected static $_auth = false;

    public function __construct()
    {
        //initialize config
        if(isset(Yii::app()->params['codebird'])) {
            Codebird::setConsumerKey(Yii::app()->params['codebird']['consumerkey'], Yii::app()->params['codebird']['consumersecret']);
            if (Yii::app()->params['codebird']['appauth']) {
                Codebird::setBearerToken(Yii::app()->params['codebird']['bearertoken']);
            }
            self::$_cb = Codebird::getInstance();
            self::$_auth = Yii::app()->params['codebird']['appauth'];
        } else {
            throw new CException('please set appropriate Codebird variables in config');
        }
    }

    /*public function getCodebird() {
		Yii::trace(get_class($this).'.getCodebird()','ext.codebird');
		if (self::$_cb == NULL) {
			Codebird::setConsumerKey(self::$_config["consumerkey"], self::$_config["consumersecret"]);
			if (self::$_config["appauth"]) {
				Codebird::setBearerToken(self::$_config["bearertoken"]);
			}			
			self::$_cb = Codebird::getInstance();
		}
		return self::$_cb;
	}*/
	
	public function getMultipleUserInfo($ids) {
		Yii::trace(get_class($this).'.getMultipleUserInfo()','ext.codebird');
		
		$result = array('success' => false, 'message' => 'Unknown error');

        if (count($ids) > 0) {

            //$cb = self::getCodebird();

            $user_ids = implode(',', $ids);

            Yii::trace(get_class($this).'.findAll()','ext.codebird');

            $criteria = array("include_entities" => false);

            if (is_numeric($ids[0])) {
                $criteria['user_id'] = $user_ids;
            } else {
                $criteria['screen_name'] = $user_ids;
            }

            $userinfo = self::$_cb->users_lookup($criteria, self::$_auth);

            if ($userinfo->httpstatus == 200) {

                $result['success'] = true;
                $result['message'] = 'OK';

            } else {

                $result['message'] = 'Codebird can\'t process the users_lookup request: ' . $userinfo->errors[0]->code . ' - ' . $userinfo->errors[0]->message;

            }

            $result['response'] = $userinfo;

        } else {

            $result['success'] = true;
            $result['message'] = 'Nothing to process';
            $result['response'] = array();

        }

		return $result;
		
	}
	
	public function getHandleInfo($handle, $findFriends = true) {
		
		Yii::trace(get_class($this).'.getHandleInfo()','ext.codebird');
	
		$result = array('handle' => $handle, 'success' => false, 'message' => 'Unknown error', 'userinfo' => array());
		
		//$cb = self::getCodebird();
		
		$userinfo = self::$_cb->users_show(array("screen_name" => $handle, "include_entities" => false), self::$_auth);
	
		if ($userinfo->httpstatus == 200) {
			
			if ($findFriends) {
				$friends = self::$_cb->friends_ids(array("user_id" => $userinfo->id), self::$_auth);

                if ($friends->httpstatus == 200) {
		
					$userinfo->friends_list = $friends->ids;

                    $result['message'] = 'OK';
                    $result['success'] = true;

                    while ($friends->httpstatus == 200 && $friends->next_cursor > 0) {

						$friends = self::$_cb->friends_ids(array("user_id" => $userinfo->id, "cursor" => $friends->next_cursor_str), self::$_auth);

                        if ($friends->httpstatus == 200) {
							
							if (count($friends->ids) > 0) {
                                $userinfo->friends_list = array_merge($userinfo->friends_list, $friends->ids);
							}							
							
						} else {
							
							$result['message'] = 'Codebird can\'t process the friends_ids subsequent request: ' . $friends->errors[0]->code . ' - ' . $friends->errors[0]->message;
                            $result['success'] = false;
							
						}						
							
					}

		
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