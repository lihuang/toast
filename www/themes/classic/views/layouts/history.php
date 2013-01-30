<?php
$this->widget('zii.widgets.CListView', array(
    'dataProvider'=>$history,
    'ajaxUpdate'=>true,
    'template'=>'{items}{pager}',
    'itemView'=>'history',
    'id' => 'history0',
    'pager'=>array(
        'class' => 'LinkPager',
        'summary' => true,
        'maxButtonCount' => 5,
//      'alwayShow' => true,
    ),
));
?>

<script type="text/javascript">
$(document).ready(function(){
    $(".expand-toggle").click(function(event){
        if($(this).parent().next().find("div:first").css("white-space") == "nowrap")
        {
            $(this).parent().next().find("div").css("white-space", "normal")
            $(this).text("<?php echo Yii::t('Diff', 'Collapse'); ?>");
        }
        else
        {
            $(this).parent().next().find("div").css("white-space", "nowrap")
            $(this).text("<?php echo Yii::t('Diff', 'Expand'); ?>");
        }
        event.stopImmediatePropagation();
    })
})
</script>