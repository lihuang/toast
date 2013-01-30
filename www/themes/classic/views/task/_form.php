<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/task.css" />
<div class="content">
    <div class="sub-nav">
    <?php
    $currentProductId = isset($task->project) ? $task->project->product_id : Yii::app()->user->getCurrentProduct();
    $currentProductName = isset($task->project) ? $task->project->product->name 
                                        : Yii::app()->user->getCurrentProduct(true)->name;
    $this->widget('zii.widgets.CMenu', array(
        'id' => 'path-nav',
        'items' => $task->getFormNavItems(),
        'firstItemCssClass' => 'first',
        'lastItemCssClass' => 'last'
    ));
    ?>
    </div>
    <div class="main-detail">
        <?php echo CHtml::beginForm($action); ?>
        <div class="button-actions clearfix">
            <?php echo join('', $task->getBtnlist());?>
        </div>
        <?php echo CHtml::errorSummary($task); ?>
        <div class="detail block basic-info clearfix">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($task, 'type', array('class' => 'span1'));
                $this->widget('application.extensions.select2.ESelect2', array(
                    'model' => $task,
                    'attribute' => 'type',
                    'data' => $task->getTypeOptions(FALSE),
                    'htmlOptions' => array(
                        'class' => 'span2'
                    ),
                    'options' => array(
                        'minimumResultsForSearch' => 10,
                        'placeholder' => Yii::t('Task', 'Select Task Type'),
                    )
                ));
                echo CHtml::activeLabelEx($task, 'name', array('class' => 'span1 offset1'));
                echo CHtml::activeTextField($task, 'name', array('class' => 'focus span7'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($task, 'responsible', array('class' => 'span1'));
                echo CHtml::activeHiddenField($task, 'responsible_realname');
                echo CHtml::activeTextField($task, 'responsible', array('class' => 'span2'));
                $this->widget('application.extensions.select2.ESelect2', array(
                    'selector'=>'#Task_responsible',
                    'options' => array(
                        'minimumInputLength' => 2,
                        'ajax' => array(
                            'url' => Yii::app()->getBaseUrl() . '/user/lookup2',
                            'dataType' => 'json',
                            'data' => 'js:function (term, page) {
                                return {
                                    term: term,
                                    limit: 10,
                                    page: page
                                }
                            }',
                            'results' => 'js:function (data, page) {
                                var more = (page * 10) < data.total;
                                return {
                                    results: data.users,
                                    more: more
                                };
                            }'
                        ),
                        'formatResult' => 'js:function(data){
                            return data.label
                        }',
                        'formatSelection' => 'js:function(data){
                            return (data.realname || data.username);
                        }',
                        'initSelection' => 'js:function (element, callback) {
                            var id = $("#Task_responsible").val();
                            var name = $("#Task_responsible_realname").val();
                            callback({id: id, label: name, realname: name, username: name});
                        }'
                    )
                ));
                echo CHtml::activeLabelEx($task, 'project_id', array('class' => 'span1 offset1'));
                echo CHtml::hiddenField('product-id', $currentProductId);
                $this->widget('application.extensions.masspicker.MassPickerWidget', array(
                    'model' => $task,
                    'attribute' => 'project_id',
                    'value' => empty($task->project) ? '' : $task->project->path,
                    'options' => array(
                        'click' => 'js:function(){
                            data = {productid: $("#product-id").val()};
                            $.get(toast.getProjectTree, data, function(html){
                                $("#dlg-find-project").html(html);
                                $("#dlg-find-project").treeview({
                                    persist: "cookie",
                                    collapsed: true
                                });
                                $("#dlg-find-project a").click(function(){
                                    var projectId = $(this).attr("data-project-id");
                                    var projectPath = $(this).attr("data-project-path");
                                    $("#Task_project_id").val(projectId);
                                    $("#Task_project_id-input").val(projectPath);
                                    $("#dlg-find-project").dialog("close");
                                })
                            })
                            $("#dlg-find-project").dialog("open");
                        }'
                    ),
                    'htmlOptions' => array('class' => 'focus span7',
                        'placeholder' => '/' . $currentProductName),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($task, 'cron_time', array('class' => 'span1'));
                $this->Widget('application.extensions.timereditor.TimerEditorWidget', array(
                        'model' => $task,
                        'attribute' => 'cron_time',
                        'htmlOptions' => array('class' => 'focus span2'),
                ));
                echo '<div class="offset1 span3 exclusive-checkbox">';
                echo CHtml::activeLabelEx($task, 'exclusive', array('style' => 'float: left; margin-right: 5px', 
                    'title' => Yii::t('Task', 'Exclusive Tip')));
                echo CHtml::activeCheckBox($task, 'exclusive', array(
                    'style' => 'margin-right: 30px', 
                    'title' => Yii::t('Task', 'Exclusive Tip')
                ));
                echo CHtml::activeLabelEx($task, 'wait_machine', array('style' => 'float: left; margin-right: 5px', 
                    'title' => Yii::t('Task', 'Wait Machine Tip')));
                echo CHtml::activeCheckBox($task, 'wait_machine', array(
                    'title' => Yii::t('Task', 'Wait Machine Tip')
                ));
                echo '</div>';
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($task, 'build', array('class' => 'span1'));
                $task->build = trim($task->build, ',');
                echo CHtml::activeTextField($task, 'build', array(
                    'class' => 'focus span11',
                    'placeholder' => Yii::t('Task', 'Build tip')
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($task, 'svn_url', array('class' => 'span1'));
                echo CHtml::activeTextField($task, 'svn_url', array(
                    'class' => 'focus span11',
                    'placeholder' => Yii::t('Task', 'Svn url tip')
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($task, 'report_to', array('class' => 'span1'));
                $this->widget('application.extensions.select2.ESelect2', array(
                    'model' => $task,
                    'attribute' => 'report_filter',
                    'data' => $task->getReportFilterOptions(),
                    'htmlOptions' => array(
                        'class' => 'span2'
                    ),
                    'options' => array(
                        'minimumResultsForSearch' => 10,
                        'placeholder' => Yii::t('Task', 'Select Task Type'),
                    )
                ));
//                echo CHtml::activeDropDownList($task, 'report_filter', $task->getReportFilterOptions(), array('class' => 'span1'));
                echo CHtml::activeTextField($task, 'report_to', array('class' => 'span9'));
                $this->widget('application.extensions.select2.ESelect2', array(
                    'selector'=>'#Task_report_to',
                    'options' => array(
                        'minimumInputLength' => 2,
                        'multiple' => true,
                        'tokenSeparators' => array(',', ';', ' '),
                        'ajax' => array(
                            'url' => Yii::app()->getBaseUrl() . '/user/lookup2',
                            'dataType' => 'json',
                            'data' => 'js:function (term, page) {
                                return {
                                    term: term,
                                    limit: 10,
                                    page: page
                                }
                            }',
                            'results' => 'js:function (data, page){
                                var more = (page * 10) < data.total;
                                return {
                                    results: data.users, 
                                    more: more
                                };
                            }'
                        ),
                        'createSearchChoice' => 'js:function(term, data) {
                            return {id: term, label: term, realname: term};
                        }',
                        'formatResult' => 'js:function(data){
                            data.id = (data.realname || data.email || data.label);
                            return data.label;
                        }',
                        'formatSelection' => 'js:function(data){
                            return (data.realname || data.email || data.label);
                        }',
                        'initSelection' => 'js:function (element, callback) {
                            var data = []
                            var report_to = $(element).val();
                            var names = report_to.split(",");
                            for(var i = 0; i < names.length; i++)
                            {
                                data.push({id: names[i], label: names[i], realname: names[i], username: names[i]});
                            }
                            callback(data);
                        }'
                    )
                ));
                ?>
            </div>
        </div>
        <div class="stage-detail follow block">
            <?php
            $closeTag = CHtml::tag('div', array('class' => 'close'), CHtml::link('X', 'javascript:;') 
                            . CHtml::tag('span', array('class' => 'stage-name'), Yii::t('Task', 'Stage {num}', array('{num}' => 1))));
            $addJobTag = CHtml::tag('div', array('class' => 'add-job'), CHtml::link(Yii::t('Task', 'Add Job'), 'javascript:;'));
            $stageNum = 0;
            echo $closeTag;
            echo '<div class="stage stage-sortable">';
            foreach($jobs as $key => $job)
            {
                if($job->stage_num > $stageNum)
                {
                    echo $addJobTag;
                    echo '</div></div><div class="stage-detail follow block">' 
                        . $closeTag . '<div class="stage stage-sortable">';
                    $stageNum++;
                }
                echo '<div class="job">';
                $this->renderPartial('jobView', array(
                    'job' => $job,
                    'jobNum' => $key
                 ));
                 echo '</div>';
            }
            echo $addJobTag;
            echo '</div>';
            ?>
        </div>
        <div class="add-stage">
            <?php
            echo CHtml::link(Yii::t('Task', 'Add Stage'), 'javascript:;');
            ?>
        </div>
    <?php echo CHtml::endForm();?>
    </div>
