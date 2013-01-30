<div class="content">
    <div class="sub-nav">
        <?php
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'sub-menu',
            'items' => array(
                array('label' => Yii::t('TOAST', 'Product Label'), 'url' => array('/admin/product'), 'active' => true),
                array('label' => Yii::t('TOAST', 'User Label'), 'url' => array('/admin/user'), 'active' => false),
            )
        ));
        ?>
        <div class="search">
            <?php
            $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                'name' => 'search',
                'options' => array(
                    'action' => Yii::app()->getBaseUrl(true) . '/admin/#table#/index',
                    'cTable' => 'product',
                    'queryListUrl' => Yii::app()->getBaseUrl(true) . '/query/getlist',
                    'createQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/create',
                    'updateQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/update',
                    'deleteQueryUrl' => Yii::app()->getBaseUrl(true) . '/query/delete',
                    'tables' => array(
                        'product' => array(
                            'label' => '产品',
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
                                    'label' => '产品名称',
                                    'type' => 'text',
                                    'operators' => array(
                                        '' => '含有',
                                        '-' => '不含有',
                                    ),
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
            <span class="product-new icon-link"><?php echo CHtml::link(Yii::t("TOAST", "Create"), array('/admin/product/create')) ?></span>
        </div>
        <?php
        $this->widget('GridView', array(
            'id' => 'vproducts',
            'dataProvider' => $vProductProvider,
            'htmlOptions' => array('class' => 'widget-view'),
            'rowCssClassExpression' => '$data->getStatusStyle()',
            'enablePageSize' => true,
            'selectionChanged' => 'js:function(id){
                        var selectedID = $.fn.yiiGridView.getSelection(id);
                        if(selectedID.toString().match(/\d+/))
                            location.href = getRootPath() + "/admin/product/update/id/" + selectedID;
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
                    'headerHtmlOptions' => array('class' => 'name'),
                    'htmlOptions' => array('class' => 'name'),
                ),
                array(
                    'name' => 'created_by_realname',
                ),
                array(
                    'name' => 'updated_by_realname',
                ),
                array(
                    'name' => 'create_time',
                ),
                array(
                    'name' => 'update_time',
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
        //    $("div#vproducts table.items tbody tr").click(function(){
        //        var productId = $(this).children("td:first").text();
        //        location.href = getRootPath() + "/admin/product/update/id/" + productId;
        //    });
    });
</script>