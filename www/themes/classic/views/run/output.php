<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <?php Yii::app()->getClientScript()->registerCoreScript('jquery');?>
        <title>
            <?php
            if(isset($run->command)) {
                echo $run->command->name;
            } else if(isset($run->suite)) {
                echo $run->suite->name;
            }
            echo ' ' . Yii::t('TOAST', 'Run Label') . '#' . $run->id . ' ' . $run->getAttributeLabel('output'); 
            ?>
        </title>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/config.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lang.js"></script>
        <style type="text/css">
        <!--
            body { background:#FFFFDD; color: #000000; font: 13px/1.5 arial; }
            pre { margin: 1em 0px; }
        -->
        </style>
        <script type="text/javascript">
        $(document).ready(function(){
            var setOutput = function(data){
                var flag = "OUTPUT:";
                var pos = data.indexOf(flag);
                if(pos != -1)
                {
                    if($(document).height() == $(this).scrollTop() + $(window).height())
                        var scrollFlag = true;
                    var json = eval("(" + data.substr(0, pos) + ")");
                    var output = data.substr(pos + flag.length);
                    $("pre").text(output);
                    if(json.hascompleted) {
                        $("pre").append(lang.runCompleted);
                        clearInterval(outputInt);
                    }
                    if(scrollFlag)
                        $(this).scrollTop($(document).height());
                }
                else
                {
                    clearInterval(outputInt);
                }
            };
            
            var data = {id : $("input#CommandRun_id").val()};
            $.get(toast.getRunOutput, data, function(data){
                setOutput(data);
            });
            var outputInt = setInterval(function(){
                $.get(toast.getRunOutput, data, function(data){
                    setOutput(data);
                });
            }, toast.heartbeat);
        });
        </script>
    </head>
    <body>
        <?php echo CHtml::activeHiddenField($run, 'id'); ?>
        <pre></pre>
    </body>
</html>