</div>
<?php
$this->renderPartial('jobform', array(
    'currentProductId' => $currentProductId,
    'task' => $task,
));
$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
    'id' => 'dlg-find-project',
    'theme' => 'base',
    'htmlOptions' => array('style' => 'display:none'),
    'options' => array(
        'title' => $currentProductName,
        'autoOpen' => false,
        'resizable' => false,
        'modal' => true,
        'close' => 'js:function(event, ui) {
            $("#Task_project_id-input").focus();
        }'
    ),
));
echo '<div id="find-project"></div>';
$this->endWidget('zii.widgets.jui.CJuiDialog');
?>
<style type="text/css">
    div.stage {margin-bottom: 0px}
</style>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.form.js"></script>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/task.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    var goodexit = false;
    window.onbeforeunload = function () {
        if(!goodexit) {
            return lang.sureToLeave;
        }
    }
    
    $(".btn.return").click(function(){
        var href = getRootPath() + '/task'
         <?php 
         if($task->id !== NULL)
            echo "href += '/view/id/$task->id';"
        ?>
        location.href = href
    })
    $(".btn[name='saverun']").add($(".btn[name='save']")).click(function(){
        if(0 == $(".job").length) {
            alert(lang.needOneJob)
            return false
        }
        goodexit = true;
    })
    $("#Task_project_id-input").add($("#Task_cron_time")).on("keydown", function(event) {
        if(event.keyCode !== 9)
        {
            $(this).trigger("click");
            event.stopPropagation();
            event.preventDefault();
        }
    })
});
</script>