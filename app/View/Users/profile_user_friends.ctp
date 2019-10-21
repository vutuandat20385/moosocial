<?php

if (isset($profile_id)):?>
	<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery", 'mooUser', "mooBehavior"], function ($, mooUser, mooBehavior) {
        mooBehavior.initMoreResults();
        mooUser.initSearchFriend(<?php echo $profile_id?>);
    });
</script>
	<?php else: ?>
	<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooUser', 'mooBehavior'), 'object' => array('$', 'mooUser','mooBehavior'))); ?>
mooBehavior.initMoreResults();
mooUser.initSearchFriend(<?php echo $profile_id?>);
	<?php $this->Html->scriptEnd(); ?>
<?php endif;?>
<?php endif; ?>
<div class="bar-content profile-user-list">
    <div class="content_center">
	<?php if (isset($profile_id)):?>
        <h2 class="profile-friend"><?php echo __('Friends')?></h2>
        <input type="text" placeholder="<?php echo __('Search Friends')?>" id="search_friend"/>
	<?php endif;?>
        <ul class="users_list" id="list-content">
	        <?php echo $this->element('lists/users_list'); ?>
        </ul>
    </div>
</div>