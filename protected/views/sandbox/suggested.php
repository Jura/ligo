<?php
//Yii::app()->session['oauth_verified'] = true;
//Yii::app()->session['oauth_screen_name'] = 'jurakhrapunov';

$this->pageTitle=Yii::app()->name;

// check if the user is logged in
if (isset(Yii::app()->session['oauth_verified'])) {

    if (in_array(Yii::app()->session['oauth_screen_name'], Yii::app()->params['admin_twitter_handle'])) {
        $model = Sandbox::model();
        $model->setUseCursor(false);
        $dataProvider = new EMongoDocumentDataProvider($model);
        $dataProvider->pagination = false;

        $this->widget('EBootstrapGridView', array(
            'id'=>'sandbox-grid',
            'dataProvider' => $dataProvider,
            //'updateSelector'=>'{page}, {sort}, .sandbox-action',
            //'filter'=>$model,
            'columns'=>array(
                array(
                    'class' => 'CLinkColumn',
                    'header' => 'Twitter handle',
                    'labelExpression' => '"<b>@".$data->handle."</b>"',
                    'urlExpression' => '"https://twitter.com/".$data->handle',
                    'linkHtmlOptions' => array('target' => '_blank'),
                ),
                array(
                    'name' => 'groups',
                    'value' => '"<input type=\"text\" class=\"sandbox-groups\" data-provide=\"tag\" value=\"" . implode(",", $data->groups) . "\">"',
                    //'value' => '"<span class=\"label sandbox-group\"><button class=\"close\">&times;</button><span>" . implode("</span></span> <span class=\"label sandbox-group\"><button class=\"close\">&times;</button><span>", $data->groups) . "</span></span> "',
                    'type' => 'raw',
                ),
                array(
                    'class'=>'EBootstrapButtonColumn',
                    //'deleteButtonUrl' => '$this->createUrl("sandbox/delete") . "?_id=" . $data->_id',
                    'buttons' => array(
                        'save' => array(
                            'label' => '<i class="icon-save"></i>',
                            'url' => 'Yii::app()->getURLManager()->createUrl("sandbox/save", array("id" => $data->_id))', // . "/sandbox/save?id=" . $data->_id
                            'options' => array(
                                'class' => 'btn btn-success sandbox-action',
                                'title' => 'Save',
                            ),
                        ),
                        'delete' => array(
                            'label' => '<i class="icon-remove"></i>',
                            //'url' => '"#".$data->_id',
                            'options' => array(
                                'class' => 'btn btn-danger sandbox-action',
                                'title' => 'Delete',
                            ),
                        ),

                    ),
                    'template' => '{save} {delete}',
                ),
                'remoteip',
            ),
            'pager' => array(
                'class' => 'EBootstrapLinkPager',
                'header' => false,
            ),
            //'pagerAlign' => 'centered',
            'bordered' => true,
            'striped' => true,
            //'enablePagination' => false,
            //'condensed' => false,
        ));

$script = <<<EOT

$(document).on('click', '.sandbox-action', function(e) {

    e.preventDefault();

    var tr = $(this).parents('tr'),
        groups = tr.find('.sandbox-groups').val(),
        action = $(this).attr('href');

    if ($.trim(groups) != '') {
        $.ajax(action, {
            data: {groups: groups},
            success: function(data, status) {tr.remove();},
            error: function() {window.location.reload(true);} // this should be called if access is denied
        });
    } else {
        alert('Please indicate at least one group');
    }

    return false;

});

EOT;

        Yii::app()->clientScript
            ->registerCssFile(Yii::app()->request->baseUrl.'/css/font-awesome.min.css')
            ->registerCssFile(Yii::app()->request->baseUrl.'/css/bootstrap-tag.css')
            ->registerScriptFile(Yii::app()->request->baseUrl.'/js/bootstrap-tag.js')
            ->registerScript('sandbox', $script, CClientScript::POS_READY);


    } else {

        echo 'Access denied, contact <a href="mailto:' . Yii::app()->params['adminEmail'] . '">admin</a> if you believe it is a mistake';

    }

} else {

    // display Twitter login button
    echo '<div align="center"><a class="btn btn-large btn-primary" href="' . Yii::app()->session['oauth_auth_url'] . '"><i class="icon-twitter icon-large"></i> Sign in with Twitter</a></div>';

}

?>