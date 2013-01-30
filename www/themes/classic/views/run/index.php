<div class="content">
    <div class="tree">
        <div class="sub-nav sub-nav-left">
            <div class="product-select">
                <?php 
                echo CHtml::dropDownList('products', Yii::app()->user->currentProduct, Yii::app()->user->getProductOpts(),
                        array('id' => 'products', 'options' => Yii::app()->user->getProductionOptsClass()));
                ?>
            </div>
        </div>
        <div id="project-tree">
        </div>
        </div>
    <div class="layout-right">
        <div class="sub-nav sub-nav-right">
            <?php
            $this->widget('zii.widgets.CMenu', array(
                'id' => 'sub-menu',
                'items' => array(
                    array('label' => Yii::t('TOAST', 'All Task'), 'url' => array('/task'), 'active' => false),
                    array('label' => Yii::t('TOAST', 'Created By Me'), 'url' => array('/task/index/q/@task created_by_username:(=={' . Yii::app()->user->username . '})'), 'active' => false),
                    array('label' => Yii::t('TOAST', 'Responsible By Me'), 'url' => array('/task/index/q/@task responsible_username:(=={' . Yii::app()->user->username . '})'), 'active' => false),
                    array('label' => Yii::t('TOAST', 'Recent Run'), 'url' => array('/run'), 'active' => true),
                )
            ));
            ?>
            <div class="search">
            <?php
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array(
                    'action' => Yii::app()->getBaseUrl(true) . '/#table#/index',
                    'cTable' => 'run',
                    'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
                    'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
                    'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
                    'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete', 
                    'tables' => array(
                        'run' => array(
                            'label' => '运行结果',
                            'items' => array(
                                'id' => array(
                                    'label' => 'ID',
                                    'type' => 'text',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                        '>=' => '大于等于',
                                        '>' => '大于',
                                        '<' => '小于',
                                        '<=' => '小于等于',
                                        '=' => '包含',
                                        '!=' => '不包含'
                                    ),
                                ),
                                'name' => array(
                                    'label' => '运行名称',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有',
                                    ),
                                ),
                                'product_id' => array(
                                    'label' => '所属产品',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于'
                                    ),
                                    'data' => Product::getAllProductsList()
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
                                'created_by_username' => array(
                                    'label' => '运行者',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                        'tl' => 'TL等于',
                                    ),
                                    'data' => Yii::app()->user->getUsernameOpts()
                                ),
                                'created_by_username' =>  array(
                                    'label' => '触发原因',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于'
                                    ),
                                    'data' => array(
                                        'ABS' => 'ABS触发',
                                        'TOAST' => '定时运行触发',
                                    ),
                                ),
                                'create_time' => array(
                                    'label' => '创建时间',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '等于',
                                        '-' => '不等于',
                                        '>=' => '大于等于',
                                        '>' => '大于',
                                        '<' => '小于',
                                        '<=' => '小于等于',
                                    ),
                                ),
                                'result' => array(
                                    'label' => Yii::t('Run', 'Result'),
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                    'data' => CommandRun::model()->getResultOptions()
                                ),
                                'status' => array(
                                    'label' => Yii::t('Run', 'Status'),
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                    'data' => CommandRun::model()->getStatusOptions()
                                ),
                            ),
                        ),
                    ),
                ),
            ));
            ?>
            </div>
        </div>
        <div class="main-list">
            <?php
            $this->widget('GridView', array(
                'id' => 'vtaskruns',
                'dataProvider' => $vTaskRunProvider,
                'htmlOptions' => array('class' => 'widget-view'),
                'rowCssClassExpression' => '$data->getStatusStyle()',
                'enablePageSize' => true,
                'selectionChanged' => 'js:function(id){
                        var selectedID = $.fn.yiiGridView.getSelection(id);
                        if(selectedID.toString().match(/\d+/))
                            location.href = getRootPath() + "/run/view/id/" + selectedID;
                    }',
                'afterAjaxUpdate' => 'js:function(){setListHeight();triggerPageSizeChange()}',
                'columns' => array(
                    array(
                        'name' => 'id',
                        'headerHtmlOptions' => array('class' => 'id'),
                        'htmlOptions' => array('class' => 'id'),
                    ),
                    array(
                        'name' => 'name',
                        'type' => 'raw',
                        'value' => 'CHtml::link(CHtml::encode(Yii::t("Run", "Run Result") . " " . $data->task_name), array("view", "id" => $data->id))',
                        'headerHtmlOptions' => array('class' => 'name'),
                        'htmlOptions' => array('class' => 'name'),
                    ),
//                    array(
//                        'name' => 'responsible_realname'
//                    ),
                    array(
                        'header' => Yii::t('Run', 'Run By'),
                        'name' => 'created_by_realname',
                    ),
                    array(
                        'name' => 'create_time',
                    ),
                    array(
                        'name' => 'project_name',
                    ),
                    array(
                        'name' => 'result',
                        'value' => '$data->getResultText()',
                        'cssClassExpression' => '$data->getResultStyle()'
                    ),
                    array(
                        'name' => 'status',
                        'value' => '$data->getStatusText()'
                    ),                    
                ),
            ));
            ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        productChange("/run/index/VTaskRun[product_id]/");
        getProjectTree("/run/index/VTaskRun[parent_id]/");
        setListHeight();

        $(window).resize(function(){
            setListHeight();
        })
        $("select.page-size").change(function(){
            var data = {'pagesize': $(this).val()};
            $.get(toast.setPageSize, data, function(){
                location.reload();
            });
        })
//        $("div#vtasks table.items tbody tr").click(function(){
//            var taskId = $(this).children("td:first").text();
//            location.href = getRootPath() + "/task/view/id/" + taskId;
//        });
    });
</script>