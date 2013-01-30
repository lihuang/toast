<div class="content">
    <div class="sub-nav">
    <?php
    if ($machine->isNewRecord)
        $items = array(array('label' => Yii::t('Machine', 'New Machine')));
    else
        $items = array(array('label' => $machine->name, 'url' => array('/machine/view/id/' . $machine->id)),
            array('label' => Yii::t('TOAST', 'Modify')));
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
            <input type="submit" value="<?php echo Yii::t('TOAST', 'Save'); ?>" class="btn" />
            <input type="button" value="<?php echo Yii::t('TOAST', 'Return'); ?>" class="btn return"/>
        </div>
        <?php echo CHtml::errorSummary($machine); ?>
        <div class="detail block clearfix">
            <table class="detail-table detail-form">
                <tr>
                    <th><?php echo CHtml::activeLabel($machine, 'name'); ?></th>
                    <td colspan="3"><?php echo CHtml::activeTextField($machine, 'name', array('class' => 'focus', 'disabled' => 'disabled')); ?></td>
                </tr>
                <tr>
                    <th><?php echo CHtml::activeLabel($machine, 'type'); ?></th>
                    <td><?php echo CHtml::activeDropDownList($machine, 'type', $machine->getTypeOptions(), array('disabled' => 'disabled')); ?></td>
                    <th><?php echo CHtml::activeLabelEx($machine, 'product_id'); ?></th>
                    <td><?php echo CHtml::activeDropDownList($machine, 'product_id', Yii::app()->user->getProductOpts()); ?></td>
                </tr>
                <tr>
                    <th><?php echo CHtml::activeLabelEx($machine, 'responsible'); ?></th>
                    <td><?php 
                        $this->widget('application.extensions.combobox.InputComboBox', array(
                            'model' => $machine,
                            'attribute' => 'responsible',
                            'data' => Yii::app()->user->getUserOpts(),
                            'options' => array(
                                'allowText' => false,
                                'showStyle' => false,
                            ),
                            'htmlOptions' => array('class' => 'focus', 'style' => 'width: 180px'),
                        ));  
                        ?>
                    </td>
                    <th><?php echo CHtml::activeLabelEx($machine, 'notify'); ?></th>
                    <td><?php echo CHtml::activeRadioButtonList($machine, 'notify', $machine->getNotifyOptions(), array('style' => 'width:auto', 'separator'=>'&nbsp;&nbsp;'));?></td>
                </tr>
                <tr>
                    <th><?php echo CHtml::activeLabel($machine, 'desc_info'); ?></th>
                    <td colspan="3"><?php echo CHtml::activeTextArea($machine, 'desc_info', array('class' => 'focus info-area', 'rows' => '5')); ?></td>
                </tr>
            </table>
        </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        var goodexit = false;
        window.onbeforeunload = function () {
            if(!goodexit) {
                return lang.sureToLeave;
            }
        }
        $("input[type=submit]").click(function(){
            goodexit = true;
        })
        
        $(".btn.return").click(function(){
            var href = getRootPath() + '/machine';
            <?php 
            if($machine->id !== NULL)
                echo "href += '/view/id/$machine->id';";
            ?>
            location.href = href;
        })
        
        $('form').bind('reset', function(){return confirm(lang.confrimResetForm)});
    });
</script>