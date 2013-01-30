<div class="content">
    <div class="sub-nav">
        <?php
        $items = array(array('label' => Yii::t('TOAST', 'Product Label'), 'url' => array('/admin/product')),
            array('label' => $product->name));
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
        <?php echo CHtml::errorSummary($product); ?>
        <div class="block detail clearfix basic-info">
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'name', array('class' => 'span2'));
                echo CHtml::activeTextField($product, 'name', array('class' => 'span10 focus'));
                echo CHtml::hiddenField('product-id', $product->id);
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'regress_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'regress_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <div class="row-fluid">
                <?php
                echo CHtml::activeLabelEx($product, 'unit_notice', array('class' => 'span2'));
                $this->widget('application.extensions.autocomplete.AutoCompleteWidget', array(
                    'model' => $product,
                    'attribute' => 'unit_notice',
                    'htmlOptions' => array(
                        'class' => 'focus span10',
                    ),
                    'urlOrData' => Yii::app()->createUrl('user/lookup'),
                    'config' => array(
                        'multiple' => true,
                        'matchCase' => false,
                        'cookieId' => 'task-responsible',
                        'formatResult' => 'js:function(result){return result[1];}',
                   ),
                ));
                ?>
            </div>
            <hr/>
            <div class="button-actions clearfix">
                <input type="submit" value="<?php echo Yii::t('TOAST', 'Save'); ?>" name="save" class="btn" />
            </div>
        </div>
        <?php echo CHtml::endForm(); ?>
        
        <div class="follow detail-info">
            <div class="detail-title"><?php echo Yii::t('TOAST', 'Project Label');?></div>
            <div class="notice text-center" style="margin-bottom: 10px"></div>
            <div class="row-fluid">
                <div class="span4">
                    <div id="tree" style="height: 300px; overflow: auto; width: 300px;" class="area-field">
                        <?php echo $product->getProjectTree(); ?>
                    </div>
                </div>
                <div class="span5">
                    <div class="row-fluid">
                        <div class="text-bold span12"><?php echo Yii::t('Project', 'Update Project'); ?></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span4">
                            <?php echo Yii::t('Product', 'Project ID'); ?>
                        </div>
                        <div class="span8" id="project-id">
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="span4">
                            <?php echo Yii::t('Project', 'Parent Project'); ?>
                        </div>
                        <div class="span8">
                            <?php 
                            echo CHtml::dropDownList('parent-id', null, Yii::app()->user->getProjectOpts($product->id, true), array('style' => 'height: 24px; ')); 
                            ?>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="span4">
                            <?php echo Yii::t('Project', 'Name'); ?>
                        </div>
                        <input type="text" id="project-name" class="focus span8"/>
                    </div>
                    <div class="row-fluid">
                        <?php
                        echo CHtml::button(Yii::t('Project', 'Update Project'), array('class' => 'btn', 'id' => 'update-project'));
                        echo CHtml::button(Yii::t('Project', 'Delete Project'), array('class' => 'btn', 'id' => 'delete-project'));
                        ?>
                    </div>
                    <hr/>
                    <div class="row-fluid">
                        <div class="text-bold span12"><?php echo Yii::t('Project', 'Create Project'); ?></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span4">
                            <?php echo Yii::t('Project', 'Parent Project'); ?>
                        </div>
                        <div class="span8">
                            <?php 
                            echo CHtml::dropDownList('new-parent-id', null, Yii::app()->user->getProjectOpts($product->id, true), array('style' => 'height: 24px; ')); 
                            ?>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="span4">
                            <?php echo Yii::t('Project', 'Name'); ?>
                        </div>
                        <input type="text" id="new-project-name" class="focus span8"/>
                    </div>
                    <div class="row-fluid">
                        <?php
                        echo CHtml::button(Yii::t('Project', 'Create Project'), array('class' => 'btn', 'id' => 'create-project'));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="follow detail-info">
            <div class="detail-title"><?php echo Yii::t('Product', 'Product User');?></div>
            <div class="notice-user success text-center" style="margin: 5px 10px;">
                <?php echo Yii::t('ProductUser', 'Set product user success'); ?>
            </div>
             <?php if(isset($permissionUserList) && count($permissionUserList)) { ?>
                <div id="main-list">
                <?php
                $pUser = array();
                foreach($permissionUserList as $permissionUser)
                {
                    $pUser[$permissionUser->id] = $permissionUser->username;
                }
                echo CHtml::dropDownList('', '', $pUser, array('id' => 'permissionuser', 'hidden' => 'hidden'));
                ?>
                    <div class="btn-actions clearfix">
                        <input type="button" value="<?php echo Yii::t('ProductUser', 'Permission All'); ?>" class="btn" onclick="onPerBtnClick(1)" />
                         <input type="button" value="<?php echo Yii::t('ProductUser', 'Ignore All'); ?>" class="btn" onclick="onPerBtnClick(0)" />
                    </div>
                    <table class="pending-users">
                        <thead>
                            <tr>
                                <th><?php echo Yii::t('User', 'User Name'); ?></th>
                                <th><?php echo Yii::t('User', 'Real Name'); ?></th>
                                <th><?php echo Yii::t('User', 'Email'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($permissionUserList as $pUser)
                            {
                                echo '<tr style="cursor:auto;">';
                                echo '<td>'. $pUser->username. '</td>';
                                echo '<td>'. $pUser->realname. '</td>';
                                echo '<td>'. $pUser->email. '</td>';
                                echo '<td> <input type="button" value="'. Yii::t('ProductUser', 'Confirm'). '" class="btn" onclick="onPerBtnClick(1,'.$pUser->id.')" />
                                    <input type="button" value="'. Yii::t('ProductUser', 'Ignore'). '" class="btn"  onclick="onPerBtnClick(0, '.$pUser->id.')"/>
                                    </td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <hr/>
            <?php } ?>
            
            <div class="row-fluid"  style="padding-left: 4.15%;">
                <div class="span3">
                    <div class="text-center">
                        <?php
                        echo Yii::t('ProductUser', 'Not Product User List');
                        ?>
                    </div>
                    <?php 
                    echo CHtml::dropDownList('', '', $allUserList, array(
                        'multiple' => 'multiple',
                        'id' => 'userout',
                        'class' => 'focus user-list',
                    ));
                    ?>
                </div>
                <div class="span1 btn-list">
                    <input type="button" class="btn" id="user-right" value="->">
                    <input type="button" class="btn" id="user-left" value ="<-">
                </div>
                <div class="span3">
                    <div class="text-center">
                        <?php
                        echo Yii::t('ProductUser', 'Product User List');
                        ?>
                    </div>
                    <?php 
                    echo CHtml::dropDownList('', '', $productUserList, array(
                        'multiple' => 'multiple',
                        'id' => 'userin',
                        'class' => 'focus user-list',
                    ));
                    ?>
                </div>
                <div class="span1 btn-list">
                    <input type="button" class="btn" id="admin-right" value="->">
                    <input type="button" class="btn" id="admin-left" value ="<-">
                </div>
                <div class="span3">
                    <div class="text-center">
                        <?php
                        echo Yii::t('ProductUser', 'Product Admin List');
                        ?>
                    </div>
                    <?php 
                    echo CHtml::dropDownList('', '', $productAdminList, array(
                        'multiple' => 'multiple',
                        'id' => 'useradmin',
                        'class' => 'focus user-list',
                    ));
                    ?>
                </div>
            </div>
            <hr/>
            <div class="button-actions clearfix">
                <input type="submit" value="<?php echo Yii::t('TOAST', 'Save'); ?>" class="save-product-user btn" />
            </div>
        </div>
    </div>
</div>
<style type="text/css">
select.user-list {
    width: 100%;
    margin-top : 5px;
    height: 400px;
}
div.btn-list {
    padding-top: 180px;
    text-align: center;
}
div.btn-list input {
    margin-bottom: 5px;
    width: 100%;
}
div.notice-user {
    display: none;
}
.basic-info {
    padding: 10px 15px;
}
.pending-users {
    margin-top: 10px;
    width: 100%;
}
.pending-users thead th{
    background: #F2F4F6;
    padding: 3px 2px;
}
.pending-users tbody td{
    padding: 3px 2px;
    text-align: center;
}
</style>
<script type="text/javascript">
function onPerBtnClick(operator, userid)
{
    var notproductuser = new Array();
    var productuser = new Array();
    var productadmin = new Array();

    if(operator == 1)
    {
        if(userid === undefined)
        {
            $('#permissionuser option').each(function() {
                    productuser.push($(this).val());
                });
        }
        else
        {
            productuser.push(userid);
        }
    }
    else
    {
        if(userid === undefined)
        {
            $('#permissionuser option').each(function() {
                    notproductuser.push($(this).val());
                });
        }
        else
        {
            notproductuser.push(userid);
        }
    }

    var data = {
        id : $("input#product-id").val(),
        notproductuser : notproductuser.join(),
        productuser : productuser.join(),
        productadmin : productadmin.join()
    }
    $.post(toast.setProductUser, data, function(){
        //$("div.notice").show();
        //setTimeout(function(){
            location.reload();
        //}, 3000);
    });
}    
$(document).ready(function(){
    toast.updateProject = getRootPath()  + "/admin/project/update";
    toast.deleteProject = getRootPath()  + "/admin/project/delete";
    toast.createProject = getRootPath()  + "/admin/project/create";
    
    $("#create-product").click(function(){
        location.href = getRootPath() + "/admin/product/create";
    })
    $("#tree").treeview({
        persist: "cookie"
    });
    $("#tree li a").click(function(){
        $("div#tree li a").css("font-weight", "normal");
        $(this).css("font-weight", "bold");
        $("select#parent-id").val($(this).attr("data-parent-id"));
        $("select#new-parent-id").val($(this).attr("data-project-id"));
        $("input#project-name").val($(this).text());
        $("div#project-id").text($(this).attr("data-project-id"));
    });
    
    $("#update-project").click(function(){
        var projectId = $("div#project-id").text();
        if(projectId) {
            var data = {
                id : projectId,
                "Project[parent_id]" : $("select#parent-id").val(),
                "Project[name]" : $("input#project-name").val()
            }
            $.get(toast.updateProject, data, function(json){
                if(json.status == "failed") {
                    $.each(json.errors, function(key, val) {
                        $("div.notice").text(" " +  val + " ");
                        $("div.notice").removeClass("success").addClass("failed");
                        $("div.notice").show();
                    })
                } else {
                    $("div.notice").text(" " +  lang.updateSuccess + " ");
                    $("div.notice").removeClass("failed").addClass("success");
                    $("select#parent-id").attr("disabled", "disabled");
                    $("input#project-name").attr("disabled", "disabled");
                    $("select#new-parent-id").attr("disabled", "disabled");
                    $("input#new-project-name").attr("disabled", "disabled");
                    $("input#update-project").attr("disabled", "disabled");
                    $("input#delete-project").attr("disabled", "disabled");
                    $("input#create-project").attr("disabled", "disabled");
                    $("div.notice").show();
                    setTimeout(function(){
                       location.reload();
                    }, 3000);
                }
            })
        }
    });
    
    $("#delete-project").click(function(){
        var projectId = $("div#project-id").text();
        if(projectId) {
            if(!confirm(lang.deleteConfirm)) {
                return;
            }
            var data = {
                id : projectId
            }
            $.get(toast.deleteProject, data, function(json){
                if(json.status == "failed") {
                    $.each(json.errors, function(key, val) {
                        $("div.notice").text(" " +  val + " ");
                        $("div.notice").removeClass("success").addClass("failed");
                        $("div.notice").show();
                    })
                } else {
                    $("div.notice").text(" " +  lang.deleteSuccess + " ");
                    $("div.notice").removeClass("failed").addClass("success");
                    $("select#parent-id").attr("disabled", "disabled");
                    $("input#project-name").attr("disabled", "disabled");
                    $("select#new-parent-id").attr("disabled", "disabled");
                    $("input#new-project-name").attr("disabled", "disabled");
                    $("input#update-project").attr("disabled", "disabled");
                    $("input#delete-project").attr("disabled", "disabled");
                    $("input#create-project").attr("disabled", "disabled");
                    $("div.notice").show();
                    setTimeout(function(){
                       location.reload();
                    }, 3000);
                }
            })
        }
    });
    
    $("#create-project").click(function(){
        var data = {
            "Project[product_id]" : $("#product-id").val(),
            "Project[parent_id]" : $("#new-parent-id").val(),
            "Project[name]" : $("#new-project-name").val()
        }
        $.get(toast.createProject, data, function(json){
            if(json.status == "failed") {
                $.each(json.errors, function(key, val) {
                    $("div.notice").text(" " +  val + " ");
                    $("div.notice").removeClass("success").addClass("failed");
                    $("div.notice").show();
                })
            } else {
                $("div.notice").text(" " +  lang.createSuccess + " ");
                $("div.notice").removeClass("failed").addClass("success");
                $("select#parent-id").attr("disabled", "disabled");
                $("input#project-name").attr("disabled", "disabled");
                $("select#new-parent-id").attr("disabled", "disabled");
                $("input#new-project-name").attr("disabled", "disabled");
                $("input#update-project").attr("disabled", "disabled");
                $("input#delete-project").attr("disabled", "disabled");
                $("input#create-project").attr("disabled", "disabled");
                $("div.notice").show();
                setTimeout(function(){
                    location.reload();
                }, 3000);
            }
        })
    });     

    toast.setProductUser = getRootPath() + "/admin/productUser/productSetUser";
    var user = new Array();
    $('#user-right').click(function() {
        $('#userout option').each(function() {
            if($(this).attr('selected')) {
                $('#userin').append($(this));
                user[$(this).val()] = 1;
            }
        });
    });
    $('#user-left').click(function() {
        $('#userin option').each(function() {
            if($(this).attr('selected')) {
                $('#userout').append($(this));
                user[$(this).val()] = 0;
            }
        });
    });
    $('#admin-right').click(function() {
        $('#userin option').each(function() {
            if($(this).attr('selected')) {
                $('#useradmin').append($(this));
                user[$(this).val()] = 2;
            }
        });
    });
    $('#admin-left').click(function() {
        $('#useradmin option').each(function() {
            if($(this).attr('selected')) {
                $('#userin').append($(this));
                user[$(this).val()] = 1;
            }
        });
    });
    $("input.save-product-user").click(function(){
        var notproductuser = new Array();
        var productuser = new Array();
        var productadmin = new Array();
        $.each(user, function(k, v){
            if(v == 0) {
                notproductuser.push(k);
            } else if(v == 1) {
                productuser.push(k);
            } else if(v ==2 ) {
                productadmin.push(k);
            }
        });
        var data = {
            id : $("input#product-id").val(),
            notproductuser : notproductuser.join(),
            productuser : productuser.join(),
            productadmin : productadmin.join()
        }
        $.post(toast.setProductUser, data, function(){
            $("div.notice-user").show();
            setTimeout(function(){
                location.reload();
            }, 3000);
        });
    })
});
</script>