<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/command.css?ver=3" />
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/codemirror.css?ver=2" />
<style type="text/css">
    .CodeMirror {
        height: auto;
    }
    .CodeMirror-scroll,
    .CodeMirror-gutters {
        min-height: 5em;
    }
</style>
<div class="content">
    <div class="sub-nav">
        <?php
        $this->widget('zii.widgets.CMenu',
                array(
            'id' => 'path-nav',
            'items' => $vCommand->getNavItems($vCommandRun),
            'firstItemCssClass' => 'first',
            'lastItemCssClass' => 'last'
        ));
        ?>            
    </div>
    <div class="main-detail">
        <div class="button-actions clearfix">
            <?php
            echo $vCommand->getBtnList($vCommandRun);
            ?>
        </div>
        <div class="detail block clearfix">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vCommand, 'name', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span9'), CHtml::encode($vCommand->name));
                echo CHtml::activeHiddenField($vCommand, 'id');
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vCommand, 'parser_id', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span9'), CHtml::encode($vCommand->getParsers(FALSE))); 
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vCommand, 'command', array('class' => 'span1'));
                echo '<div class="span9">';
                echo CHtml::activeTextArea($vCommand, 'command');
                echo '</div>'
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vCommand, 'desc_info', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span9'), CHtml::encode($vCommand->desc_info));
                ?>
            </div>
            <?php
            if($vCommandRun)
            {
                echo CHtml::activeHiddenField($vCommandRun, 'id');
                list($case_total_amount, $case_passed_amount, $case_failed_amount, $case_notrun_amount) 
                        = $vCommandRun->getCaseAmount(); 
            ?>
            <div class="run-summary">
                <table>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Status');?></th>
                        <td class="run-status summary-status">
                            <?php 
                            if($vCommandRun->hasCompleted())
                            {
                                echo CHtml::hiddenField('task-running-flag', '1');
                            }
                            echo $vCommandRun->getStatusText(); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Total Amount');?></th>
                        <td id="view-allrun-case" class="total" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => ''));?>">
                            <?php echo $case_total_amount;?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Passed Amount');?></th>
                        <td id="view-allrun-passed-case" class="passed" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Passed'));?>">
                            <?php echo $case_passed_amount;?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Failed Amount');?></th>
                        <td id="view-allrun-failed-case" class="failed" style="cursor: pointer;" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Failed'));?>">
                            <?php echo $case_failed_amount;?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo Yii::t('Run', 'Case Not Execute Amonut');?></th>
                        <td id="view-allrun-null-case" class="null" style="cursor: pointer;" title="<?php echo Yii::t('Run', 'Click For {result} Cases', array('{result}' => 'Skipped&Blocked'));?>">
                            <?php echo $case_notrun_amount?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: right; padding: 10px 0px 0px 0px">
                            <?php
                            if($vCommandRun->hasCompleted())
                            {
                                if($vCommand->getParsers())
                                {
                                    echo CHtml::link(Yii::t('Run', 'Case Detail'), 'javascript:;', array('class' => 'case-detail'));
                                }
                                echo CHtml::link(Yii::t('Run', 'Download Output'), Yii::app()->baseUrl . '/run/getoutput/id/' . $vCommandRun->id);
                            }
                            echo CHtml::link(Yii::t('Run', 'View Output'), 'javascript:;', array('class' => 'view-output'));
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php } ?>
        </div>
        <div class="update-history follow detail-info" style="display: none;">
            <div class="detail-title"><?php echo Yii::t('TOAST', 'Update History') ?></div>
            <div class="history-content">
                <div style="text-align: center;">
                    <? echo CHtml::image(Yii::app()->theme->baseUrl . '/assets/images/loading.gif'); ?>
                </div>
            </div>
        </div>
        <div class="related-tasks follow detail-info" style="display: none;">
            <div class="detail-title"><?php echo Yii::t('Command', 'Related Tasks'); ?></div>
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
                        'name' => 'responsible_realname',
                    ),
                    array(
                        'name' => 'updated_by_realname',
                        'value' => '$data->updated_by_realname . " " . $data->update_time',
                        'headerHtmlOptions' => array('style' => 'width: 180px'),
                         'htmlOptions' => array('style' => 'width: 180px'),
                    ),
                ),
            ));
            ?>   
        </div>
         <?php
        if($vCommandRun)
        {
                echo '<div class="detail-info follow">';
                echo '<div class="detail-title">' . Yii::t('Command', 'Command Runs') . '</div>';
                $this->widget('GridView',
                        array(
                    'id' => 'vruns',
                    'dataProvider' => $vRunProvider,
                    'ajaxUpdate' => false,
                    'htmlOptions' => array('class' => 'widget-view'),
                    'rowCssClassExpression' => '$data->getStatusStyle()',
                    'columns' => array(
                        array(
                            'name' => 'id',
                            'headerHtmlOptions' => array('class' => 'id'),
                            'htmlOptions' => array('class' => 'id'),
                        ),
                        'name' => 'created_by_realname',
                        array(
                            'name' => 'machine_name',
                            'headerHtmlOptions' => array('class' => 'name'),
                            'htmlOptions' => array('class' => 'name'),
                        ),
                        'sudoer',
                        array(
                            'name' => 'status',
                            'value' => '$data->getStatusText()',
                            'headerHtmlOptions' => array('style' => 'width: 100px'),
                            'htmlOptions' => array('style' => 'width: 100px'),
                        ),
                        array(
                            'name' => 'result',
                            'value' => '$data->getResultText()',
                            'headerHtmlOptions' => array('style' => 'width: 100px'),
                            'htmlOptions' => array('style' => 'width: 100px'),
                            'cssClassExpression' => '$data->getResultStyle()',
                        ),
                        array(
                            'name' => 'start_time',
                            'headerHtmlOptions' => array('style' => 'width: 130px'),
                            'htmlOptions' => array('style' => 'width: 130px'),
                        ),
                        array(
                            'name' => 'stop_time',
                            'headerHtmlOptions' => array('style' => 'width: 130px'),
                            'htmlOptions' => array('style' => 'width: 130px'),
                        ),
                    ),
                ));
                echo '</div>';
        }
        ?>
    </div>
