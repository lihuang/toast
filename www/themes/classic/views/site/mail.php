<layout label="Text with left-aligned image">
    <table class="w580" width="580" cellpadding="0" cellspacing="0" border="0">
        <tbody>
            <tr>
                <td class="w580" width="580">
                    <table cellpadding="0" cellspacing="0" border="0" align="left">
                        <tbody>
                            <tr>
                                <td><img src="http://www.gravatar.com/avatar/<?php echo md5($user->email); ?>?s=40" /></td>
                                <td class="w30" width="15"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="w30" width="10" height="5"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div align="left" class="article-content" style="line-height: 40px; font-size: 18px;">
                        <?php
                        echo Yii::t('ProductUser',  '{realname} ({username}) apply product {product} access',  array(
                            '{realname}' => $user->realname,
                            '{username}' => $user->username,
                            '{product}' => $product->name,
                        ));
                        ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="w580" width="580">
                    <div align="left" class="article-content">
                        <?php
                        echo Yii::t('ProductUser',  '{verfiy the applicaion at {link}',  array(
                            '{link}' => CHtml::link(Yii::app()->request->getBaseUrl(true) . '/admin/product/update/id/' . $product->id, 
                                    Yii::app()->request->getBaseUrl(true) . '/admin/product/update/id/' . $product->id),
                        ));
                        ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</layout>