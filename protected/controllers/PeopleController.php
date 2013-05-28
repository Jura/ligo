<?php

class PeopleController extends Controller {
	
	// default group
	protected static $_group = 'UNDP';
	
	public function actionIndex($group = '', $maxnodes = 50) {

        $people = new People;
        $groups = $people->getPopulatedGroups();
        $this->render('index', compact('group', 'maxnodes', 'groups'));
		
	}
	
	public function actionGetNodesAndLinks($group=NULL, $maxnodes=250) {
		
		if ($group == NULL) {
			$group = self::$_group;
		}
		
		$nodes = array();
		$links = array();
		
		$criteria = new EMongoCriteria;
		$criteria->groups('==', new MongoRegex('/^' . $group . '$/i'))->select(array('twitter_id', 'handle', 'groups', 'userinfo.friends_list', 'userinfo.profile_image_url_https'));
		$handles = People::model()->findAll($criteria);
		
		$track = array();

		// combine all friends together
		$allfriends = array();
		foreach ($handles as $handle) {
			$allfriends = array_merge($allfriends, $handle->userinfo['friends_list']);
		}

        // create initial popularity rating
		$popularity = array_count_values($allfriends);
		
		$allmembers = array();
		foreach ($handles as $handle) { 
			if (isset($popularity[$handle->twitter_id])) {
				$popularity[$handle->twitter_id]++;
			} else {
				$popularity[$handle->twitter_id] = 0;
			}
			array_push($allmembers, $handle->twitter_id);
		}
		
		// reduce amount of nodes/links to display
        arsort($popularity, SORT_NUMERIC);
        $popular = array_slice($popularity, 0, $maxnodes, true);
        $options = array(
            'max' => (count($popular) > 0) ? max($popular) : 0,
            'min' => (count($popular) > 0) ? min($popular) : 0,
            'members' => count($allmembers),
            'friends' => count($allfriends),
            'group' => $group,
            'maxnodes' => $maxnodes,
        );

         // find unresolved handles
        $unresolved = array_diff(array_keys($popular), $allmembers);

        $params = array(
            'conditions' => array(
                'userinfo' => array('exists'),
                'twitter_id' => array('in' => $unresolved),
            ),
            'select' => array('twitter_id', 'handle', 'groups','userinfo.profile_image_url_https'),
        );
		$criteria = new EMongoCriteria($params);
		$dbpool = People::model()->findAll($criteria);
		
		$pool = array();
		foreach ($dbpool as $h) {
			$pool[$h->twitter_id] = array(
				'id' => (string)$h->twitter_id,
				'handle' => $h->handle,
				'image' => $h->userinfo['profile_image_url_https'],
				'size' => $popular[$h->twitter_id],
				'group' => false,			
			);
		}
		
		foreach ($handles as $handle) {

            if (isset($popular[$handle->twitter_id] )) {
                if (isset($track[$handle->twitter_id])) {
                    $target = $track[$handle->twitter_id];
                    if (!isset($nodes[$target]['handle'])) {
                        $nodes[$target]['handle'] = $handle->handle;
                        $nodes[$target]['image'] = $handle->userinfo['profile_image_url_https'];
                        $nodes[$target]['group'] = true;
                    }
                } else {
                    $target = array_push($nodes, array(
                        'id' => (string)$handle->twitter_id,
                        'handle' => $handle->handle,
                        'image' => $handle->userinfo['profile_image_url_https'],
                        'size' => $popular[$handle->twitter_id],
                        'group' => true,
                    )) - 1;
                    $track[$handle->twitter_id] = $target;
                }

            } else {
                $target = -1;
            }

            foreach ($handle->userinfo['friends_list'] as $friend) {

                if (isset($popular[$friend])) {

                    if (!isset($track[$friend])) {
                        $track[$friend] = (isset($pool[$friend])) ?
                            $track[$friend] = array_push($nodes, $pool[$friend])-1 :
                                array_push($nodes, array('id' => (string)$friend, 'size' => $popular[$friend], 'group' => false ))-1;
                    }

                     // insert link
                    if ($target >= 0) { //&& isset($track[$friend])
                        array_push($links, array(
                            'target' => $target,
                            'source' => $track[$friend]
                        ));
                    }

                }

            }
		}

        //krsort($options['top'], SORT_NUMERIC);
		$this->renderPartial('//layouts/json', array('content' => compact('nodes','links','options')));
	}

