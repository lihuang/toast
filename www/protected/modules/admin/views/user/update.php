<div class="content">
    <div class="sub-nav">
        <?php
        $items = array();
        if(Yii::app()->user->isAdmin())
        {
            $items[] = array('label' => Yii::t('TOAST', 'User Label'), 'url' => array('/admin/user'));
        }
        $items[] = array('label' => $user->realname . '(' . $user->username . ')');
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
            <input type="button" value="<?php echo ($user->status == User::STATUS_AVAILABLE)?Yii::t('User', 'Disable'):Yii::t('User', 'Enable'); ?>" class="btn disable" />
            <input type="button" value="<?php echo Yii::t('TOAST', 'Return'); ?>" class="btn return" />
        </div>
        <?php echo CHtml::errorSummary($user); ?>
        <div class="block detail clearfix basic-info">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabel($user, 'username', array('class' => 'span1'));
                echo CHtml::tag('div', array('class' => 'span11'), CHtml::encode($user->username));
                echo CHtml::activeHiddenField($user, 'id');
                echo CHtml::activeHiddenField($user, 'status');
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'realname', array('class' => 'span1'));
                echo CHtml::activeTextField($user, 'realname', array('class' => 'span3 focus'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'email', array('class' => 'span1'));
                echo CHtml::activeTextField($user, 'email', array('class' => 'span3 focus'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'pinyin', array('class' => 'span1'));
                echo CHtml::activeTextField($user, 'pinyin', array('class' => 'span3 focus'));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'abbreviation', array('class' => 'span1'));
                echo CHtml::activeTextField($user, 'abbreviation', array('class' => 'span3 focus'));
                ?>
            </div>
            <?php if(Yii::app()->user->isAdmin()) {?>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($user, 'role', array('class' => 'span1'));
                echo '<div class="span3">';
                echo CHtml::activeRadioButtonList($user, 'role', $user->getRoleOptions(), array('separator' => '&nbsp;&nbsp;',));
                echo '</div>';
                ?>
            </div>
            <?php }?>
        </div>
        <div class="follow detail-info">
            <div class="detail-title"><?php echo Yii::t('User', 'API Authentication Key');?></div>
            <div class="clearfix">
                <?php echo CHtml::activeTextField($user, 'token', array('class' => 'focus', 'style' => ' width: 250px; float: left; margin-right: 10px', 'readonly' => TRUE)); ?>
                <input type="button" value="<?php echo Yii::t('User', 'Generate'); ?>" class="btn generate" />
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
    window.onbeforeunload = function () {
        if(!goodexit) {
            return lang.sureToLeave;
        }
    }
    $("input[type=submit]").click(function(){
        goodexit = true;
    })
    
    $("input.disable").click(function(){
        var id = $("#User_id").val();
        var status = $("#User_status").val();
        if(status == "0" || confirm(lang.confirmDisableUser))
        {
            $.getJSON(getRootPath() + "/admin/user/disable", {id:id, disable:status}, function(json){
                if(json.code != 0)
                    alert(json.msg);
                else
                {
                    goodexit = true;
                    location.reload();
                }
            })
        }
    })
    $("input.return").click(function(){
        history.back();
    })
    
    $("#User_token").mouseover(function(){
        $(this).select();
    })
    $("input.generate").click(function(){
        if(confirm(lang.confirmRegenerateToken))
        {
            var id = $("#User_id").val();
            $.getJSON(getRootPath() + "/admin/user/gettoken", {id:id}, function(json){
                if(json.code == 0)
                    $("#User_token").val(json.token);
                else
                    alert(json.msg);
            })
        }
    })
});
</script>