<div class="content">
    <div class="sub-nav">
        <?php
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'sub-menu',
            'items' => array(
                array('label' => Yii::t('TOAST', 'Product Label'), 'url' => array('/admin/product'), 'active' => false),
                array('label' => Yii::t('TOAST', 'User Label'), 'url' => array('/admin/user'), 'active' => true),

            )
        ));
        ?>
        <div class="search">
            <?php
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array(
                    'action' => Yii::app()->getBaseUrl(true) . '/admin/#table#/index',
                    'cTable' => 'user',
                    'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
                    'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
                    'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
                    'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete',
                    'tables' => array(
                        'user' => array(
                            'label' => '用户',
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
                                'username' => array(
                                    'label' => '用户名',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有',
                                    ),
                                ),
                                'realname' => array(
                                    'label' => '显示名',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有',
                                    ),
                                ),
                                'role' => array(
                                    'label' => '角色',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                    'data' => User::model()->getRoleOptions()
                                ),
                                'status' => array(
                                    'label' => '状态',
                                    'type' => 'select',
                                    'operators' => array(
                                        '==' => '等于',
                                        '-=' => '不等于',
                                    ),
                                    'data' => User::model()->getStatusOptions()
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
            <span class="user-new icon-link"><?php echo CHtml::link(Yii::t("TOAST", "Create"), array('/admin/user/create')) ?></span>
        </div>
        <?php
        $this->widget('GridView', array(
            'id' => 'users',
            'dataProvider' => $userProvider,
            'htmlOptions' => array('class' => 'widget-view'),
            'rowCssClassExpression' => '$data->getStatusStyle()',
            'enablePageSize' => true,
            'selectionChanged' => 'js:function(id){
                        var selectedID = $.fn.yiiGridView.getSelection(id);
                        if(selectedID.toString().match(/\d+/))
                            location.href = getRootPath() + "/admin/user/view/id/" + selectedID;
                    }',
            'afterAjaxUpdate' => 'js:function(){setListHeight();triggerPageSizeChange()}',
            'columns' => array(
                array(
                    'name' => 'id',
                    'headerHtmlOptions' => array('class' => 'id'),
                    'htmlOptions' => array('class' => 'id'),
                ),
                array(
                    'name' => 'username',
                ),
                array(
                    'name' => 'realname',
                ),
                array(
                    'name' => 'role',
                    'value' => '$data->getRoleText()'
                ),
                array(
                    'name' => 'status',
                    'value' => '$data->getStatusText()'
                ),
                array(
                    'name' => 'update_time',
                ),
            ),
        ));
        ?>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        inputFocus();
        $("select.page-size").change(function(){
            var data = {'pagesize': $(this).val()};
            $.get(toast.setPageSize, data, function(){
                location.reload();
            });
        })
        setListHeight();
        $(window).resize(function(){
            setListHeight();
        })
        //    $("div#users table.items tbody tr").click(function(){
        //        var productId = $(this).children("td:first").text();
        //        location.href = getRootPath() + "/admin/user/view/id/" + productId;
        //    });
    });
</script>