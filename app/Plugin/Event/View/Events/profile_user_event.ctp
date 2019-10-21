
<div class="bar-content">
    <div class="content_center">
        <?php if ($user_id == $uid): ?>
            <div class="bar-content profile-sub-menu">
                <a href="<?php echo  $this->request->base ?>/events/create" class="topButton button button-action"><?php echo  __('Create New Event') ?></a>
            </div>
        <?php endif; ?>
        <ul class="list6 comment_wrapper list-mobile" id="list-content">
            <?php echo $this->element('lists/events_list'); ?>
        </ul>
    </div>
</div>