<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/report.css" />
<div class="content">
    <div class="sub-nav">
        <?php
        $items = array(
            array('label' => Yii::t('Task', 'Report')),
        );
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'path-nav',
            'items' => $items,
            'firstItemCssClass' => 'first',
            'lastItemCssClass' => 'last'
        ));
        ?>
    </div>
    <div class="main-detail" style="padding-right: 1px">
        <div class="report-control">
            <?php
            echo '<div class="action-group">';
            echo CHtml::button(Yii::t('Report', 'Send Report'), array('class' => 'btn send-current-report')); 
            echo '</div>';
            echo CHtml::dropDownList('products', Yii::app()->user->currentProduct, Yii::app()->user->getProductOpts(), array('id' => 'products', 'options' => Yii::app()->user->getProductionOptsClass()));
            echo CHtml::dropDownList('task_type', $report->task_type, $report->getTypeOptions());
            $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                'model' => $report,
                'attribute' => 'date',
                'options' => array(
                    'showAnim' => 'fold',
                    'dateFormat' => 'yy-mm-dd',
                ),
                'language' => Yii::app()->language,
                'htmlOptions' => array('class' => 'focus')));
            ?>
        </div>
        <div class="report-search">
            <?php
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array(
                    'action' => Yii::app()->getBaseUrl(true)
                    . '/#table#/index/task_type/'
                    . $report->task_type . '/date/' . $report->date
                    . '/product_id/' . $report->product_id . '/r/1',
                    'cTable' => 'report',
                    'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
                    'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
                    'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
                    'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete', 
                    'tables' => array(
                        'report' => array(
                            'label' => '报表',
                            'items' => array(
                                'task_name' => array(
                                    'label' => '任务名称',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '包含',
                                        '-' => '不包含',
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                ),
                                'project_path' => array(
                                    'label' => '所属项目',
                                    'type' => 'text',
                                    'operators' => array(
                                        'in' => '在某路径下',
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    )
                                ),
                                'status' => array(
                                    'label' => '运行状态',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                    'data' => CommandRun::model()->getStatusOptions()
                                ),
                                'responsible_username' => array(
                                    'label' => '负责人',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                        'tl' => 'TL等于',
                                    ),
                                    'data' => Yii::app()->user->getUsernameOpts()
                                ),
                            ),
                        ),
                    ),
                ),
            ));
            ?>
        </div>
        <div style="clear:both"></div>
        <?php
        $data = $report->getDetailObj()->getCount($condition);
        $case_passed = $data['case']['case_passed'][count($data['case']['case_passed']) - 1];
        $case_failed = $data['case']['case_failed'][count($data['case']['case_failed']) - 1];
        $task_success = $data['task']['success'];
        $task_failure = $data['task']['failure'];
        $task_other = $data['task']['other'];

        $pieData = array(
            array(
                'type' => 'pie',
                'data' => array(
                    array(
                        'name' => Yii::t('Report', 'Case Passed'),
                        'y' => $case_passed,
                        'color' => '#89A54E',
                    ),
                    array(
                        'name' => Yii::t('Report', 'Case Failed'),
                        'y' => $case_failed,
                        'color' => '#AA4643',
                    ),
                    array(
                        'name' => 'fill',
                        'visible' => false,
                        'y' => $case_passed + $case_failed == 0 ? 1 : 0,
                    )
                ),
                'center' => array('30%', '50%'),
            ),
            array(
                'type' => 'pie',
                'data' => array(
                    array(
                        'name' => Yii::t('Task', 'Success Task'),
                        'y' => $task_success,
                        'color' => '#89A54E',
                    ),
                    array(
                        'name' => Yii::t('Task', 'Failed Task'),
                        'y' => $task_failure,
                        'color' => '#AA4643',
                    ),
                    array(
                        'name' => Yii::t('Task', 'Other Task'),
                        'y' => $task_other,
                        'color' => '#E99D12',
                    ),
                    array(
                        'name' => 'fill',
                        'y' => $task_success + $task_failure + $task_other== 0 ? 1 : 0,
                        'visible' => false
                    ),
                ),
                'center' => array('70%', '50%')
            )
        );

        $lineData = array(
            array(
                'name' => Yii::t('Report', 'Case Passed'),
                'pointStart' => (strtotime($report->date . ' UTC') - 86400 * 364) * 1000,
                'pointInterval' => 86400 * 1000,
                'data' => $data['case']['case_passed'],
            ),
            array(
                'name' => Yii::t('Report', 'Case Failed'),
                'pointStart' => (strtotime($report->date . ' UTC') - 86400 * 364) * 1000,
                'pointInterval' => 86400 * 1000,
                'data' => $data['case']['case_failed'],
            ),
            array(
                'name' => Yii::t('Report', 'Case Total'),
                'pointStart' => (strtotime($report->date . ' UTC') - 86400 * 364) * 1000,
                'pointInterval' => 86400 * 1000,
                'data' => $data['case']['case_total']
            ),
        );
        ?>
        <div>
            <?php
            //Pie Chart
            $this->Widget('application.extensions.highcharts.HighchartsWidget', array(
                'options' => array(
                    'chart' => array(
                        'marginTop' => 30,
                        'reflow' => true,
                    ),
                    'title' => array(
                        'text' => null
                    ),
                    'exporting' => array('enabled' => false),
                    'credits' => array('enabled' => false),
                    'plotOptions' => array(
                        'pie' => array(
                            'allowPointSelect' => true,
                            'cursor' => 'pointer',
                            'dataLabels' => array(
                                'enabled' => true,
                                'formatter' => 'js:function() {
                                    return this.point.name + ": " + 
                                    Math.round(this.point.percentage*10)/10+ "%";
                                }'
                            ),
                        )
                    ),
                    'tooltip' => array(
                        'formatter' => 'js:function(){
                            if (this.point.name == "fill")
                                return false;                            
                            return this.point.name + ": " + this.y;
                        }'
                    ),
                    'series' => $pieData,
                ),
                'chartType' => 'highcharts',
                'htmlOptions' => array('style' => 'height: 250px; width: 100%')
            ));

            //line chart
            $this->Widget('application.extensions.highcharts.HighchartsWidget', array(
                'options' => array(
                    'chart' => array(
                        'borderWidth' => 0,
                        'marginTop' => 20,
                        'type' => 'spline'
                    ),
                    'colors' => array(
                        '#89A54E',
                        '#AA4643',
                        '#4572A7',
                        '#80699B',
                    ),
                    'rangeSelector' => array(
                        'selected' => 0,
                    ),
                    'exporting' => array('enabled' => false),
                    'credits' => array('enabled' => false),
                    'legend' => array(
                        'enabled' => true,
                        'floating' => true,
                        'layout' => 'vertical',
                        'align' => 'left',
                        'shadow' => true,
                        'verticalAlign' => 'top',
                        'x' => 40,
                        'y' => 40,
                        'backgroundColor' => '#FFFFFF',
                    ),
                    'tooltip' => array(
                        'formatter' => 'js:function() {
                            var tip = "<b>"+Highcharts.dateFormat("%a %b %e %Y", this.x)+"</b>";
                            $.each(this.points, function(i, point) {
                                tip += "<br/>" + point.series.name + ": "+ point.y;
                                if (point.series.name == "' . Yii::t('VReport', 'Line Coverage Rate') . '")
                                    tip += "%";
                            });
                            return tip;
                        }'
                    ),
                    'yAxis' => array(
                        array(
                            'title' => array(
                                'text' => null,
                            ),
                            'min' => 0,
                        ),
                        array(
                            'title' => array(
                                'text' => null
                            ),
                            'labels' => array(
                                'formatter' => 'js:function(){
                                    return this.value + "%";
                                }',
                                'style' => array(
                                    'color' => '#89A54E'
                                )
                            ),
                            'min' => 0,
                            'opposite' => true
                        ),
                    ),
                    'plotOptions' => array(
                        'series' => array(
                            'marker' => array(
                                'enabled' => false,
                                'states' => array(
                                    'hover' => array(
                                        'enabled' => true,
                                        'radius' => 3
                                    )
                                )
                            ),
                        ),
                    ),
                    'series' => $lineData,
                ),
                'chartType' => 'highstock',
                'htmlOptions' => array('style' => 'height: 400px; width: 100%'),
            ));
            ?>
        </div>
        <div class="report-items">
            <table class="stats-table">
                <tr>
                    <th class="col-num">
                        <?php
                        echo CHtml::radioButton('module_id', empty($report->module_id), array('value' => '', 'class' => 'module'));
                        ?>
                    </th>
                    <th>
                        <?php
                        echo $report->getAttributeLabel('task_name');
                        ?>
                    </th>
                    <th>
                        <?php
                        echo $report->getAttributeLabel('project_name');
                        ?>
                    </th>
                    <th>
                        <?php
                        echo Yii::t('VReport', 'Case Ratio');
                        ?>
                    </th>
                    <th>
                        <?php
                        echo Yii::t('VReport', 'CC Result');
                        ?>
                    </th>
                    <th style="text-align: center">
                        <?php
                        echo $report->getAttributeLabel('status');
                        ?>
                    </th>
                    <th>
                        <?php
                        echo $report->getAttributeLabel('responsible_realname');
                        ?>
                    </th>
                </tr>
                <?php
                $reports = $report->search($condition);
                $module = '';
                foreach ($reports as $key => $r) {
                    if ($module != $r->module_id) {
                        echo CHtml::tag('tr', array('class' => 'even'));
                        echo CHtml::tag('td', array('class' => 'col-num'), CHtml::radioButton('module_id' . $r->module_id, ($r->module_id == $report->module_id), array('value' => $r->module_id, 'class' => 'module')));
                        echo CHtml::tag('td', array('colspan' => 6, 'style' => 'font-weight: bold; text-align: left'), CHtml::tag('label', array('for' => 'module_id' . $r->module_id), $r->module_name));
                        $module = $r->module_id;
                    }
                    if ($r->module_id != $report->module_id && !empty($report->module_id)) {
                        continue;
                    }
                    echo CHtml::tag('tr', array('class' => 'odd'));
                    echo CHtml::tag('td', array('class' => 'col-num'));
                    echo CHtml::tag('td', array('class' => 'col-target'), CHtml::link(CHtml::encode($r->task_name), Yii::app()->getBaseUrl(true) . '/task/view/id/' . $r->task_id . '/runID/' . $r->task_run_id, array('target' => '_blank')));
                    echo CHtml::tag('td', array('class' => 'col-project'), $r->project_name);
                    echo CHtml::tag('td', array('class' => 'col-ratio'), $r->getCaseRatioHtml());
                    echo CHtml::tag('td', array('class' => 'col-cc'), $r->getCCResultHtml());
                    echo CHtml::tag('td', array('class' => 'col-status'), $r->getStatusText());
                    echo CHtml::tag('td', array('class' => 'col-name'), $r->responsible_realname);
                }
                ?>
            </table>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $("#ReportUnit_date").datepicker({
            onSelect: function(){
                var date = $.datepicker.formatDate('yy-mm-dd', ($("#ReportUnit_date").datepicker("getDate")));
                location.href = getRootPath() + "/report/index/date/" + date;
            },
            dateFormat: "yy-mm-dd"
        });
        
        $("select#task_type").change(function(){
            location.href = getRootPath() + "/report/index/task_type/" + $(this).val();
        })
        
        $("input.module").click(function(){
            location.href = getRootPath() + "/report/index/module_id/" + $(this).val();
        })
        
        $("select#products").change(function(){
            location.href = getRootPath() + "/report/index/product_id/" + $(this).val();;            
        });
                
        $("input.send-current-report").on("click", function(){
            var url = location.href.replace(/index/, "sendcurrentreport");
            $.getJSON(url, null, function(json){
                if(json.status) {
                    $("div.notify").text("已发送")
                } else {
                    $("div.notify").text(json.msg)
                }
                $("div.notify").fadeIn().delay(3000).fadeOut()
            })
        })
    });
</script>