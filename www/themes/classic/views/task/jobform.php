<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/job.form.js?ver=1"></script>
<?php 
$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
    'id' => 'dlg-add-job',
    'htmlOptions' => array('style' => 'display:none; overflow-x:hidden; font-size: 13px;'),
     // additional javascript options for the dialog plugin
    'options' => array(
        'autoOpen' => false,
        'resizable' => false,
        'modal' => true,
        'width' => 700,
        'close' => 'js:function(){
            jobForm.clear()
        }'
    ),
));

echo CHtml::beginForm('#', 'post', array('enctype' => 'multipart/form-data', 'id' => 'job-form'));
$command = new Command();
$job = new Job();

// job form
// error field
echo CHtml::tag('div', array(
    'id' => 'job-error',
    'class' => 'errorSummary', 
    'style' => 'margin-bottom: 10px; display: none'
), '');
?>
<ul id="run-tabs">
    <li id="command-tab" class="selected"><?php echo Yii::t('Job', 'Command Mode'); ?></li><li id="test-case-tab"><?php echo Yii::t('Job', 'Test Case Mode'); ?></li>
</ul>
<div id="run-fields">
    <!-- command fields start -->
    <div id="command-fields">
        <div class="row-fluid">
            <?php 
            echo CHtml::activeLabel($command, 'name', array('class' => 'span2'));
            $listData = array('' => '', -1 => '[' . Yii::t('Command', 'New Command') . ']');
            $listData += Command::model()->getCommandOptions();
            $this->widget('application.extensions.combobox.InputComboBox', array(
                'model' => $job,
                'attribute' => 'command_id',
                // data to populate the select. Must be an array.
                'data' => $listData,
                // options passed to plugin
                'options' => array(
                    // JS code to execute on 'select' event, the selected item is
                    // available through the 'item' variable.
                    'onSelect' => '
                        if(-1 == $("#Job_command_id").val()) {
                            item.value = ""
                        }
                        jobForm.setCommand(item.value, $("#Job_command_id").val())
                    ',
                    // If false, field value must be present in the select.
                    // Defaults to true.
                    'allowText' => true,
                    'showStyle' => false,
                    'disableAutocomplete' => true,
                ),
                // Options passed to the text input
                'htmlOptions' => array(
                    'class' => 'focus span10',
                    'placeholder' => Yii::t('Job', 'Name Tip')
                ),
            ));

            // set hidden data.
            echo CHtml::activeHiddenField($job, 'id');
            echo CHtml::activeHiddenField($job, 'stage_num');
            echo CHtml::activeHiddenField($command, 'name');
            echo CHtml::activeHiddenField($command, 'mode');
            echo CHtml::hiddenField('Job[type]', $job->type, array("id" => "Job_type"));
            ?>
        </div>
        <div class="row-fluid" style="margin-bottom: 0">
            <?php echo CHtml::activeLabel($command, 'command', array('class' => 'span2')); ?>
            <div class="span10">
                <ul id="command-tabs">
                    <li id="basic-command-tab" class="selected"><?php echo Yii::t('Job', 'Basic Command View'); ?></li>
                    <li id="ut-command-tab"><?php echo Yii::t('Job', 'UT Command View'); ?></li>
                    <li id="ci-command-tab"><?php echo Yii::t('Job', 'CI Command View'); ?></li>
                </ul>
                <!-- basic command fields start -->
                <div id="basic-command-fields" class="field-with-border">
                    <?php echo CHtml::tag('label', array(), Yii::t('Job', 'Basic Command')); ?>
                    <div class="row-fluid">
                        <?php
                        echo CHtml::activeTextArea($command, 'command', array(
                            'class' => 'focus span12',
                            'rows' => '6',
                            'placeholder' => Yii::t('Job', 'Basic Command Tip')
                        ));
                        ?>
                    </div>
                    <hr/>
                    <div class="row-fluid" style="margin-bottom: 0">
                        <?php
                        echo CHtml::activeLabel($command, 'parser_id', array('class' => 'span2'));
                        $this->widget('application.extensions.select2.ESelect2', array(
                            'model' => $command,
                            'attribute' => 'parser_id',
                            'data' => Parser::model()->getParserOptions(),
                            'htmlOptions' => array(
                                'multiple' => 'multiple',
                                'class' => 'span10',
                                'placeholder' => Yii::t('Parser', 'No need parse')
                            ),
                        ));
                        ?>
                    </div>
                </div>
                <!-- basic command fields end -->
                <!-- ut command fields start -->
                <div id="ut-command-fields" class="field-with-border">
                    <div class="row-fluid">
                        <?php
                        echo CHtml::label(Yii::t('Job', 'Code Path'), 'code-path', array('class' => 'span2'));
                        echo CHtml::textField('code-path', '', array(
                            'class' => 'focus span10',
                            'placeholder' => Yii::t('Job', 'Code Path Tip')
                        ));
                        ?>
                    </div>
                    <hr/>
                    <?php echo CHtml::label(Yii::t('Job', 'Unit Test Command'), 'ut-command'); ?>
                    <div class="row-fluid">
                        <?php
                        echo CHtml::textArea('ut-command', '', array(
                            'class' => 'focus span12',
                            'rows' => '6',
                            'placeholder' => Yii::t('Job', 'Unit Test Command Tip')
                        ));
                        ?>
                    </div>
                    <hr/>
                    <div class="row-fluid">
                        <?php
                        echo CHtml::label(Yii::t('Job', 'Make Tool'), 'make-tool', array('class' => 'span2'));
                        echo CHtml::dropDownList('make-tool', '', $job->getMakeToolOpt(), array('class' => 'span2'));
                        echo '<div class="span8">';
                        echo CHtml::checkBox('code-coverage', false, array('value' => '-y', 'style' => 'margin: 6px 0px 0px 10px; float: left;'));
                        echo CHtml::label(YIi::t('Job', 'Code Coverage'), 'code-coverage', array('style' => 'float: left'));
                        echo CHtml::checkBox('keep-workspace', false, array('value' => '-w', 'style' => 'margin: 6px 0px 0px 10px; float: left;'));
                        echo CHtml::label(YIi::t('Job', 'Keep Workspace'), 'keep-workspace', array('style' => 'float: left'));
                        echo CHtml::checkBox('debug', false, array('value' => '-d', 'style' => 'margin: 6px 0px 0px 10px; float: left;'));
                        echo CHtml::label(YIi::t('Job', 'Debug'), 'debug', array('style' => 'float: left'));
                        echo '</div>';
                        ?>
                    </div>
                    <hr/>
                    <div class="row-fluid" style="margin-bottom: 0">
                        <?php
                        echo CHtml::label(Yii::t('Job', 'Other Options'), 'other-opts', array('class' => 'span2'));
                        echo CHtml::textField('other-opts', '', array(
                            'class' => 'focus span10',
                            'placeholder' => Yii::t('Job', 'Other Options Tip'),
                        ));
                        ?>
                    </div>
                </div>
                <!-- ut command fields end -->
                <!-- ci command fields start -->
                <div id="ci-command-fields" class="field-with-border">
                    <div class="row-fluid">
                        <?php
                        echo CHtml::label(Yii::t('Job', 'CI Config'), 'ci-config', array('class' => 'span2'));
                        echo CHtml::link(Yii::t('Job', 'New Config'), 'javascript:void(0);', array('class' => 'span2 btn ci-config-new'));
                        echo CHtml::openTag('a', array('class' => 'span2 btn ci-config', 'href' => 'javascript:void(0);'));
                        echo CHtml::tag('span', array(), Yii::t('Job', 'Upload Config'), true);
                        echo CHtml::fileField('ci-config-input', '');
                        echo CHtml::closeTag('a');
                        echo CHtml::hiddenField('ci-config-newname');
                        echo CHtml::tag('span', array('id' => 'ci-config-filename', 'class' => 'span4 ci-config-filename', 'style' => 'line-height: 25px;'), '', true);
                        echo CHtml::link(Yii::t('Job', 'Edit Config'), 'javascript:void(0);', array('class' => 'span2 btn ci-config-edit', 'style' => 'display:none'));
                        ?>
                    </div>
                    <div class="row-fluid">
                        <?php
                        echo '<div class="span12">';
                        echo CHtml::label(Yii::t('Job', 'CI Stage'), 'ci-stage', array('class' => 'span2'));
                        echo CHtml::checkBox('ci-stage-unittest', false, array('value' => 'u', 'ext' => 'CI-Stage', 'style' => 'margin: 6px 2px 0px 10px; float: left;'));
                        echo CHtml::label(YIi::t('Job', 'CI Unittest'), 'ci-stage-unittest', array('style' => 'float: left'));
                        echo CHtml::checkBox('ci-stage-build', false, array('value' => 'b', 'ext' => 'CI-Stage', 'style' => 'margin: 6px 2px 0px 10px; float: left;'));
                        echo CHtml::label(YIi::t('Job', 'CI Build'), 'ci-stage-build', array('style' => 'float: left'));
                        echo CHtml::checkBox('ci-stage-deploy', false, array('value' => 'd', 'ext' => 'CI-Stage', 'style' => 'margin: 6px 2px 0px 10px; float: left;'));
                        echo CHtml::label(YIi::t('Job', 'CI Deploy'), 'ci-stage-deploy', array('style' => 'float: left'));
                        echo CHtml::checkBox('ci-stage-functest', false, array('value' => 'f', 'ext' => 'CI-Stage', 'style' => 'margin: 6px 2px 0px 10px; float: left;'));
                        echo CHtml::label(YIi::t('Job', 'CI Functest'), 'ci-stage-functest', array('style' => 'float: left'));
                        echo '</div>';
                        ?>
                    </div>
                    <div class="row-fluid" style="margin-bottom: 0">
                        <?php
                        echo CHtml::label(Yii::t('Job', 'Other Options'), 'ci-other-opts', array('class' => 'span2'));
                        echo CHtml::textField('ci-other-opts', '', array(
                            'class' => 'focus span10',
                            'placeholder' => Yii::t('Job', 'Other Options Tip'),
                        ));
                        ?>
                    </div>
                    <hr/>
                    <div class="row-fluid" style="margin-bottom: 0">
                        <?php
                        echo CHtml::label(Yii::t('Command', 'Test Tool'), 'ci-parser', array('class' => 'span2'));
                        $this->widget('application.extensions.select2.ESelect2', array(
                            'name' => 'ci-parser',
                            'data' => Parser::model()->getParserOptions(),
                            'htmlOptions' => array(
                                'multiple' => 'multiple',
                                'placeholder' => Yii::t('Parser', 'No need parse'),
                                'class' => 'span10',
                            ),
                        ));
                        ?>
                    </div>
                </div>
                <!-- ci command fields end -->
            </div>
        </div>
    </div>
    <!-- command fields end -->
    <!-- case fields start -->
    <div id="test-case-fields">
        <div class="row-fluid">
            <?php
            echo CHtml::label(Yii::t('TestCase', 'Query Case'), 'query-case', array('class' => 'span2'));
