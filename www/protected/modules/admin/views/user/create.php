<div class="content">
    <div class="sub-nav">
        <?php
        $items = array(array('label' => Yii::t('TOAST', 'User Label'), 'url' => array('/admin/user')),
            array('label' => Yii::t('User', 'New User')));
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'path-nav',
            'items' => $items,
            'firstItemCssClass' => 'first',
            'lastItemCssClass' => 'last'
            ));
        ?>
    </div>
    <div class="main-detail">        
        <?php echo CHtml::beginForm(); ?>
        <div class="button-actions clearfix">
            <input type="submit" value="<?php echo Yii::t('TOAST', 'Save'); ?>" name="save" class="btn" />
        </div>
        <?php echo CHtml::errorSummary($user); ?>
        <div class="block detail clearfix basic-info">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'username', array('class' => 'span1'));
                echo CHtml::activeTextField($user, 'username', array('class' => 'focus span3'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'realname', array('class' => 'span1'));
                echo CHtml::activeTextField($user, 'realname', array('class' => 'focus span3'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'password', array('class' => 'span1'));
                echo CHtml::activePasswordField($user, 'password', array('class' => 'focus span3'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'email', array('class' => 'span1'));
                echo CHtml::activeTextField($user, 'email', array('class' => 'focus span3'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'role', array('class' => 'span1'));
                echo '<div class="span3">';
                echo $user->getRoleFormField();
                echo '</div>';
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::label(Yii::t('User', 'Products'), 'user-products', array('class' => 'span1'));
                echo '<div class="span11">';
                echo CHtml::checkBoxList('products', '', Yii::app()->user->getAdminProductOpts(), 
                        array('style' => 'width: auto;', 'separator' => '&nbsp;&nbsp;&nbsp;'));
                echo '</div>';
                ?>
            </div>
        </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
<style type="text/css">
.basic-info {
    padding: 10px 15px;
}
.basic-info label {
    color: #919191;
}
</style>
<script type="text/javascript">
$(document).ready(function(){
    var goodexit = false;
    function bindunbeforunload() {
        goodexit = false;
        window.onbeforeunload = perforresult;
    }
    function unbindunbeforunload() {
        goodexit = true;
        window.onbeforeunload = null;
    }
    function perforresult() {
        if(!goodexit) {
            return lang.sureToLeave;
        }
    }
    bindunbeforunload();
    $("input[type=submit]").click(function(){
        unbindunbeforunload();
    })
    $("input#create-user").click(function(){
        location.href = getRootPath() + "/admin/user/create";
    })
});
</script>