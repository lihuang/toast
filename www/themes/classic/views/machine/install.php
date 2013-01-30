<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/reset.css"/>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/style.css"/>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/status.css"/>
        <?php Yii::app()->getClientScript()->registerCoreScript('jquery');?>
        <title><?php echo Yii::t('Machine', 'Add Machine'); ?></title>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/config.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lang.js"></script>
        <script type="text/javascript">
        $(document).ready(function(){
            inputFocus();
            var data = {id: $("input#Machine_id").val()}
            setInterval(function(){
                $.getJSON(toast.getMachineStatus, data, function(json){
                    $("span#status").text(json.status);
                    $("span#status").attr('class', json.clazz);
                })
            }, toast.heartbeat);
        });
        </script>
    </head>
    <body>
        <div id="content">
            <div id="main-detail" style="margin:0; height: 390px; min-height: 0; min-width: 0;">
                <table style="background: #6694E2; width: 100%; text-align: left">
                    <tr>
                        <th style="font-size: 14px; font-weight: bold; color: #CCCCCC; width: 430px"><?php echo Yii::t('Machine', 'Input machine info'); ?></th>
                        <th style="font-size: 14px; font-weight: bold; color: #FFFFFF; "><?php echo Yii::t('Machine', 'Run toast agent install script'); ?></th>
                     </tr>
                </table>
                <div class="last">
                    <div style="padding: 20px; font-size: 14px;">
                        <div>
                        <?php
                        echo Yii::t('Machine', 'Run script at {machine}', array('{machine}' => $machine->name));
                        echo CHtml::activeHiddenField($machine, 'id');
                        ?>
                        </div>
                        <div style="padding: 10px; background: #CFCFCF; margin-bottom: 20px;">
                        <?php
                        echo 'wget <a href="http://toast.corp.taobao.com/machine/getInstallScript/id/' . $machine->id . '" target="_blank">http://toast.corp.taobao.com/machine/getInstallScript/id/' . $machine->id . '</a>  -O ./toast.py<br/>'
                        .'sudo python ./toast.py';
                        ?>
                        </div>
                        <div><?php echo Yii::t('Machine', 'Install Specification'); ?></div>
                        <div style="margin-top: 5px; padding: 10px; text-align: center; background: #CFCFCF; "><?php echo '<span style="padding-right: 40px">' . $machine->name . '</span><span id="status" style="padding: 3px 5px" class="' . $machine->getStatusStyle() . '">' . $machine->getStatusText() . '</span>'; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>