//            echo CHtml::textField('query-case', '', array('class' => 'span10 focus'));
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array_merge(VTestCase::model()->getQueryOpts(), array(
                    'keyClick' => 'js:function(queryStr){
                        $.getJSON(queryStr, {}, function(json){
                            if("success" == json.status){
                                $("#query-result").text("")
                                $.each(json.testcases, function(k, v){
                                    $("#query-result").append("<option value=\"" + k + "\" title=\"" + v.url  + "\">" + v.name + "</option>")
                                })
                            }
                        })
                    }',
                    'btnClick' => 'js:function(queryStr){
                        $.getJSON(queryStr, {}, function(json){
                            if("success" == json.status){
                                $("#query-result").text("")
                                $.each(json.testcases, function(k, v){
                                    $("#query-result").append("<option value=\"" + k + "\" title=\"" + v.url  + "\">" + v.name + "</option>")
                                })
                            }
                        })
                    }',
                    'queryClick' => 'js:function(queryStr){
                        $.getJSON(queryStr, {}, function(json){
                            if("success" == json.status){
                                $("#query-result").text("")
                                $.each(json.testcases, function(k, v){
                                    $("#query-result").append("<option value=\"" + k + "\" title=\"" + v.url  + "\">" + v.name + "</option>")
                                })
                            }
                        })
                    }',
                    'reset' => 'js:function(){
                        $("#query-result").text("")
                    }'
                ))
            ));
            ?>
        </div>
        <div class="row-fluid">
            <?php
            echo CHtml::label(Yii::t('TestCase', 'Query Result'), 'query-result', array('class' => 'span2'));
            echo CHtml::dropDownList('query-result', '', array(), array(
                'multiple' => 'multiple',
                'class' => 'span10 focus',
                'style' => 'height: 100px;'
            ));
            ?>
        </div>
        <div class="row-fluid">
            <div class="span7 offset5">
                <button class="btn select-case">&darr; <?php echo Yii::t('TestCase', 'Select'); ?></button>
                <button class="btn cancel-case">&uarr; <?php echo Yii::t('TestCase', 'Cancel'); ?></button>
            </div>
        </div>
        <div class="row-fluid" style="margin-bottom: 0">
            <?php
            echo CHtml::label(Yii::t('TestCase', 'Selected Case'), 'selected-case', array('class' => 'span2'));
            echo CHtml::dropDownList('testcases[]', '', array(), array(
                'multiple' => 'multiple',
                'class' => 'span9 focus',
                'style' => 'height: 200px;',
                'id' => 'selected-case'
            ));
            ?>
            <div class="span1" style="padding-top: 60px">
                <button class="btn move-up">&uarr;</button><br/><br/>
                <button class="btn move-down">&darr;</button>
            </div>
        </div>        
    </div>
    <!-- case fields end -->
