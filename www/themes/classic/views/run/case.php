<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title><?php echo Yii::t('Run', 'Case Detail');?></title>
        <link rel="shortcut icon" href="<?php echo Yii::app()->baseUrl; ?>/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/status.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/style.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/lightbox.css?ver=1"/>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lib.js?ver=1"></script>
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/lightbox.js?ver=1"></script>    
        <script type="text/javascript" src="<?php echo Yii::app()->theme->baseUrl; ?>/assets/js/config.js?ver=1"></script>
    </head>
    <body style="overflow-y: hidden; min-width: 1100px;">
        <div class="content">
            <div class="sub-nav">
                <?php
                $this->widget('zii.widgets.CMenu', array(
                    'id' => 'path-nav',
                    'encodeLabel'=>false,
                    'items' => $items,
                    'firstItemCssClass' => 'first',
                    'lastItemCssClass' => 'last'
                ));
                ?>            
            </div>
            <div class="main-detail">
            <?php
                $this->widget('GridView', array(
                    'id' => 'case-result-list',
                    'dataProvider' => $resultProvider,
                    'selectableRows' => 0,
                    'enablePageSize' => true,
                    'htmlOptions' => array('class' => 'widget-view'),
                    'afterAjaxUpdate' => 'js:function(){setListHeight();triggerPageSizeChange();click4Detail();}',
                    'columns' => array(
                        array(
                            'name' => 'test_case_id',
                            'type' => 'raw',
                            'value' => '!empty($data->test_case_id) ? CHtml::link($data->test_case_id, Yii::app()->getBaseUrl(true) . "/case/view/id/" . $data->test_case_id, array("target" => "blank")) : "NA"',
                            'headerHtmlOptions' => array('style' => 'width: 100px'),
                            'htmlOptions' => array('style' => 'width: 100px'),
                        ),
                        array(
                            'name' => 'case_name',
                            'headerHtmlOptions' => array('style' => 'text-align: left; width: 250px'),
                            'htmlOptions' => array('style' => 'text-align: left; width: 250px'),
                        ),
                        array(
                            'name' => 'case_result',
                            'value' => '$data->getResultText()',
                            'cssClassExpression' => '$data->getResultStyle()',
                            'headerHtmlOptions' => array('style' => 'width: 100px'),
                            'htmlOptions' => array('style' => 'width: 100px'),
                        ),
                        array(
                            'name' => 'case_info',
                            'type' => 'raw',
                            'headerHtmlOptions' => array('class' => 'name'),
                            'htmlOptions' => array('class' => 'name'),
                            'value' => '$data->getCaseInfo()',
                        ),
                    ),
                ));
            ?>
            </div>
        </div>
        <script type="text/javascript">
            var click4Detail = function() {
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
            });
        </script>        
    </body>
</html>