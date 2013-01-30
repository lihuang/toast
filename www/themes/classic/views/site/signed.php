<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title><?php echo Yii::t('SignUpForm', 'Signup') . ' - ' . Yii::t('Toast', 'Toast'); ?></title>
        <link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap-responsive.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/signup.css" />
    </head>
    <body>
        <div class="signup-form container-fluid form-horizontal signed">
            <div class="row-fluid">
                <img src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/images/logo_signup.png" alt="Toast"/>
                <span class="signup-label pull-right">
                    <?php
                    echo Yii::t('SignUpForm', 'Signup');
                    ?>
                </span>
                <hr />
            </div>
            <h3 class="text-success"><?php echo Yii::t('SignUpForm', 'Sign up success'); ?></h3>
            <p class="lead">
            <?php 
            echo Yii::t('SignUpForm', 'Account {username} sign up success.', array(
                '{username}' => $username,
            ));
            ?>
            </p>
            <p class="muted">
            <?php 
            echo Yii::t('SignUpForm', 'Apply for product access has sended.');
            ?>
            </p>
            <p>
                <?php
                echo CHtml::link(Yii::t('SignUpForm', 'Back to login'), array('site/logout'));
                ?>
            </p>
        </div>
    </body>
</html>