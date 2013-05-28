<?php

class SandboxController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	//public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	/*public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}*/

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	/*public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view','suggested','login'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}*/

    public function actionSave($id, $groups = '') {

        if (!isset(Yii::app()->session['oauth_verified'])) {

            throw new CHttpException(401,'Unauthorized, please reload the page to proceed');

        } else {

            $g = array_values(array_unique(preg_split("/\W/", $groups, -1, PREG_SPLIT_NO_EMPTY)));

            $doc = Sandbox::model()->findByPk(new MongoID($id));

            if ($doc->count() > 0) {

                // check if the handle is already in database (case-insensitive)
                $criteria = new EMongoCriteria;
                $criteria->handle = new MongoRegex('/^' . $doc->handle . '$/i');
                $people = People::model()->find($criteria);

                if ($people->count() > 0 && isset($people->userinfo) && isset($people->userinfo['friends_list']) && count($people->userinfo['friends_list']) > 0) {

                    $newgroups = array_diff($g, $people->groups);

                    if (count($newgroups) > 0) {

                        $people->groups = array_merge($people->groups, $newgroups);
                        $people->update(array('groups'));

                        $result = array('handle' => $doc->handle, 'success' => true, 'message' => 'Groups have been updated');

                    } else {

                        $result = array('handle' => $doc->handle, 'success' => false, 'message' => 'Nothing to change, handle is already in database');

                    }

                } else {
                    $codebird = new CCodebird;
                    $result = $codebird->getHandleInfo($doc->handle);
                    if ($result['success']) {

                        if ($people->count() < 1) {
                            $people = new People;
                            $people->twitter_id = $result['userinfo']->id;
                        }

                        $people->handle = $result['userinfo']->screen_name;
                        $people->groups = $g;
                        $people->timestamp = time();
                        $people->userinfo = (array)$result['userinfo'];

                        $people->save();

                        // insert all new handles from friends list into db for further parsing
                        if (isset($result['userinfo']->friends_list) && count($result['userinfo']->friends_list) > 0) {
                            $people->insertBareHandles($result['userinfo']->friends_list);
                        }
                    } else {

                        throw new CHttpException(503,$result['message']);

                    }

                }

                $doc->delete();


            } else {

                throw new CHttpException(404,'Record not found');

            }

            $this->renderPartial('//layouts/json', array('content' => $result));

        }

    }

    public function actionLogin($oauth_verifier){

        if (Yii::app()->session['oauth_token'] && Yii::app()->session['oauth_token_secret']) {

            $codebird = new CCodebird(false);

            $reply = $codebird->verifyUser(Yii::app()->session['oauth_token'], Yii::app()->session['oauth_token_secret'], $oauth_verifier);

            Yii::app()->session['oauth_token'] = $reply->oauth_token;
            Yii::app()->session['oauth_token_secret'] = $reply->oauth_token_secret;
            Yii::app()->session['oauth_screen_name'] = $reply->screen_name;
            Yii::app()->session['oauth_verified'] = true;

            $this->redirect(array(Yii::app()->session['oauth_return_url']));

        } else {

            throw new CException('This page is a callback for Twitter Oauth sign in and cannot be called directly');

        }

    }

    public function actionSuggested() {

        // check if the user is logged in and authorised to use function
        if (!isset(Yii::app()->session['oauth_verified'])) {

            /*if (in_array(Yii::app()->session['oauth_screen_name'], Yii::app()->params['admin_twitter_handle'])) {

                $handles = Sandbox::model()->findAll();

            }

        } else {*/

            $codebird = new CCodebird(false);
            $reply = $codebird->requestToken();
            Yii::app()->session['oauth_token'] = $reply['oauth_token'];
            Yii::app()->session['oauth_token_secret'] = $reply['oauth_token_secret'];
            Yii::app()->session['oauth_auth_url'] = $reply['auth_url'];
            Yii::app()->session['oauth_return_url'] = 'sandbox/suggested';

        }

        $this->render('suggested');
    }

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	/*public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}*/

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	/*public function actionCreate()
	{
		$model=new Sandbox;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Sandbox']))
		{
			$model->attributes=$_POST['Sandbox'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->_id));
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}*/

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	/*public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Sandbox']))
		{
			$model->attributes=$_POST['Sandbox'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->_id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}*/

	/**
	 * Deletes a particular model.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
        if (!isset(Yii::app()->session['oauth_verified'])) {

            throw new CHttpException(401,'Unauthorized, please reload the page to proceed');

        } else {

            if(Yii::app()->request->isPostRequest)
            {
                // we only allow deletion via POST request
                $this->loadModel($id)->delete();

                // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
                //if(!isset($_GET['ajax']))
                //    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
            }
            else {
                throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
            }

        }
	}

	/**
	 * Lists all models.
	 */
	/*public function actionIndex()
	{
		$dataProvider=new EMongoDocumentDataProvider('Sandbox');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}*/

	/**
	 * Manages all models.
	 */
	/*public function actionAdmin()
	{
		$model = new Sandbox('search');
		$model->unsetAttributes();

		if(isset($_GET['Sandbox']))
			$model->setAttributes($_GET['Sandbox']);

		$this->render('admin', array(
			'model'=>$model
		));
	}*/

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	/*public function loadModel($id)
	{
		$model=Sandbox::model()->findByPk(new MongoId($id));
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}*/

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	/*protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='sandbox-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}*/
}
