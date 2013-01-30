<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title><?php echo Yii::t('Apply', 'Apply') . ' - ' . Yii::t('Toast', 'Toast'); ?></title>
        <link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap-responsive.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/apply.css" />
    </head>
    <body>
        <div class="signup-form container-fluid form-horizontal applied">
            <div class="row-fluid">
                <img src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/images/logo_signup.png" alt="Toast"/>
                <span class="signup-label pull-right">
                    <?php
                    echo Yii::t('Apply', 'Apply');
                    ?>
                </span>
                <hr />
            </div>
            <h3 class="text-success"><?php echo Yii::t('Apply', 'Apply success'); ?></h3>
            <p class="muted">
            <?php 
            echo Yii::t('Apply', 'Apply for product access has sended.');
            ?>
            </p>
            <p>
                <?php
                echo CHtml::link(Yii::t('Apply', 'Back to login'), array('site/logout'));
                ?>
            </p>
        </div>
    </body>
</html>