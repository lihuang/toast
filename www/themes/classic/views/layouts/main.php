<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/reset.css?ver=1"/>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/style.css?ver=1"/>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/tree.css?ver=1"/>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/status.css?ver=1"/>
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/bootstrap.css" />
        <?php Yii::app()->getClientScript()->registerCoreScript('jquery');?>
        <title><?php echo Yii::t('TOAST', 'TOAST'); ?></title>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.js?ver=2"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.cookie.js?ver=1"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.treeview.js?ver=1"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/config.js?ver=1"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lang.js?ver=1"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/bootstrap.js"></script>
    </head>
    <body>
        <div class="bg">
            <div class="header">
                <div class="logo">
                    <a href="<?php echo Yii::app()->baseUrl . '/task';?>">
                        <img alt="TOAST" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/images/page_logo.png" />
                    </a>
                </div>
                <?php
                $this->widget('zii.widgets.CMenu',array(
                    'id' => 'nav',
                    'items' => array(
                        array('label' => Yii::t('TOAST', 'Auto Task Label'), 'url' => array('/task'), 
                            'active' => ('task' == $this->getId() || 'run' == $this->getId() || 'command' == $this->getId())),
                        array('label' => Yii::t('TOAST', 'Task Report Label'), 'url' => array('/report'), 
                            'active' => ('report' == $this->getId())),
                        array('label' => Yii::t('TOAST', 'Test Case Label'),  'url' => array('/case'), 
                            'active' => 'case' == $this->getId()), 
                        array('label' => Yii::t('TOAST', 'Test Machine Label'),  'url' => array('/machine'), 
                            'active' => ('machine' == $this->getId())),

                    )
                ));
                $this->widget('zii.widgets.CMenu', array(
                    'id' => 'user',
                    'encodeLabel' => false,
                    'items' => array(
                        array('label' => Yii::app()->user->realname . '(' . Yii::app()->user->username . ')', 
                            'url' => array('/admin/user/update', 'id' => Yii::app()->user->id),
                            'visible' => !Yii::app()->user->isGuest, 
                            'linkOptions' => array('id' => 'username')),
                        array('label' => Yii::t('TOAST','Admin Entrance'), 'url' => array('/admin'), 'visible' => !Yii::app()->user->isGuest && Yii::app()->user->isShowBE(), 
                            'active' => in_array($this->getId(), array('product', 'user')) 
                                && (!preg_match('#(feedback|update/id/' . Yii::app()->user->id . ')#', Yii::app()->request->requestUri))),
                        array('label' => Yii::t('TOAST','Login'), 'url' => array('/site/login'), 'visible'=>Yii::app()->user->isGuest),
                        array('label' => Yii::t('TOAST','Logout'), 'url' => array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest),
                    ),
                    'lastItemCssClass' => 'last',
                ));
                ?>
            </div>
            <div class="notify hidden">
                NOTIFY BAR
            </div>
            <?php echo $content; ?>
        </div>
    </body>
</html>