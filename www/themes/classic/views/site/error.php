<div class="content">
    <div class="" style="padding: 20px 40px">
        <h2 style="font-size: 20px"><?php echo Yii::t('TOAST', 'Error Label'); ?></h2>
        <hr />
        <div id="message-box"><span id="message"><?php echo $error['message']; ?></span></div>
        <br />
        <div>
            <a href="<?php echo Yii::app()->request->getBaseUrl(true); ?>">
                <?php echo Yii::t('TOAST', 'Back to index'); ?>
            </a>
        </div>
    </div>
</div>