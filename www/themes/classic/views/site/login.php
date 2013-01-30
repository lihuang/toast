<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title><?php echo Yii::t('LoginForm', 'Login') . ' - ' . Yii::t('Toast', 'Toast'); ?></title>
        <link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap.min.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap-responsive.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/login.css" />
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/jquery.js"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/jquery.placeholder.js"></script>
        <script type="text/javascript">
        $(document).ready(function(){
            $("input").placeholder({force: true})
        })
        </script>
    </head>
    <body>
        <div class="login-form container-fluid">
            <div class="row-fluid">
                <div class="span5">
                    <img src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/images/logo_login.png" alt="Toast" class="logo"/>
                </div>
                <div class="span7">
                    <?php echo CHtml::beginForm(); ?>
                    <div class="row-fluid row-login-input">
                        <?php
                        echo CHtml::activeTextField($loginForm, 'username', array(
                            'class' => 'span12',
                            'placeholder' => $loginForm->getAttributeLabel('username'),
                            'title' => $loginForm->getAttributeLabel('username'),
                        ));
                        ?>
                    </div>
                    <div class="row-fluid">
                        <?php
                        echo CHtml::activePasswordField($loginForm, 'password', array(
                            'class' => 'span12',
                            'placeholder' => $loginForm->getAttributeLabel('password'),
                            'title' => $loginForm->getAttributeLabel('password'),
                        ));
                        ?>
                    </div>
                    <div class="row-fluid row-info-error">
                        <?php
                        echo CHtml::error($loginForm, 'username', array('class' => 'text-error'));
                        echo CHtml::error($loginForm, 'password', array('class' => 'text-error'));
                        ?>
                    </div>
                    <div class="row-fluid">
                        <?php
                        echo CHtml::submitButton(Yii::t('LoginForm', 'Login'),  array('class' => 'btn btn-primary'));
                        echo CHtml::link(Yii::t('LoginForm', 'No account'), array('signup'), array(
                            'class' => 'signup',
                            'target' => '_blank'
                        ));
                        ?>
                    </div>
                    <?php echo CHtml::endForm(); ?>
                </div>
            </div>
        </div>
    </body>
</html>