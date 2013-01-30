<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/case.css" />
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/codemirror.css" />
<div class="content">
    <div class="sub-nav">
        <?php
        $items = array(array('label' => '#' . $vTestCase->id . ' ' . $vTestCase->name));
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
            <input type="button" value="<?php echo Yii::t("TestCase", "Update Case"); ?>" class="btn update-case" />
            <input type="button" value="<?php echo Yii::t("TestCase", "Delete Case"); ?>" class="btn delete-case" />
        </div>
        <div class="detail block">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTestCase, 'name', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span11'), CHtml::encode($vTestCase->name));
                echo CHtml::hiddenField('case-id', $vTestCase->id);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTestCase, 'framework', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span2'), CHtml::encode($vTestCase->getFrameworkText()));
                echo CHtml::activeLabel($vTestCase, 'project_id', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span8'), CHtml::encode($vTestCase->project_path));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTestCase, 'code_url', array('class' => 'span1'));
                echo '<div class="span6">';
                echo $vTestCase->getCodeLink() . ' (' . CHtml::link('show', 'javascript:;', array('class' => 'code-trigger')). ')';
                echo '</div>';
                echo CHtml::activeLabel($vTestCase, 'func_name', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span4'), CHtml::encode($vTestCase->func_name));
                ?>
            </div>
            <div style="text-align: center;display: none;">
                <? echo CHtml::image(Yii::app()->theme->baseUrl . '/assets/images/loading.gif'); ?>
            </div>
            <textarea id="code" style="display:none"></textarea>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($vTestCase, 'created_by', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTestCase->created_by_realname . '&nbsp;&nbsp;' . $vTestCase->create_time);
                echo CHtml::activeLabel($vTestCase, 'updated_by', array('class' => 'span1'));
                echo CHtml::tag('span', array('class' => 'span3'), $vTestCase->updated_by_realname . '&nbsp;&nbsp;' . $vTestCase->update_time);
                ?>
            </div>
        </div>
        <div class="follow detail-info">
            <div class="detail-title"><?php echo Yii::t('TestCase', 'Case Results'); ?></div>
            <?php
            $this->widget('GridView', array(
                'id' => 'case-result-list',
                'dataProvider' => $vTestCase->getResultProvider(),
                'selectableRows' => 0,
                'enablePageSize' => true,
                'htmlOptions' => array('class' => 'widget-view'),
                'columns' => array(
                    array(
                        'name' => 'case_result',
                        'value' => '$data->getResultText()',
                        'cssClassExpression' => '$data->getResultStyle()',
                        'headerHtmlOptions' => array('style' => 'width: 40px'),
                        'htmlOptions' => array('style' => 'width: 40px'),
                    ),
                    array(
                        'name' => 'case_info',
                        'headerHtmlOptions' => array('class' => 'name'),
                        'htmlOptions' => array('class' => 'name'),
                    ),
                    array(
                        'name' => 'created_by_realname',
                        'headerHtmlOptions' => array('style' => 'width: 40px'),
                        'htmlOptions' => array('style' => 'width: 40px'),
                    ),
                    array(
                        'name' => 'create_time',
                        'headerHtmlOptions' => array('style' => 'width: 100px'),
                        'htmlOptions' => array('style' => 'width: 100px'),
                    ),
                ),
            ));
            ?>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/codemirror.js"></script>
<script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/shell.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    var caseId = $("#case-id").val();
    $("input.update-case").click(function(){
        location.href = getRootPath() + "/case/update/id/" + caseId;
    })

    $("input.delete-case").click(function(){
        if(confirm(lang.confrimDeleteCommand)){
            location.href = getRootPath() + "/case/delete/id/" + caseId;
        }
    })

    $("div#case-result-list table.items tbody tr").click(function(){
        if($(this).find("td:last").css('white-space') == 'nowrap')
        {
            $(this).find("td").eq(1).css('white-space', 'normal')
            $(this).find("td:last").css('white-space', 'normal')
            var html = $(this).find("td:last").html();
            $(this).find("td:last").html(html.split("\n").join("<br>"));
        }
        else
        {
            $(this).find("td").eq(1).css('white-space', 'nowrap')
            $(this).find("td:last").css('white-space', 'nowrap')
            var html = $(this).find("td:last").html();
            $(this).find("td:last").html(html.split("<br>").join("\n"));
        }
    });

    
    $(".code-trigger").toggle(function(){
        $(this).text("hide")
        if($("#code").next(".CodeMirror").length == 0){
            $("#code").prev("div").show()
            $.getJSON(toast.getCode, {url: $(".code_url").attr("href")}, function(json){
                if("success" == json.status) {
                    $("#code").val(json.info)
                    $("#code").prev("div").hide()
                    var codeMirror = CodeMirror.fromTextArea($("#code").get(0), {
                        mode: "shell",
                        lineNumbers: true,
                        readOnly: true
                    })
                    $(".CodeMirror-scroll").css("max-height", "350px")
                }
            })
        } else {
            $("#code").next(".CodeMirror").show()
        }
    }, function(){
        $("#code").next(".CodeMirror").hide()
        $(this).text("show")
    })
})
</script>