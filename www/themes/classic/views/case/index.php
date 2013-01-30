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
                'items' => $vTestCase->getListMenuItems()
            ));
            ?>
            <div class="search">
                <?php
                $this->Widget('application.extensions.querybuilder.QueryBuilderWidget', array(
                    'name' => 'search',
                    'options' => $vTestCase->getQueryOpts()
                ));
                ?>
            </div>
        </div>
        <div class="main-list">
            <div class="link-bar">
                <span class="twf-new icon-link">
                    <?php echo CHtml::link(Yii::t("TestCase", "Create"), array('/case/create'))?>
                </span>
            </div>
             <?php
            $this->widget('GridView', array(
                'id' => 'vSuite',
                'dataProvider' => $vTestCaseProvider,
                'htmlOptions' => array('class' => 'widget-view'),
                'enablePageSize' => true,
                'selectionChanged' => 'js:function(id){
                    var selectedID = $.fn.yiiGridView.getSelection(id);
                    if(selectedID.toString().match(/\d+/))
                        location.href = getRootPath() + "/case/view/id/" + selectedID;
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
                        'name' => 'project_name',
                        'type' => 'raw',
                        'value' => 'CHtml::link($data->project_name, array("index", "VTestCase[project_id]" => $data->project_id), array("style" => "color: #000000;"))',
                    ),
                    array(
                        'name' => 'created_by_realname',
                        'type' => 'raw',
                        'value' => 'CHtml::link($data->created_by_realname, array("index", "VTestCase[created_by]" => $data->created_by), array("style" => "color: #000000;"))',
                    ),
                    array(
                        'name' => 'updated_by_realname',
                        'type' => 'raw',
                        'value' => 'CHtml::link($data->updated_by_realname, array("index", "VTestCase[updated_by]" => $data->updated_by), array("style" => "color: #000000;"))',
                    ),
                    array(
                        'name' => 'create_time',
                        'headerHtmlOptions' => array('style' => 'width: 80px'),
                        'htmlOptions' => array('style' => 'width: 80px'),
                        'value' => 'TString::covertTime2Date($data->update_time)',
                    ),
                    array(
                        'name' => 'update_time',
                        'headerHtmlOptions' => array('style' => 'width: 80px'),
                        'htmlOptions' => array('style' => 'width: 80px'),
                        'value' => 'TString::covertTime2Date($data->update_time)',
                    ),
                ),
            ));
            ?>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function(){
    var p = /\/case\/([^/]*)/;
    var model = 'index';
    if(location.href.match(p)) {
        model = location.href.match(p)[1];
    }
    productChange("/case/" + model + "/VTestCase[product_id]/");
    getProjectTree("/case/" + model + "/VTestCase[parent_id]/");
    setListHeight();
})
</script>