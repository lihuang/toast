<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title><?php
echo Yii::t('Run', 'Case Detail');
?></title>
        <link rel="shortcut icon" href="<?php echo Yii::app()->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/status.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/style.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/comparsion.css" />
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.js?ver=1"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/config.js?ver=2"></script>
    </head>
    <body style="overflow-y: hidden; min-width: 1100px;">
        <div class="content">
            <div class="sub-nav">
                <?php
                $this->widget('zii.widgets.CMenu',
                        array(
                    'id' => 'path-nav',
                    'encodeLabel' => false,
                    'items' => $items,
                    'firstItemCssClass' => 'first',
                    'lastItemCssClass' => 'last'
                ));
                ?>            
            </div>
            <div class="main-detail">
                <?php
                list($host1, $host2) = RunUtility::getHost($resultProvider);
                $this->widget('GridView',
                        array(
                    'id' => 'case-result-list',
                    'dataProvider' => $resultProvider,
                    'selectableRows' => 0,
                    'enablePageSize' => true,
                    'htmlOptions' => array('class' => 'widget-view'),
                    'afterAjaxUpdate' => 'js:function(){setListHeight();triggerPageSizeChange();click4Detail();}',
                    'columns' => array(
                        array(
                            'header' => Yii::t('Comparison', 'Query'),
                            'name' => 'case_name',
                            'headerHtmlOptions' => array('class' => 'name'),
                            'htmlOptions' => array('class' => 'name'),
                        ),
                        array(
                            'name' => 'case_result',
                            'value' => '$data->getResultText()',
                            'cssClassExpression' => '$data->getResultStyle()',
                            'headerHtmlOptions' => array('class' => 'result'),
                            'htmlOptions' => array('class' => 'result'),
                        ),
                        array(
                            'name' => 'case_info',
                            'headerHtmlOptions' => array('class' => 'info'),
                            'htmlOptions' => array('class' => 'info'),
                            'type' => 'raw',
                            'value' => '$data->case_info . "\nDiff: <a href=\"javascript:;\" class=\"diff\" data-case-id=\"" . $data->id . "\">detail</a>"'
                        ),
                    ),
                ));
                ?>
            </div>
        </div>
        <?php
        $this->beginWidget('zii.widgets.jui.CJuiDialog',
                array(
            'id' => 'case-detail-dailog',
            'theme' => 'base',
            'htmlOptions' => array('style' => 'display:none'),
            'options' => array(
                'title' => Yii::t('Comparison', 'Output'),
                'autoOpen' => false,
                'resizable' => true,
                'modal' => true,
                'width' => 800,
                'height' => 600,
            ),
        ));
        echo '<pre id="case-detail" class="output"></pre>';
        $this->endWidget('zii.widgets.jui.CJuiDialog');
        ?>
    </body>
    <script type="text/javascript">
        var click4Detail = function() {
            $("div#case-result-list table.items tbody tr").click(function(){
                if($(this).find("td:last").css('white-space') == 'nowrap')
                {
                    $(this).find("td").eq(0).css('white-space', 'normal').css('word-wrap', 'break-word')
                    $(this).find("td:last").css('white-space', 'normal').css('word-wrap', 'break-word')
                    var html = $(this).find("td:last").html();
                    $(this).find("td:last").html(html.split("\n").join("<br>"));
                }
                else
                {
                    $(this).find("td").eq(0).css('white-space', 'nowrap').css('word-wrap', 'normal')
                    $(this).find("td:last").css('white-space', 'nowrap').css('word-wrap', 'normal')
                    var html = $(this).find("td:last").html();
                    $(this).find("td:last").html(html.split("<br>").join("\n"));
                }
                $(".diff").click(function(){
                    $.get(toast.getCaseOutput, {'resultId': $(this).attr("data-case-id")}, function(res){
                        $("#case-detail").text(res);
                        $("#case-detail-dailog").dialog("open")
                    })
                    return false
                })
            });
        }
        $(document).ready(function(){
            setListHeight();
            click4Detail();
            $(window).resize(function(){
                setListHeight();
            })
            $("select.page-size").change(function(){
                var data = {'pagesize': $(this).val()};
                $.get(toast.setPageSize, data, function(){
                    location.reload();
                });
            })

            var filterChanged = function() {
                var filter = [];
                $("input[name='results[]']").each(function() {
                    if (this.checked)
                        filter.push(this.value);
                })
                var url = getRootPath() + '/run/case';
                if ($("#commands").val() == -1)
                    url += '/taskrun/' + $("#taskrun-id").val();
                else
                    url += '/commandrun/' + $("#commands").val();
                url += '/filter/' + filter.join('|');
                location.href = url;

            }
            $("input[name='results[]']").bind('change', filterChanged);
            $("#commands").bind('change', filterChanged);
            $(".diff").click(function(){
                $.get(toast.getCaseOutput, {'resultId': $(this).attr("data-case-id")}, function(res){
                    $("#case-detail").text(res);
                    $("#case-detail-dailog").dialog("open")
                })
                return false
            })
        });
    </script>
</html>