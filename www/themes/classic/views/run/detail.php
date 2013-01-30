<input type="hidden" value="runDetail-<?php echo $vRun->id; ?>" />
<table>
    <tbody>
    <tr>
        <td>
        <?php
            $case_total = intval($vRun->case_total_amount);
            $case_passed = intval($vRun->case_pass_amount);
            $case_failed = intval($vRun->case_fail_amount);
            $case_blocked = intval($vRun->case_block_amount);
            $case_skipped = intval($vRun->case_skip_amount);

            $pieData = array(
                array(
                    'type' => 'pie',
                    'data' => array(
                        array(
                            'name' => Yii::t('Run', 'Case Passed'),
                            'y' => $case_passed,
                            'color' => '#89A54E',
                        ),
                        array(
                            'name' => Yii::t('Run', 'Case Failed'),
                            'y' => $case_failed,
                            'color' => '#AA4643',
                        ),
                        array(
                            'name' => Yii::t('Run', 'Case Skipped'),
                            'y' => $case_skipped,
                            'color' => '#E99D12',
                        ),
                        array(
                            'name' => Yii::t('Run', 'Case Blocked'),
                            'y' => $case_blocked,
                            'color' => '#999',
                        ),
                        array(
                            'name' => 'fill',
                            'y' => $case_total==0?1:0,
                            'visible' => $case_total==0?true:false,
                            'color' => 'rgba(0,0,0,0)',
                        ),
                    ),
                )
            );
            $this->Widget('application.extensions.highcharts.HighchartsWidget', array(
                'options' => array(
                    'chart' => array(
                        'reflow' => true,
                        'backgroundColor' => '#FAFBFC',
                        'borderRadius' => 0,
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
                                    if (this.point.name == "fill")
                                        return null;
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
                'htmlOptions' => array('style' => 'height: 200px; width: 350px;')
            ));
        ?>
        </td>
        <td style="width: 100%;">
            <div class="case-list">
            <?php
                $this->widget('GridView', array(
                    'id' => 'results',
                    'dataProvider' => $resultProvider,
                    'selectableRows' => 0,
                    'htmlOptions' => array('class' => 'widget-view'),
                    'columns' => array(
                        array(
                            'name' => 'test_case_id',
                            'type' => 'raw',
                            'value' => '!empty($data->test_case_id) ? CHtml::link($data->test_case_id, $data->getCaseLink(), array("target" => "blank")) : "NA"',
                            'headerHtmlOptions' => array('style' => 'width: 100px'),
                            'htmlOptions' => array('style' => 'width: 100px'),
                        ),
                        array(
                            'name' => 'case_name',
                            'headerHtmlOptions' => array('style' => 'text-align: left; width: 300px'),
                            'htmlOptions' => array('style' => 'text-align: left; width: 300px'),
                        ),
                        array(
                            'name' => 'case_result',
                            'value' => '$data->getResultText()',
                            'cssClassExpression' => '$data->getResultStyle()'
                        ),
                        array(
                            'name' => 'case_info',
                            'headerHtmlOptions' => array('class' => 'name'),
                            'htmlOptions' => array('class' => 'name'),
//                            'value' => 'nl2br($data->case_info)'
                        ),
                    ),
                ));
            ?>
            </div>
        </td>
    </tr>
    </tbody>
</table>