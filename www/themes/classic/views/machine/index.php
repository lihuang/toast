<div class="content">
    <div class="sub-nav">
        <div class="product-select">
            <?php
            echo CHtml::dropDownList('products', Yii::app()->user->currentProduct, Yii::app()->user->getProductOpts(), array('id' => 'products', 'options' => Yii::app()->user->getProductionOptsClass()));
            ?>
        </div>
        <?php
//    $items = array(array('label' => Yii::t('TOAST', 'Test Machine Label')));
//    $this->widget('zii.widgets.CMenu', array(
//        'id' => 'path-nav',
//        'items' => $items,
//        'firstItemCssClass' => 'first',
//        'lastItemCssClass' => 'last'
//    ));
        ?>    
        <?php
        $actives = array(false, false, false);
        if(preg_match('#^\S*\@machine\%20product\_id\:\(\)$#', Yii::app()->request->requestUri))
        {
            $actives[1] = true;
        }
        else if(preg_match('#^\S*\@machine\%20responsible_username\:\(\=\=\%7B' . Yii::app()->user->username . '\%7D\)$#', Yii::app()->request->requestUri))
        {
            $actives[2] = true;
        }
        else
        {
            $actives[0] = true;
        }        
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'sub-menu',
            'items' => array(
                array('label' => Yii::t('Machine', 'Assigned'), 'url' => array('/machine'), 'active' => $actives[0]),
                array('label' => Yii::t('Machine', 'Unassigned'), 'url' => array('/machine/index/q/@machine product_id:()'), 'active' => $actives[1]),
                array('label' => Yii::t('TOAST', 'Responsible By Me'), 'url' => array('/machine/index/q/@machine responsible_username:(=={' . Yii::app()->user->username . '})'), 'active' => $actives[2]),
//                array('label' => Yii::t('Reservation', 'Reservation Label'), 'url' => array('/reserve'), 'active' => false),
            )
        ));
        ?>
        <div class="search">
            <?php
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array(
                    'action' => Yii::app()->getBaseUrl(true) . '/#table#/index',
                    'cTable' => 'machine',
                    'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
                    'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
                    'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
                    'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete', 
                    'tables' => array(
                        'machine' => array(
                            'label' => '测试机',
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
                                    'label' => '主机域名',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有',
                                    ),
                                ),
                                'type' => array(
                                    'label' => '类型',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                    'data' => Machine::model()->getTypeOptions()
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
                                'project_name' => array(
                                    'label' => '所属项目',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有'
                                    )
                                ),
                                'agent_version' => array(
                                    'label' => 'Agent版本',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有'
                                    )
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
                                'status' => array(
                                    'label' => '状态',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于'
                                    ),
                                    'data' => Machine::model()->getStatusOptions()
                                ),
                                'update_time' => array(
                                    'label' => '最后更新时间',
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
                            ),
                        ),
                    ),
                ),
            ));
            ?>
        </div>
    </div>    
    <div class="main-list">
        <div class="link-bar">
            <span class="machine-new icon-link"><?php echo CHtml::link(Yii::t("TOAST", "Add"), 'javascript:void(0);', array('id' => 'add-machine')) ?></span>
        </div>
        <?php
        $this->widget('GridView', array(
            'id' => 'vMachines',
            'dataProvider' => $vMachineProvider,
            'htmlOptions' => array('class' => 'widget-view'),
            'enablePageSize' => true,
            'selectionChanged' => 'js:function(id){
                        var selectedID = $.fn.yiiGridView.getSelection(id);
                        if(selectedID.toString().match(/\d+/))
                            location.href = getRootPath() + "/machine/view/id/" + selectedID;
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
                    'value' => 'CHtml::link(CHtml::encode($data->name), array("view", "id" => $data->id))',
                    'headerHtmlOptions' => array('class' => 'name'),
                    'htmlOptions' => array('class' => 'name'),
                ),
                array(
                    'name' => 'type',
                    'value' => '$data->getTypeText()'
                ),
                array(
                    'name' => 'responsible_realname',
                ),
                array(
                    'name' => 'status',
                    'value' => '$data->getStatusText()',
                    'cssClassExpression' => '$data->getStatusStyle()'
                ),
                array(
                    'name' => 'agent_version'
                ),
                array(
                    'name' => 'update_time'
                ),
//                array(
//                    'name' => 'activity',
//                    'type' => 'image',
//                    'value' => '"' . Yii::app()->theme->baseUrl . '/assets/images/activity.png"',
//                    'headerHtmlOptions' => array('style' => 'width:100px'),
//                    'htmlOptions' => array('style' => 'width:100px; padding-bottom:0')
//                ),
            ),
        ));
        ?>
    </div>
</div>
<?php
$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
    'id' => 'dlg-add-machine',
    'theme' => 'base',
    'htmlOptions' => array('style' => 'display:none'),
    // additional javascript options for the dialog plugin
    'options' => array(
        'title' => Yii::t('TOAST', 'Create'),
        'autoOpen' => false,
        'modal' => true,
        'buttons' => array(
            Yii::t('TOAST', 'OK') => 'js:function(){ $(this).dialog("close");}',
        ),
        'width' => 700,
    ),
));
?>
<div class="block-title" style="margin: 10px 0 0"><?php echo Yii::t("Machine", "Install Step"); ?></div>
<div style="margin: 0 10px 20px">
    <p><?php echo Yii::t('Machine', 'Install Linux Agent'); ?></p>
</div>
<div class="block-title"><?php echo Yii::t("Machine", "Assign Step"); ?></div>
<div style="margin: 0 10px 20px">
    <p><?php echo Yii::t('Machine', 'Assign Step 1'); ?></p>
    <p><?php echo Yii::t('Machine', 'Assign Step 2'); ?></p>
    <p><?php echo Yii::t('Machine', 'Assign Step 3'); ?></p>
</div>
<?php $this->endWidget('zii.widgets.jui.CJuiDialog'); ?>
<script type="text/javascript">
    $(document).ready(function(){
        productChange("/machine/index/VMachine[product_id]/");
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
        $("#add-machine").click(function(){
            $("#dlg-add-machine").dialog("open");
        })
    });
</script>