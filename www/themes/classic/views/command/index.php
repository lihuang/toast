    <div class="content">
    <div class="sub-nav">
        <?php
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'sub-menu',
            'items' => array(
                array('label' => Yii::t('TOAST', 'All Task'), 'url' => array('/task'), 'active' => false),
                array('label' => Yii::t('TOAST', 'Created By Me'), 'url' => array('/task/index/q/@task created_by_username:(=={' . Yii::app()->user->username . '})'), 'active' => false),
                array('label' => Yii::t('TOAST', 'Responsible By Me'), 'url' => array('/task/index/q/@task responsible_username:(=={' . Yii::app()->user->username . '})'), 'active' => false),
                array('label' => Yii::t('TOAST', 'Recent Run'), 'url' => array('/run'), 'active' => false),
                array('label' => Yii::t('Task', 'Task Commands'), 'url' => array('/command'), 'active' => true),
            )
        ));
        ?>
        <div class="search">
            <?php
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array(
                    'action' => Yii::app()->getBaseUrl(true) . '/#table#/index',
                    'cTable' => 'command',
                    'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
                    'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
                    'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
                    'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete',
                    'tables' => array(
                        'command' => array(
                            'label' => '命令集',
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
                                    'label' => '名称',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有',
                                    ),
                                ),
//                                'parser_id' => array(
//                                    'label' => '解析方式',
//                                    'type' => 'select',
//                                    'operators' => array(
//                                        '==' => '等于',
//                                        '-=' => '不等于',
//                                    ),
//                                    'data' => Parser::model()->getParserOptions()
//                                ),
                                'status' => array(
                                    'label' => '状态',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于'
                                    ),
                                    'data' => array(
                                        Command::STATUS_AVAILABLE => Yii::t('Command', 'Status Available'),
                                        Command::STATUS_PUBLISH => Yii::t('Command', 'Status Publish'),
                                    )
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
                                'updated_by_username' => array(
                                    'label' => '修改者',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                        'tl' => 'TL等于',
                                    ),
                                    'data' => Yii::app()->user->getUsernameOpts()
                                ),
                                'update_time' => array(
                                    'label' => '修改时间',
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
            <span class="command-new icon-link"><?php echo CHtml::link(Yii::t("TOAST", "Create"), array('/command/create'))?></span>
        </div>
        <?php
            $this->widget('GridView', array(
                'id' => 'vCommand',
                'dataProvider' => $vCommandProvider,
                'htmlOptions' => array('class' => 'widget-view'),
                'enablePageSize' => true,
                'selectionChanged' => 'js:function(id){
                        var selectedID = $.fn.yiiGridView.getSelection(id);
                        if(selectedID.toString().match(/\d+/))
                            location.href = getRootPath() + "/command/view/id/" + selectedID;
                    }',
                'afterAjaxUpdate' => 'js:function(){setListHeight();triggerPageSizeChange()}',
                'columns' => array(
                    array(
                        'name' => 'id',
                        'headerHtmlOptions' => array('class' => 'id'),
                        'htmlOptions' => array('class' => 'id')
                    ),
                    array(
                        'name' => 'name',
                        'type' => 'raw',
                        'value' => 'CHtml::link(CHtml::encode($data->name), array("view", "id" => $data->id))',
                        'headerHtmlOptions' => array('class' => 'name'),
                        'htmlOptions' => array('class' => 'name')
                    ),
                    array(
                        'name' => 'parser_id',
                        'value' => '$data->getParsers(false)'
                    ),
                    array(
                        'name' => 'created_by_realname',
                        'value' => '$data->created_by_realname . " " . $data->create_time' 
                    ),
                    array(
                        'name' => 'updated_by_realname',
                        'value' => '$data->updated_by_realname . " " . $data->update_time'
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
<script type="text/javascript">
$(document).ready(function(){
    inputFocus();
    setListHeight();
    triggerPageSizeChange();
    
    $(window).resize(function(){
        setListHeight();
    })
//    $("select.page-size").change(function(){
//        var data = {'pagesize': $(this).val()};
//        $.get(toast.setPageSize, data, function(){
//            location.reload();
//        });
//    })
//    $("div#vCommand table.items tbody tr").click(function(){
//        var commandId = $(this).children("td:first").text();
//        location.href = getRootPath() + "/command/view/id/" + commandId;
//    });
    $("input#create-command").click(function(){
        window.open(getRootPath() + "/command/create", "", "width=1100, height=400, top=100, left= 100, resizable=no");
    })
});
</script>