    public function actionSuggest() {//$handles = '', $groups = '', $comments = '', $recaptcha_remoteip = '', $recaptcha_challenge = '', $recaptcha_response = ''

        // turn lists into arrays
        $_handles = array_values(array_unique(preg_split("/\W/", Yii::app()->getRequest()->getParam('handles'), -1, PREG_SPLIT_NO_EMPTY)));
        $_groups = array_values(array_unique(preg_split("/\W/", Yii::app()->getRequest()->getParam('groups'), -1, PREG_SPLIT_NO_EMPTY)));

        // process CAPTCHA
        $rc = new CRecaptcha;
        $recaptcha = $rc->check_answer(Yii::app()->getRequest()->getParam('recaptcha_remoteip'), Yii::app()->getRequest()->getParam('recaptcha_challenge'), Yii::app()->getRequest()->getParam('recaptcha_response'));

        $content = array(
            'recaptcha' => array(
                'isValid' => $recaptcha->is_valid,
                'error' => $recaptcha->error,
            ),
            'success' => $recaptcha->is_valid,
            'message' => ($recaptcha->error == null) ? 'OK' : $recaptcha->error,
        );

        if ($recaptcha->is_valid && count($_handles) > 0 && count($_groups > 0)) {

            // search for the handles already in database
            $params = array(
                'conditions' => array(
                    'handle' => array(
                        'in' => $_handles
                    ),
                    'groups' => array(
                        'all' => $_groups
                    ),
                ),
                'select' => array('handle')
            );

            $existing = array();
            $people = People::model()->findAll($params);
            if ($people->count() > 0) {
                foreach($people as $p) {
                    array_push($existing, $p->handle);
                }
            }
            $sandbox = Sandbox::model()->findAll($params);
            if ($sandbox->count() > 0) {
                foreach($sandbox as $s) {
                    array_push($existing, $s->handle);
                }
            }

            $new = array_diff($_handles, $existing);

            if (count($new) > 0) {

                foreach ($new as $h) {
                    $doc = new Sandbox;
                    $doc->handle = $h;
                    $doc->groups = $_groups;
                    $doc->ts = Task::getTimestamp();
                    $doc->remoteip = Yii::app()->getRequest()->getParam('recaptcha_remoteip');
                    $doc->save();
                }

                $content['message'] = 'Thank you for submission, ' . count($new) . ' new Twitter handle' . ( (count($new) % 10 == 1) ? ' is' : 's are' ) . ' queued for addition to ' . implode(', ', $_groups) . ' groups.';

                // Notify admin about new additions
                $mail = new YiiMailer('notification', array(
                    'description' => 'New Twitter handles suggestion',
                    'name' => Yii::app()->getRequest()->getParam('recaptcha_remoteip'),
                    'message' => 'Handles <b>@' . implode(', @', $_handles) . '</b> proposed to add to <b>' . implode(', ', $_groups) . '</b> groups',
                ));
                $mail->render();
                $mail->Subject = Yii::app()->name . ': New Twitter handles notification';
                $mail->AddAddress(Yii::app()->params['adminEmail']);

                if (!$mail->Send()) {
                    $mail->ClearAddresses();
                    $content['message'] .= ' Email wasn\'t sent: ' . $mail->ErrorInfo;
                }

            } else {

                $content['message'] = 'Nothing to add, all handles already in database';

            }

            $content['success'] = true;

        } else {

            $content['success'] = false;
            $content['message'] = 'Please submit correct values';

        }

        $this->renderPartial('//layouts/json', compact('content'));

    }


