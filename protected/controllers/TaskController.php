<?php
/**
 * Class TaskController
 *
 * Tasks management
 */
class TaskController extends Controller {

    protected static $registered_tasks = array(
        'parseBareHandles',
    );

    public function actionRun() {

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

                foreach (self::$registered_tasks as $rt) {

                    if (is_callable(array($this, $rt))) {

                        $result = call_user_func(array($this, $rt), $task->start);
                        $task->status = $result->status;
                        $task->log = array_merge($task->log, $result->log);

                    } else {
                        die($rt);
                    }

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

    }

    // tasks
    protected function parseBareHandles($start) {

        $task = new Task;
        $task->start = $start;

        // get all bare handles
        $params = array(
            'conditions' => array(
                'userinfo' => array('notexists')
            ),
            'limit' => 500, // limit records to process to avoid cursor timeouts
        );
        $criteria = new EMongoCriteria($params);
        $handles = People::model()->findAll($criteria);
//var_dump($handles);die();
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
                if ($task->takesTooLong()) {
                    break;
                }
                /*if (Task::getTimestamp() > $start + $task->getMaxExec()) {
                    array_push($task->log, $task->formatLogString('Maximum execution time exceeded'));
                    $task->status = 'Finished with errrors';
                    break;
                }*/

                $codebird = new CCodebird;
                $result = $codebird->getMultipleUserInfo($bulk);

                if ($result['success']) {

                    foreach ($result['response'] as $key => $value) {

                        // prevent max execution time exceeded errors
                        if ($task->takesTooLong()) {
                            break 2;
                        }
                        /*if (Task::getTimestamp() > $start + $task->getMaxExec()) {
                            array_push($task->log, $task->formatLogString('Maximum execution time exceeded'));
                            $task->status = 'Finished with errrors';
                            break 2;
                            //throw new Exception('Maximum execution time exceeded');
                        }*/

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
                    $task->status = 'OK';

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

            }

        } else {

            array_push($task->log, $task->formatLogString('DB is saying: nothing to update'));
            $task->status = 'OK';

        }


        return $task;

    }

}
?>