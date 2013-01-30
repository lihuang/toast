<?php 
$this->htmlOptions['readonly'] = 'readonly';
//$this->htmlOptions['id'] = 'timer_txt_field';
echo CHtml::activeTextField($this->model,$this->attribute,$this->htmlOptions);

list($name, $id) = $this->resolveNameID();
$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
    'id' => 'dlg_' . $id,
    'theme' => 'base',
    'htmlOptions' => array('style' => 'display:none'),
    // additional javascript options for the dialog plugin
    'options' => array(
        'title' => Yii::t('Task', 'Scheme Time'),
        'resizable' => false,
        'autoOpen' => false,
        'modal' => true,
        'width' => 500,
        'buttons' => array(
            Yii::t('Task', 'NoCron') => 'js:function(){$("#' . $id . '").val(""); $(this).dialog("close");}',
            Yii::t('TOAST', 'Save') => 'js:function(){if($("#' . $id . '").setval()) $(this).dialog("close");}',
            Yii::t('TOAST', 'Cancel') => 'js:function(){$(this).dialog("close")}'
        ),
        'close' => 'js:function(event, ui) {
            $("#' . $id . '").focus();
        }'
    ),
));
?>
<style>
    .row-fluid .btn {
        margin-right: 0;
    }
    .custom.well,
    .cron-tip.well {
        margin-bottom: 5px;
        word-break:break-all;
        color: #333;
    }
</style>
<div class="row-fluid">
    <input class="btn span3" role="every_day" type="button" value="<?php echo Yii::t('Task', 'Every Day')?>" />
    <input class="btn span3" role="every_week" type="button" value="<?php echo Yii::t('Task', 'Every Week')?>" />
    <input class="btn span3" role="every_month" type="button" value="<?php echo Yii::t('Task', 'Every Month')?>" />
    <input class="btn span3" role="every_hour" type="button" value="<?php echo Yii::t('Task', 'Every Hour')?>" />
</div>
<div class="row-fluid">
    <div class="custom well well-small basic-info">
        <div class="row-fluid">
            <label class="offset1 span1"><?php echo Yii::t('Task', 'Minute')?></label>
            <input class="span9 focus" role="input_minute" />
        </div>
        <div class="row-fluid">
            <label class="offset1 span1"><?php echo Yii::t('Task', 'Hour')?></label>
            <input class="span9 focus" role="input_hour" />
        </div>
        <div class="row-fluid">
            <label class="offset1 span1"><?php echo Yii::t('Task', 'Day')?></label>
            <input class="span9 focus" role="input_day" />
        </div>
        <div class="row-fluid">
            <label class="offset1 span1"><?php echo Yii::t('Task', 'Month')?></label>
            <input class="span9 focus" role="input_month" />
        </div>
        <div class="row-fluid">
            <label class="offset1 span1"><?php echo Yii::t('Task', 'Week')?></label>
            <input class="span9 focus" role="input_week" />
        </div>
    </div>
</div>
<div class="cron-tip well well-small">TIP</div>
<?php
$this->endWidget('zii.widgets.jui.CJuiDialog');
?>