	/*public function actionSuggestInternallll($handle = '', $group = '') {
		
		// turn lists into arrays
		$handles = array_values(array_unique(preg_split("/\W/", $handle, -1, PREG_SPLIT_NO_EMPTY)));
		$groups = array_values(array_unique(preg_split("/\W/", $group, -1, PREG_SPLIT_NO_EMPTY)));
		
		$messages = array();

		foreach ($handles as $h) {
		
			// check if the handle is already in database (case-insensitive)			
			$criteria = new EMongoCriteria;
			$criteria->handle = new MongoRegex('/^' . $h . '$/i');
			$people = People::model()->find($criteria);

			if ($people->count() > 0 && isset($people->userinfo) && isset($people->userinfo['friends_list']) && count($people->userinfo['friends_list']) > 0) {
				
				$newgroups = array_diff($groups, $people->groups);
					
				if (count($newgroups) > 0) {
					
					$people->groups = array_merge($people->groups, $newgroups);
					$people->update(array('groups'));
					
					array_push($messages, array('handle' => $h, 'success' => true, 'message' => 'Groups have been updated'));
		
				} else {
		
					array_push($messages, array('handle' => $h, 'success' => false, 'message' => 'Nothing to change, handle is already in database'));
		
				}
					
			} else {
				$codebird = new CCodebird;
				$result = $codebird->getHandleInfo($h);
				if ($result['success']) {
		
					if ($people->count() < 1) {
						$people = new People;
						$people->twitter_id = $result['userinfo']->id;
					}
					
					$people->handle = $result['userinfo']->screen_name;
					$people->groups = $groups;
					$people->timestamp = time();
					$people->userinfo = (array)$result['userinfo'];
					
					$people->save();
					
					// insert all new handles from friends list into db for further parsing
					if (isset($result['userinfo']->friends_list) && count($result['userinfo']->friends_list) > 0) {
						$people->insertBareHandles($result['userinfo']->friends_list);
					}					
					
				}
					
				array_push($messages, $result);
					
			}
		}
		
		$this->renderPartial('//layouts/json', array('content' => $messages));		
		
	}*/
	
