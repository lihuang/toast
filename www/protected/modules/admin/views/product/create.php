<div class="content">
    <div class="sub-nav">
        <?php
        $items = array(array('label' => Yii::t('TOAST', 'Product Label'), 'url' => array('/admin/product')),
            array('label' => Yii::t('Product', 'New Product')));
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'path-nav',
            'items' => $items,
            'firstItemCssClass' => 'first',
            'lastItemCssClass' => 'last'
            ));
        ?>
    </div>
    <div class="main-detail">
        <?php echo CHtml::beginForm(); ?>
        <div class="button-actions clearfix">
            <input type="submit" value="<?php echo Yii::t('TOAST', 'Save'); ?>" name="save" class="btn" />
            <input type="reset" value="<?php echo Yii::t('TOAST', 'Reset'); ?>" class="btn" />
        </div>
        <?php echo CHtml::errorSummary($product); ?>
        <div class="detail block basic-info clearfix">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'name', array('class' => 'span2'));
                echo CHtml::activeTextField($product, 'name', array('class' => 'span10 focus'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'regress_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'regress_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'unit_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'unit_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'system_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'system_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'bvt_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'bvt_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'comparison_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'comparison_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'regress_fail_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'regress_fail_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'unit_fail_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'unit_fail_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'system_fail_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'system_fail_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'bvt_fail_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'bvt_fail_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'comparison_fail_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'comparison_fail_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
        </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function(){
    var goodexit = false;
    function bindunbeforunload() {
        goodexit = false;
        window.onbeforeunload = perforresult;
    }
    function unbindunbeforunload() {
        goodexit = true;
        window.onbeforeunload = null;
    }
    function perforresult() {
        if(!goodexit) {
            return lang.sureToLeave;
        }
    }
    bindunbeforunload();
    $("input[type=submit]").click(function(){
        unbindunbeforunload();
    })
    inputFocus();
    
    $("input#create-product").click(function(){
        location.href = getRootPath() + "/admin/product/create";
    })
});
</script>