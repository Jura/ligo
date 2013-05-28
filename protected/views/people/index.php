<?php

$this->pageTitle=Yii::app()->name;

?>

<div class="navbar">
    <div class="navbar-inner">
        <a class="brand" href="#" data-toggle="tooltip" data-placement="bottom" data-title="Loading..." data-trigger="manual"><?php echo Yii::app()->name; ?></a>
        <ul class="nav">
            <li class="dropdown" data-toggle="tooltip" data-placement="bottom" data-title="Data has been recorded" data-trigger="manual">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Suggest new accounts  <span class="caret"></span></a>
                <div class="dropdown-menu" style="padding: 10px;">
                    <form class="navbar-form" id="newhandles" action="<?php echo $this->createUrl('people/suggest'); ?>">
                        <label for="handles">Who do you want to add?</label>
                        <div class="control">
                            <input type="text" data-provide="tag" name="handles" id="handles">
                        </div>
                        <label for="groups">To what groups?</label>
                        <div class="control">
                            <input type="text" data-provide="tag" name="groups" id="groups" data-source='<?php echo CJSON::encode(array_keys($groups)); ?>'>
                        </div>
                        <label for="comments">Any comments (optional)?</label>
                        <div class="control">
                            <textarea name="comments" id="comments"></textarea>
                        </div>

                        <script>var RecaptchaOptions = {theme : 'custom',custom_theme_widget: 'recaptcha_widget'}, _remote_ip = '<?php echo Yii::app()->params['remoteip']; ?>';</script>
                        <div id="recaptcha_widget" style="display:none">

                            <div class="controls">
                                <a id="recaptcha_image" href="#" class="thumbnail"></a>
                                <div class="hide" id="recaptcha-error"></div>
                            </div>

                            <label class="recaptcha_only_if_image control-label">Enter the words above:</label>
                            <label class="recaptcha_only_if_audio control-label">Enter the numbers you hear:</label>

                            <div class="controls">
                                <div class="input-append">
                                    <input type="text" id="recaptcha_response_field" name="recaptcha_response_field" class="input-recaptcha" />
                                    <a class="btn" href="javascript:Recaptcha.reload()"><i class="icon-refresh"></i></a>
                                    <a class="btn recaptcha_only_if_image" href="javascript:Recaptcha.switch_type('audio')"><i title="Get an audio CAPTCHA" class="icon-headphones"></i></a>
                                    <a class="btn recaptcha_only_if_audio" href="javascript:Recaptcha.switch_type('image')"><i title="Get an image CAPTCHA" class="icon-picture"></i></a>
                                    <a class="btn" href="javascript:Recaptcha.showhelp()"><i class="icon-question-sign"></i></a>
                                </div>
                            </div>

                        </div>

                        <button type="submit" class="btn pull-right">Submit</button>
                    </form>
                </div>
            </li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Click me"><i class="icon-hand-up"></i></a>
                <div class="dropdown-menu" style="padding: 10px;">
                    Made by <a href="http://twitter.com/jurakhrapunov" target="_blank" title="Jura Khrapunov"><img src="https://si0.twimg.com/profile_images/2882222033/7777718f9d576e9747484be21439c2cf_normal.png"></a>
                    using <a href="http://www.yiiframework.com/" target="_blank" title="Yii framework"><img src="http://static.yiiframework.com/favicon.ico"></a> backed with <a href="http://www.mongodb.org/" target="_blank" title="MongoDB"><img src="http://www.mongodb.org/favicon.ico"></a>,
                    frontend based on <a href="http://twitter.github.io/" target="_blank" title="Bootstrap"><img src="http://twitter.github.io/bootstrap/assets/ico/favicon.png"></a> and <a href="http://d3js.org/" target="_blank" title="D3.js">D3</a>,
                    hosted on <a href="https://www.cloudcontrol.com/" target="_blank" title="cloudControl"><img src="https://www.cloudcontrol.com/favicon.png"></a></div>
            </li>
        </ul>
        <ul class="nav pull-right">
            <li class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">Available groups  <span class="caret"></span></a>
                <ul class="dropdown-menu">
                    <?php
                    foreach($groups as $key => $value) {
                        if ($value > 3) {
                            echo '<li><a href="' . Yii::app()->request->baseUrl . '/#' . $key . '"><span class="badge badge-info">' . $value . '</span> <b>' . $key . '</b></a></li>';
                        }
                    }
                    ?>
                </ul>
            </li>
            <li class="divider-vertical"></li>
            <li id="fps"></li>
        </ul>
    </div>
</div>

<div class="row-fluid">
    <div class="span8">
        <div id="graph"></div>
    </div>
    <div class="span4">
        <div id="toplist"></div>
    </div>
</div>

<?php

$api_endpoint = Yii::app()->request->baseUrl;

$script = <<<EOT

    // set graph container's height
    var _graph = $('#graph');
    _graph.height($(window).height() - _graph.offset().top - _graph.offset().left);

    var options = {
        'api_endpoint': '$api_endpoint',
        'group': window.location.hash.substring(1),
        'maxnodes': '$maxnodes'
    };

    ligo.init(options).renderStats('#toplist').renderGraph('#graph').monitorFps('#fps');

EOT;

Yii::app()->clientScript
    ->registerPackage('d3')
    ->registerScriptFile(Yii::app()->request->baseUrl.'/js/ligo.js')
    ->registerCssFile(Yii::app()->request->baseUrl.'/css/bootstrap-tag.css')
    ->registerScriptFile(Yii::app()->request->baseUrl.'/js/bootstrap-tag.js')
    ->registerScriptFile('https://www.google.com/recaptcha/api/challenge?k=' . Yii::app()->params['recaptcha_public_key'])
    ->registerCssFile(Yii::app()->request->baseUrl.'/css/main.css')
    ->registerScriptFile(Yii::app()->request->baseUrl.'/js/main.js')
    ->registerScript('ligoinit', $script, CClientScript::POS_READY);

?>