	/*public function actionParseBareHandlers() {
		
		$max_exec = ((int) ini_get('max_execution_time') - 10) * 1000; // have a bit of reserve time to finish the logging 
		
		$criteria = new EMongoCriteria;
		$criteria->sort('ts', EMongoCriteria::SORT_DESC);
		$recent = Task::model()->find($criteria);
		
		// do anything only in case there is no active process or previous process finished (got stuck) for at least 1 hour
		if (count($recent) < 1 || is_numeric($recent->finish) ||  Task::getTimestamp() - $recent->start > 60*60*1000) {
			
			// register new task
			$task = new Task;
			$task->setStart();
			array_push($task->log, $task->formatLogString('started'));
			$task->status = 'active';
			$task->save();
			
			try {
				
			
				// get all bare handles
				$params = array(
					'conditions' => array(
						'userinfo' => array('notexists')
					),
					'limit' => 500, // limit records to process to avoid cursor timeouts
				);
				$criteria = new EMongoCriteria($params);
				$handles = People::model()->findAll($criteria);
				
				if ($handles->count() > 0) {
					
					$user_id = array();
                    $user_handle = array();
					
					foreach ($handles as $handle) {
						
						// extract IDs
                        if (is_numeric($handle->twitter_id)) {
                            array_push($user_id, $handle->twitter_id);
                        } else if (strlen($handle->handle) > 0) {
                            array_push($user_handle, $handle->handle);
                        }
					}
					
					// 100 is a limit by Twitter API for bulk user info resolution
					$ids = array_merge(array_chunk($user_id, 100), array_chunk($user_handle, 100));

					foreach ($ids as $bulk) {
						
						// prevent max execution time exceeded errors
						if (Task::getTimestamp() > $task->start + $max_exec) {
								array_push($task->log, $task->formatLogString('Maximum execution time exceeded'));
								$task->status = 'Finished with errrors';
								break;
							//throw new Exception('Maximum execution time exceeded');
						}

                        $codebird = new CCodebird;
                        $result = $codebird->getMultipleUserInfo($bulk);
						
						if ($result['success']) {
							
							foreach ($result['response'] as $key => $value) {
								
								// prevent max execution time exceeded errors
								if (Task::getTimestamp() > $task->start + $max_exec) {
									array_push($task->log, $task->formatLogString('Maximum execution time exceeded'));
									$task->status = 'Finished with errrors';
									break 2;
									//throw new Exception('Maximum execution time exceeded');
								}
								
								// parse only userinfo objects
								if (is_numeric($key)) {

                                    if (is_numeric($bulk[0])) {
                                        $doc = People::model()->findByAttributes(array('twitter_id' => $value->id));
                                        $doc->handle = $value->screen_name;
                                    } else {
                                        $doc = People::model()->findByAttributes(array('handle' => $value->screen_name));
                                        $doc->twitter_id = $value->id;
                                    }
                                    // bare record
									if (!isset($doc->groups)) {
										$doc->groups = array();
									}
									$doc->timestamp = Task::getTimestamp();
									$doc->userinfo = (array)$value;
									if (!isset($doc->userinfo['friends_list'])) {
										$doc->userinfo['friends_list'] = array();
									}
									$doc->save();
									
								}
								
							}						
							
							array_push($task->log, $task->formatLogString(count($bulk) . ' twitter IDs processed'));
							
						} else {
							
							$task->status = 'Finished with errrors';
							
							// try to clean up database from dead souls (suspended or deleted users), one record at a time
							if ($result['response']->httpstatus == 403 || $result['response']->httpstatus == 404) {

                                $field = (is_numeric($bulk[0])) ? 'twitter_id' : 'handle';

                                $doc = People::model()->findByAttributes(array($field => $bulk[0]));

								$doc->delete();
								
								array_push($task->log, $task->formatLogString('ID: ' . $bulk[0] . ' has been removed from database since its returned API error: ' . $result['message']));
								
								
							} else {
								
								array_push($task->log, $task->formatLogString('Twitter API error: ' . $result['message']));
								break;
								
							}							
							
						}
						
						$task->status = 'OK';
							
					}				
					
				} else {
					
					array_push($task->log, $task->formatLogString('DB is saying: nothing to update'));
					$task->status = 'OK';
					
				}
			
				$this->renderPartial('//layouts/json', array('content' => array('status' => 'OK', 'message' => 'Success')));
				
			} catch (Exception $e) {
				
				$this->renderPartial('//layouts/json', array(
					'content' => array(
						'status' => 'Failed', 
						'message' => 'PHP Exception raised', 
						'exception' => array(
							'message' => $e->getMessage(), 
							'trace' => $e->getTrace(),
							'code' => $e->getCode(),
							'file' => $e->getFile(),
							'line' => $e->getLine(),
						)
					)
				));
				array_push($task->log, $task->formatLogString('abnormal termination due to the PHP error: ' . $e->getMessage()));
				$task->status = 'Failed';
				
			}
			
			// finalize the task, regardless the result
			array_push($task->log, $task->formatLogString('finished'));
			$task->setFinish();
			$task->save();
			
		} else {
			
			$this->renderPartial('//layouts/json', array('content' => array('status' => 'busy', 'message' => 'Another process is active, come back later')));
			
		}
		
	}*/

    /*public function actionMapFriendsTest(){

        $people = new People;
        $col = $people->model()->getCollection();

        $frineds = $col->distinct('userinfo.friends_list');
        $ids = $col->distinct('twitter_id');
        //ini_set('max_execution_time', '120');
        $result = $people->insertBareHandles(array_diff($frineds, $ids));

        var_dump(MongoDB::lastError());
        die();
        //$result = ;
        //$this->renderPartial('//layouts/json', compact('result'));
    }*/

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}
	
	
}
?>