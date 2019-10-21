
<div class="bar-content">
    <div class="content_center">
        <?php if ($user_id == $uid): ?>
            <div class="bar-content profile-sub-menu">
                <a href="<?php echo  $this->request->base ?>/topics/create" class="topButton button button-action"><?php echo  __('Create New Topic') ?></a>    
            </div>
        <?php endif; ?>
        <ul class="list6 comment_wrapper" id="list-content">
            <?php echo $this->element('lists/topics_list'); ?>
        </ul>
    </div>
</div>