</div>

<div id="env-fields">
    <div class="row-fluid">
        <?php
        echo CHtml::activeLabel($job, 'machine_id', array('class' => 'span2'));
        echo '<div class="span10" id="machine-selector">';
        $this->widget('application.extensions.combobox.InputComboBox', array(
            'model' => $job,
            'attribute' => 'machine_id',
            // data to populate the select. Must be an array.
            'data' => Machine::model()->getMachineOptions(),
            // options passed to plugin
            'options' => array(
                // JS code to execute on 'select' event, the selected item is
                // available through the 'item' variable.
                'onSelect' => '',
                // JS code to be executed on 'change' event, the input is available
                // through the '$(this)' variable.
                'onChange' => '',
                // If false, field value must be present in the select.
                // Defaults to true.
                'allowText' => false,
                'showStyle' => true,
            ),
            // Options passed to the text input
            'htmlOptions' => array(
                'class' => 'focus span12', 
                'style' => 'margin-left: 0',
                'placeholder' => Yii::t('Job', 'Machine Tip'),
            ),
        ));
        echo '</div>';
        ?>
    </div>
    <div class="row-fluid">
        <?php
        echo CHtml::activeLabel($job, 'sudoer', array('class' => 'span2'));
        echo CHtml::activeTextField($job, 'sudoer', array('class' => 'focus span4'));
        echo CHtml::activeLabel($job, 'timeout', array('class' => 'span2'));
        echo CHtml::activeTextField($job, 'timeout', array('class' => 'focus span3'));
        echo CHtml::tag('label', array('class' => 'span1'), Yii::t('Run', 'Minutes'));
        ?>
    </div>
    <div class="row-fluid">
        <?php
        echo CHtml::activeLabel($job, 'crucial', array('class' => 'span2'));
        echo '<div class="span4" id="curical-opts">';
        echo CHtml::activeRadioButtonList($job, 'crucial', $job->getCrucialOptions(), array('separator'=>'&nbsp;&nbsp;'));
        echo '</div>';
        echo CHtml::activeLabel($job, 'failed_repeat', array('class' => 'span2'));
        echo CHtml::tag('input', array('value' => $job->failed_repeat, 
            'type' => 'number', 'max' => 99, 'min' => '0', 'maxlength' => '2',
            'class' => 'focus span1', 'style' => 'text-align: center',
            'placeholder' => '0', 'name' => 'Job[failed_repeat]', 'id' => 'Job_failed_repeat'
        ));
        ?>
    </div>
</div>
<?php 
echo CHtml::endForm();
$this->endWidget('zii.widgets.jui.CJuiDialog');
?>