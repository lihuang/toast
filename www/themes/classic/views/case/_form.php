<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->theme->baseUrl; ?>/assets/css/case.css" />
<div class="content">
    <div class="sub-nav">
    <?php
    $currentProductId =  isset($vTestCase) ? $vTestCase->product_id : Yii::app()->user->getCurrentProduct();
    $currentProductName = isset($vTestCase) 
                                        ? $vTestCase->product_name 
                                        : Yii::app()->user->getCurrentProduct(true)->name;
    $this->widget('zii.widgets.CMenu', array(
        'id' => 'path-nav',
        'items' => $testCase->getNavItems(),
        'firstItemCssClass' => 'first',
        'lastItemCssClass' => 'last'
    ));
    ?>
    </div>
    <div class="main-detail">
        <?php echo CHtml::beginForm(); ?>
        <div class="button-actions clearfix">
            <?php echo CHtml::submitButton(Yii::t('TestCase', 'Save'), array('class' => 'btn')); ?>
        </div>
        <?php echo CHtml::errorSummary($testCase); ?>
        <div class="detail block basic-info clearfix">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($testCase, 'name', array('class' => 'span1'));
                echo CHtml::activeTextField($testCase, 'name', array('class' => 'span11 focus'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($testCase, 'framework', array('class' => 'span1'));
                echo '<div class="span2">';
                echo CHtml::activeDropDownList($testCase, 'framework', $testCase->getFrameworkOpts());
                echo '</div>';
                echo CHtml::activeLabelEx($testCase, 'project_id', array('class' => 'offset1 span1'));
                $this->widget('application.extensions.masspicker.MassPickerWidget', array(
                    'model' => $testCase,
                    'attribute' => 'project_id',
                    'value' => empty($testCase->project) ? '' : $testCase->project->path,
                    'options' => array(
                        'click' => 'js:function(){
                            data = {productid: ' . $currentProductId . '};
                            $.get(toast.getProjectTree, data, function(html){
                                $("#dlg-find-project").html(html);
                                $("#dlg-find-project").treeview({
                                    persist: "cookie",
                                    collapsed: true
                                });
                                $("#dlg-find-project a").click(function(){
                                    var projectId = $(this).attr("data-project-id");
                                    var projectPath = $(this).attr("data-project-path");
                                    $("#TestCase_project_id").val(projectId);
                                    $("#TestCase_project_id-input").val(projectPath);
                                    $("#dlg-find-project").dialog("close");
                                })
                            })
                            $("#dlg-find-project").dialog("open");
                        }'
                    ),
                    'htmlOptions' => array('class' => 'focus span7',
                        'placeholder' => '/' . $currentProductName ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($testCase, 'code_url', array('class' => 'span1'));
                echo CHtml::activeTextField($testCase, 'code_url', array('class' => 'span6  focus'));
                echo CHtml::activeLabelEx($testCase, 'func_name', array('class' => 'offset1 span1'));
                echo CHtml::activeTextField($testCase, 'func_name', array('class' => 'span3 focus'));
                ?>
            </div>
        </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
<?php
$this->beginWidget('zii.widgets.jui.CJuiDialog', array(
    'id' => 'dlg-find-project',
    'theme' => 'base',
    'htmlOptions' => array('style' => 'display:none'),
    'options' => array(
        'title' => $currentProductName,
        'autoOpen' => false,
        'resizable' => false,
        'modal' => true,
    ),
));
echo '<div id="find-project"></div>';
$this->endWidget('zii.widgets.jui.CJuiDialog');
?>