</div>

<?php $this->beginWidget('zii.widgets.jui.CJuiDialog', array(
        'id' => 'dlg-run-command',
        'theme' => 'base',
        'htmlOptions' => array('style' => 'display:none'),
         // additional javascript options for the dialog plugin
        'options' => array(
            'title' => Yii::t('Command', 'Run Command'),
            'autoOpen' => false,
            'resizable' => false,
            'width' => 450,
            'modal' => true,
            'close' => 'js:function(){
                $("#command-run-error").hide();
            }',
        ),
    ));
    echo CHtml::beginForm('#', 'post', array('id' => 'comman-run-form'));
?>
<div id="command-run-error" class="errorSummary" style="margin-bottom: 10px; display: none"></div>
<div class="detail">
    <div class="row-fluid">
        <?php
        echo CHtml::activeLabel($lastRunInfo, 'machine_id', array('class' => 'span2'));
        echo CHtml::activeHiddenField($lastRunInfo, 'machine_name');
//        echo CHtml::activeTextField($lastRunInfo, 'machine_id', array('class' => 'span9'));
//        $this->widget('application.extensions.select2.ESelect2', array(
//            'selector'=>'#VCommandRun_machine_id',
//            'options' => array(
//                'minimumInputLength' => 1,
//                'ajax' => array(
//                    'url' => Yii::app()->getBaseUrl() . '/machine/lookup',
//                    'dataType' => 'json',
//                    'data' => 'js:function (term, page) {
//                        return {
//                            term: term,
//                        }
//                    }',
//                    'results' => 'js:function (data, page) {
//                        return {
//                            results: data.machines,
//                        };
//                    }'
//                ),
//                'formatResult' => 'js:function(data){
//                    var result = "";
//                    if(data.responsible)
//                        result += "[" + data.responsible + "] ";
//                    result += (data.hostname || data.name);
//                    if(data.ip)
//                        result += "<br />" + data.ip;
//                    if(data.id && data.style)
//                        result = "<div class=\"" + data.style + "\">" + result + "</div>";
//                    return result;
//                }',
//                'formatSelection' => 'js:function(data){
//                    return (data.hostname || data.name);
//                }',
//                'initSelection' => 'js:function (element, callback) {
//                    var id = $("#VCommandRun_machine_id").val();
//                    var name = $("#VCommandRun_machine_name").val();
//                    callback({id: id, hostname: name});
//                }',
//            )
//        ));
        $this->widget('application.extensions.combobox.InputComboBox', array(
            'model' => $lastRunInfo,
            'attribute' => 'machine_id',
            // data to populate the select. Must be an array.
            'data' => Machine::model()->getMachineOptions(),
            // options passed to plugin
            'options' => array(
                'onSelect' => '',
                'allowText' => false,
                'showStyle' => true,
            ),
            // Options passed to the text input
            'htmlOptions' => array(
                'class' => 'focus span9', 
                'placeholder' => Yii::t('Command', 'Machine Placeholder')
             ),
        ));
        ?>
    </div>
    <div class="row-fluid">
        <?php
        echo CHtml::activeLabel($lastRunInfo, 'sudoer', array('class' => 'span2'));
        echo CHtml::activeTextField($lastRunInfo, 'sudoer', array('class' => 'focus span9'));
        ?>
    </div>
    <div class="row-fluid">
        <?php
        echo CHtml::activeLabel($lastRunInfo, 'timeout', array('class' => 'span2'));
        echo CHtml::activeTextField($lastRunInfo, 'timeout', array('class' => 'focus span8'));
        echo CHtml::tag('div', array('class' => 'span2'), Yii::t('Run', 'Minutes'));
        ?>
    </div>
