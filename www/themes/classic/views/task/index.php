<div class="content">
    <div class="tree">
        <div class="sub-nav sub-nav-left">
            <div class="product-select">
                <?php
                echo CHtml::dropDownList('products', Yii::app()->user->currentProduct, Yii::app()->user->getProductOpts(), array('id' => 'products', 'options' => Yii::app()->user->getProductionOptsClass()));
                ?>
            </div>
        </div>
        <div id="project-tree">
        </div>
        <?php
//        $this->widget('CTreeView', array(
//            'id' => 'project-tree',
//            'data' => $projectTree,
//            'animated' => 'fast', //quick animation
//            'collapsed' => 'false', //remember must giving quote for boolean value in here
//            'htmlOptions' => array(
//                'class' => 'project-tree2', //there are some classes that ready to use
//            ),
//        ));
        ?>
    </div>
    <div class="layout-right">
        <div class="sub-nav sub-nav-right">
            <?php
            $actives = array(false, false, false);
            if(preg_match('#^\S*\@task\%20created\_by\_username\:\(\=\=\%7B' . Yii::app()->user->username . '\%7D\)$#', Yii::app()->request->requestUri))
            {
                $actives[1] = true;
            }
            else if(preg_match('#^\S*\@task\%20responsible_username\:\(\=\=\%7B' . Yii::app()->user->username . '\%7D\)$#', Yii::app()->request->requestUri))
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
                    array('label' => Yii::t('TOAST', 'All Task'), 'url' => array('/task'), 'active' => $actives[0]),
                    array('label' => Yii::t('TOAST', 'Created By Me'), 'url' => array('/task/index/q/@task created_by_username:(=={' . Yii::app()->user->username . '})'), 'active' => $actives[1]),
                    array('label' => Yii::t('TOAST', 'Responsible By Me'), 'url' => array('/task/index/q/@task responsible_username:(=={' . Yii::app()->user->username . '})'), 'active' => $actives[2]),
                    array('label' => Yii::t('TOAST', 'Recent Run'), 'url' => array('/run'), 'active' => false),
                )
            ));
            ?>
            <div class="search">
            <?php
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array(
                    'action' => Yii::app()->getBaseUrl(true) . '/#table#/index',
                    'cTable' => 'task',
                    'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
                    'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
                    'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
                    'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete',
                    'tables' => array(
                        'task' => array(
                            'label' => '任务',
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
                                    'label' => '任务名称',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有',
                                    ),
                                ),
                                'type' => array(
                                    'label' => '任务类型',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                    'data' => Task::model()->getTypeOptions()
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
                                    'label' => '模块路径',
                                    'type' => 'text',
                                    'operators' => array(
                                        'in' => '在某路径下',
                                        '==' => '等于',
                                        '-=' => '不等于',
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
                                'svn_url' => array(
                                    'label' => 'SVN监控',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有'
                                    )
                                ),
                                'created_by_username' => array(
                                    'label' => '创建者',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                        'tl' => 'TL等于',
                                    ),
                                    'data' => Yii::app()->user->getUsernameOpts()
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
                <span class="task-new icon-link"><?php echo CHtml::link(Yii::t("TOAST", "Create"), array('/task/create')) ?></span>
            </div>
            <?php
            $this->widget('GridView', array(
                'id' => 'vtasks',
                'dataProvider' => $vTaskProvider,
                'htmlOptions' => array('class' => 'widget-view'),
                'enablePageSize' => true,
                'selectionChanged' => 'js:function(id){
                                    var selectedID = $.fn.yiiGridView.getSelection(id);
                                    if(selectedID.toString().match(/\d+/))
                                        location.href = getRootPath() + "/task/view/id/" + selectedID;
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
                        'value' => '$data->getTypeText()',
                    ),
                    array(
                        'name' => 'project_name',
                    ),
                    array(
                        'name' => 'responsible_realname',
                    ),
                    array(
                        'name' => 'updated_by_realname',
                        'headerHtmlOptions' => array('class' => 'modify'),
                        'htmlOptions' => array('class' => 'modify'),
                        'value' => '$data->updated_by_realname . " " . $data->update_time',
                    ),
                ),
            ));
            ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        var p = /\/task\/([^/]*)/;
        var model = 'index';
        if(location.href.match(p)) {
            model = location.href.match(p)[1];
        }
        productChange("/task/" + model + "/VTask[product_id]/");
        getProjectTree("/task/" + model + "/VTask[parent_id]/");
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