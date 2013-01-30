<div class="content">
    <div class="sub-nav">
        <?php
        $items = array(array('label' => Yii::t('TOAST', 'User Label'), 'url' => array('/admin/user')),
            array('label' => $user->username));
        $this->widget('zii.widgets.CMenu', array(
            'id' => 'path-nav',
            'items' => $items,
            'firstItemCssClass' => 'first',
            'lastItemCssClass' => 'last'
            ));
        ?>
    </div>
    <div class="main-detail">        
        <div class="button-actions clearfix">
            <input type="button" value="<?php echo Yii::t("User", "Update"); ?>" class="btn update-user" />
        </div>
        <div class="block detail clearfix basic-info">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'username', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->username);
                echo CHtml::hiddenField('user-id', $user->id);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'realname', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->realname);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'pinyin', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->pinyin);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'abbreviation', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->abbreviation);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'email', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->email);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'role', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->getRoleText());
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'status', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->getStatusText());
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'create_time', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->create_time);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'update_time', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span10'), $user->update_time);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::label(Yii::t('User', 'Products'), 'user-products', array('class' => 'span1'));
                echo '<div class="span10">';
                foreach($products as $product)
                {
                        echo $product->product_name . ' ';
                }
                echo '</div>';
                ?>
            </div>
        </div>
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
    inputFocus();
    $("input#create-user").click(function(){
        location.href = getRootPath() + "/admin/user/create";
    });
    $("input.update-user").click(function(){
        var userId = $("#user-id").val();
        location.href = getRootPath() + "/admin/user/update/id/" + userId;
    });
});
</script>