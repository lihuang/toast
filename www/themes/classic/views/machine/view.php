<div class="content">
<!--    <div class="tree">
        <div class="sub-nav">
            <div class="product-select">
                <?php echo CHtml::dropDownList('products', $machine->product_id, Yii::app()->user->getProductOpts(), array('id' => 'products')); ?>
            </div>
        </div>
        <div id="machine-list" style="padding: 5px 0px 0px 20px">
        </div>
    </div>
    <div class="layout-right">-->
        <div class="sub-nav">
        <?php
        $items = array(array('label' => $machine->name));
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'path-nav',
            'items' => $items,
            'firstItemCssClass' => 'first',
            'lastItemCssClass' => 'last'
        ));
        ?>
        </div>
        <div class="main-detail">
            <div class="button-actions clearfix">
                <input type="button" value="<?php echo Yii::t("Machine", "Update Machine"); ?>" class="btn update-machine" />
                <input type="button" value="<?php echo Yii::t("Machine", "Delete Machine"); ?>" class="btn delete-machine" />
                <input type="button" value="<?php echo Yii::t("Machine", "Upgrade Agent"); ?>" class="btn upgrade-agent" />
                <input type="button" value="<?php echo Yii::t("Machine", "Tasks On This Machine"); ?>" class="btn right show-task" style="float: right;"/>
            </div>
            <div class="detail block clearfix">
                <table class="detail-table" style="width: 100%">
                    <tr>
                        <th><?php echo CHtml::activeLabel($machine, 'name'); ?></th>
                        <td colspan="3" id="machine-name" style="width: 280px;" class="<?php echo $machine->getStatusStyle(); ?>"><?php echo $machine->name; echo CHtml::activeHiddenField($machine, 'id'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo CHtml::activeLabel($machine, 'type'); ?></th>
                        <td><?php echo $machine->getTypeText(); ?></td>
                        <th><?php echo CHtml::activeLabel($machine, 'product_id'); ?></th>
                        <td><?php echo $machine->product_name; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo CHtml::activeLabel($machine, 'responsible'); ?></th>
                        <td><?php echo $machine->responsible_realname; ?></td>
                        <th><?php echo CHtml::activeLabel($machine, 'notify'); ?></th>
                        <td><?php echo $machine->getNotifyText(); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo CHtml::activeLabel($machine, 'agent_version'); ?></th>
                        <td><?php echo $machine->agent_version; ?></td>
                        <th><?php echo CHtml::activeLabel($machine, 'update_time'); ?></th>
                        <td><?php echo $machine->update_time; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo CHtml::activeLabel($machine, 'desc_info'); ?></th>
                        <td colspan="3">
                            <?php echo CHtml::activeTextArea($machine, 'desc_info', array('class' => 'info-area area-field', 'disabled' => 'disabled', 'rows' => '2')); ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="task-on-machine" style="display: none;">
                <div class="block-title"><?php echo Yii::t("Machine", "Tasks On This Machine");?></div>
                <?php
                $this->widget('GridView', array(
                    'id' => 'vtasks',
                    'dataProvider' => $vTaskProvider,
                    'htmlOptions' => array('class' => 'widget-view'),
                    'selectionChanged' => 'js:function(id){
                            var selectedID = $.fn.yiiGridView.getSelection(id);
                            if(selectedID.toString().match(/\d+/))
                                location.href = getRootPath() + "/task/view/id/" + selectedID;
                        }',
                    'columns' => array(
                        array(
                            'name' => 'id',
                            'headerHtmlOptions' => array('class' => 'id'),
                            'htmlOptions' => array('class' => 'id'),
                        ),
                        array(
                            'name' => 'name',
                            'headerHtmlOptions' => array('class' => 'name'),
                            'htmlOptions' => array('class' => 'name'),
                        ),
                        array(
                            'name' => 'type',
                            'value' => '$data->getTypeText()',
                        ),
                        array(
                            'name' => 'product_name',
                        ),
                        array(
                            'name' => 'project_name',
                        ),
                        array(
                            'name' => 'responsible_realname',
                        ),
                        array(
                            'name' => 'updated_by_realname',
                            'value' => '$data->updated_by_realname . " " . $data->update_time',
                        ),
                    ),
                ));
                ?>   
            </div>
            <div class="follow">
                <div class="block-title"><?php echo Yii::t("Machine", "Monitor Graph");?></div>
                <table>
                    <tbody>
                        <tr>
                            <td colspan="2" style="padding: 5px 15px; text-align: left">
                                <div style="padding-bottom: 10px">
                                    <?php 
                                    $this->beginWidget('CActiveForm', array(
                                        'action' => Yii::app()->createUrl($this->route),
                                        'method' => 'get'
                                    ));
                                    echo CHtml::hiddenField('id', $machine->id);
                                    ?>
                                    <label><?php echo Yii::t('Machine', "Group");?></label>
                                    <?php 
                                    echo CHtml::checkBoxList('groups[]', $groups, $machine->getOpts(),  array('separator' => '&nbsp;'));
                                    ?>
                                </div>
                                <div>
                                    <label><?php echo Yii::t('Machine', "Time");?></label>
                                    <?php
                                    $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                                        'name' => 'start',
                                        'options' => array(
                                            'showAnim' => 'fold',
                                            'dateFormat' => 'yy-mm-dd 00:00:00',
                                            'showTime' => true,
                                        ),
                                        'htmlOptions' => array(
                                            'class' => 'focus'
                                        ),
                                        'value' => $start,
                                        'language' => Yii::app()->language
                                    ));
                                    echo Yii::t('Machine', 'To'); 
                                    $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                                        'name' => 'end',
                                        'options' => array(
                                            'showAnim' => 'fold',
                                            'dateFormat' => 'yy-mm-dd 00:00:00',
                                            'showTime' => true,
                                        ),
                                        'htmlOptions' => array(
                                            'class' => 'focus'
                                        ),
                                        'value' => $end,
                                        'language' => Yii::app()->language
                                    ));
                                    echo CHtml::submitButton(Yii::t('TOAST', 'Make Ture'), array('class' => 'btn'));
                                    $this->endWidget();
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <?php foreach($groups as $group) { ?>
                        <tr class="graph">
                            <td style="width: 800px; text-align: left;">
<!--                                <img onerror="this.src='<?php echo Yii::app()->theme->baseUrl . '/assets/images/error.gif'; ?>'"
                                    src="<?php echo Yii::app()->request->getBaseUrl(true) 
                                        . '/machine/rrd/id/' . $machine->id  . '/MachineMonitor[group]/' . $group 
                                        . '/MachineMonitor[start]/' . strtotime($start) . '/MachineMonitor[end]/' . strtotime($end); ?>" />-->
                                <img src="<?php echo Yii::app()->request->getBaseUrl(true) 
                                        . '/machine/rrd/id/' . $machine->id  . '/MachineMonitor[group]/' . $group 
                                        . '/MachineMonitor[start]/' . strtotime($start) . '/MachineMonitor[end]/' . strtotime($end); ?>" />
                            </td>
                            <td style="vertical-align: top; text-align: left">
                                <?php
                                $moniterClass = MachineMonitor::getDetailClass($group);
                                $gmetrics = $moniterClass::model()->gmetrics;
                                foreach($gmetrics as $metric)
                                {
                                    echo CHtml::checkBox($metric, true, array('class' => 'gmetrics', 'id' => $group . '_' . $metric, 'style' => 'vertical-align: middle;'));
                                    echo '&nbsp;';
                                    echo CHtml::label($metric, $group . '_' . $metric, array('style' => 'font-weight: bold; color: #' . $moniterClass::model()->colors[$metric]));
                                    echo '<br/>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
<!--    </div>-->
</div>
<?php $this->beginWidget('zii.widgets.jui.CJuiDialog', array(
        'id' => 'dlg-upgrade-agent',
        'theme' => 'base',
        'htmlOptions' => array('style' => 'display:none'),
         // additional javascript options for the dialog plugin
        'options' => array(
            'title' => Yii::t('Machine', 'Upgrade Agent'),
            'autoOpen' => false,
            'modal' => true,
            'resizable' => false,
            'buttons' => ($machine->type == Machine::TYPE_LINUX)?
                    array(Yii::t('Machine', 'Confirm Upgrade') => 'js:function(){
                        $(this).dialog("close");
                        var machineId = $("input#VMachine_id").val();
                        var data = {"id" : machineId};
                        $.getJSON(getRootPath() + "/machine/updateAgent", data, function(json){
                            if(json.flag) {
                                alert(lang.updateAgentSuccess);
                            } else {
                                alert(lang.updateAgentFailed);
                            }
                        })}',
                        Yii::t('TOAST', 'Cancel') => 'js:function(){ $(this).dialog("close");}'):
                    array(Yii::t('TOAST', 'OK') => 'js:function(){ $(this).dialog("close");}'),
            'width' => 400,
        ),
    ));
?>
<div style="margin: 10px 10px">
<?php if($machine->type == Machine::TYPE_LINUX) {?>
    <p><?php echo Yii::t('Machine', 'Upgrade Linux Agent'); ?></p>
    <pre class="command-line">sudo yum install t-test-toast -b test -y</pre>
<?php } else {?>
    <p><?php echo Yii::t('Machine', 'Upgrade Windows Agent {download link}', array('{download link}' => Yii::app()->createUrl(Yii::app()->params['winAgentLink']))); ?></p>
<?php } ?>
</div>
<?php $this->endWidget('zii.widgets.jui.CJuiDialog');?>

<script type="text/javascript">
$(document).ready(function(){
    $("tr.graph input.gmetrics").click(function(){
        if(!($("input:checked", $(this).parent()).size())) {
           alert(lang.selectAtLeastOne);
           return false;
        }
        var split = "/MachineMonitor[gmetrics][]/";
        var metric = split + $("input:checked",$(this).parent()).map(function(){
            return $(this).attr('name');
        }).get().join(split);
        var img = $("img",$(this).parent().parent()).attr("src");
        var index =  img.indexOf(split);
        if(index != -1) {
            img = img.substring(0, index);
        }
        $("img",$(this).parent().parent()).attr("src", img + metric);
    });
    $("input.delete-machine").click(function(){
        if(confirm(lang.confrimDeleteMachine)){
            var machineId = $("input#VMachine_id").val();
            var data = {"id" : machineId};
            $.getJSON(toast.deleteMachine, data, function(json){
                if(json.flag) {
//                    alert(lang.deleteMachineSuccess);
                    location.href = toast.machineList;
                } else {
                    alert(lang.machineHasTask);
                }
            })
        }
    })
    $("input.update-machine").click(function(){
        var machineId = $("input#VMachine_id").val();
        location.href = getRootPath() + "/machine/update/id/" + machineId;
    })
    $("input.upgrade-agent").click(function(){
        $("#dlg-upgrade-agent").dialog("open");
    });
    $("input.show-task").click(function(){
        $("input.show-task").toggleClass("active");
        $("div.task-on-machine").slideToggle("fast");
    })
//    $("div#vtasks table.items tbody tr").click(function(){
//        var taskId = $(this).children("td:first").text();
//        location.href = getRootPath() + "/task/view/id/" + taskId;
//    });
});
</script>