</div>
<?php 
    echo CHtml::endForm();
    $this->endWidget('zii.widgets.jui.CJuiDialog'); 
?>

<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/codemirror.js?ver=1"></script>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/shell.js?ver=1"></script>
<script type="text/javascript">
$(document).ready(function(){
    var codeMirror = CodeMirror.fromTextArea($("#VCommand_command").get(0), {
        mode: "shell",
        lineNumbers: true,
        readOnly: true
    })
    
    $("div#vruns table.items tbody tr").has($("td[title='" + $("#VCommandRun_id").val() + "']")).addClass("selected")
    
    $("input.update-command").click(function(){
        var commandId = $("#VCommand_id").val()
        location.href = getRootPath() + "/command/update/id/" + commandId
    })
    
    $("input.delete-command").click(function(){
        if(confirm(lang.confrimDeleteCommand)){
            var commandId = $("#VCommand_id").val()
            var data = {"id" : commandId}
            $.getJSON(toast.deleteCommand, data, function(json){
                if(json.flag) {
                    location.href = getRootPath() + "/command"
                } else {
                    alert(lang.deleteFailed)
                }
            })
        }
    })
    
    $("input.update-history").click(function(){
        $("input.update-history").toggleClass("active");
        $("div.update-history").slideToggle("fast");
        if($("div.update-history div.list-view").size() == 0)
        {
            $("div.history-content").load(getRootPath() + "/command/getHistory/id/" + $("#VCommand_id").val());
        }
    })
    
    $("input.cancel-run").click(function(){
        $(this).attr("disabled", "disabled");
        $(this).val(lang.Cancelling);
        var data = {id : $("#VCommandRun_id").val()};
        $.getJSON(toast.cancelCommandRun, data, function(json){
            $("tr:contains(" + json.id + ")").children("td.run-status").text(json.status);
            $("td.summary-status").text(json.status);
            location.reload();
        });
    })
    
    $("input.run-command").click(function(){
        $("input#VCommandRun_machine_id_input").val($("#VCommandRun_machine_id option[selected='selected']").text());
        $("#dlg-run-command")
        .dialog("option", "buttons",[
            {
                text: lang.Run,
                click: function() {
                    var commandId = $("#VCommand_id").val();
                    $.getJSON(toast.runCommand + '/id/' + commandId, 
                        $("#comman-run-form").serialize(), function (json) {
                            if(json.validate)
                            {
                                $("#dlg-run-command").dialog("close");
                                location.href = getRootPath() + "/command/view/id/" + commandId;
                            }
                            else
                            {
                                var html = '';
                                for(var item in json.errors)
                                {
                                    html += '<p>' + json.errors[item] + '</p>';
                                }
                                $("#command-run-error").html(html).show();
                            }
                    })
                }
            },
            {
                text: lang.Cancel,
                click: function() {
                    $(this).dialog("close");
                }
            }
        ])
        .dialog("open");
    })
    
    $("input.show-task").click(function(){
        $("input.show-task").toggleClass("active");
        $("div.related-tasks").slideToggle("fast");
    })
    
    $("div#vruns table.items tbody tr").click(function(){
        var runId = $(this).children("td:first").text();
        if (-1 == location.href.search(/runID\/[0-9]*/i))
        {
            var href = location.href;
            href += '/runID/' + runId;
            href = href.replace('//runID', '/runID');
            location.href = href;
        }
        else
        {
            location.href = location.href.replace(/runID\/[0-9]*/i, 'runID/' + runId);
        }
    });
    
    $("a.view-output").click(function(){
        window.open(toast.openRunOutput + "/id/" + $("#VCommandRun_id").val(),
        "", "width=900, height=500, top=100, left=100, resizable=yes, scrollbars=1")
    })
    
    $("a.case-detail").click(function(){
        var url = getRootPath() + "/run/case/commandrun/" + $("#VCommandRun_id").val()
        window.open(url, '', "width=1100, height=600, top=100, left=100, resizable=yes, scrollbars=1")
    })
    
    $(".run-summary td.total").click(function(){
        var url = getRootPath() + "/run/case/commandrun/" + $("#VCommandRun_id").val()
        window.open(url, '', "width=1100, height=600, top=100, left=100, resizable=yes, scrollbars=1")
    })
    $(".run-summary td.passed").click(function(){
        var url = getRootPath() + "/run/case/commandrun/" + $("#VCommandRun_id").val() + '/filter/<?php echo CaseResult::RESULT_PASSED?>'
        window.open(url, '', "width=1100, height=600, top=100, left=100, resizable=yes, scrollbars=1")
    })
    $(".run-summary td.failed").click(function(){
        var url = getRootPath() + "/run/case/commandrun/" + $("#VCommandRun_id").val() + '/filter/<?php echo CaseResult::RESULT_FAILED?>'
        window.open(url, '', "width=1100, height=600, top=100, left=100, resizable=yes, scrollbars=1")
    })
    $(".run-summary td.null").click(function(){
        var url = getRootPath() + "/run/case/commandrun/" 
            + $("#VCommandRun_id").val()
            + '/filter/<?php echo CaseResult::RESULT_BLOCKED . '|' . CaseResult::RESULT_SKIPPED; ?>'
        window.open(url, '', "width=1100, height=600, top=100, left=100, resizable=yes, scrollbars=1")
    })
    
    syncMachineStatus($("#VCommandRun_machine_id"))
    
    <?php if (!empty($vCommandRun) && !$vCommandRun->hasCompleted()): ?>
    var commandRunID = $("#VCommandRun_id").val();
    var getstatus = setInterval(function() {
        $.getJSON(getRootPath() + "/run/getCommandRunStatus/id/" + commandRunID, function(json){
            if(json.hascompleted) {
                clearInterval(getstatus);
                location.reload();
            }
        });
        }, toast.heartbeat);
    <?php endif ?>
})
</script>