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
        <div class="signup-form container-fluid form-horizontal">
            <?php echo CHtml::beginForm(); ?>
            <div class="row-fluid">
                <img src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/images/logo_signup.png" alt="Toast"/>
                <span class="signup-label pull-right">
                    <?php
                    echo Yii::t('Apply', 'Apply');
                    ?>
                </span>
                <hr />
            </div>
            <div class="well well-smallest">
                <strong>
                <?php
                echo Yii::t('Apply', 'Product list');
                ?>
                </strong>
            </div>
            <div class="row-fluid row-product-list">
                <?php
                foreach($productOpts as $key => $val)
                {
                    $htmlOptions = array('id' => 'products_' . $key, 'value' => $key);
                    $checked = false;
                    if(in_array($key, $accessProductIds))
                    {
                        $htmlOptions += array('disabled' => 'disbaled');
                        $checked = true;
                    }
                    else if($key == $productId)
                    {
                        $checked = true;
                    }
                    echo CHtml::tag('div', array('class' => 'checkbox inline span4'),
                            CHtml::checkBox('products[]', $checked, $htmlOptions)
                            . CHtml::label($val, 'products_' . $key), true);
                }
                ?>
            </div>
            <div class="row-fluid">
               <?php
                echo CHtml::submitButton(Yii::t('Apply', 'Apply'),  array('class' => 'btn btn-primary'));
                echo CHtml::link(Yii::t('Apply', 'Logout'), array('site/logout'), array('class' => 'pull-right'));
                ?>
            </div>
            <?php echo CHtml::endForm(); ?>
        </div>
    </body>
</html>