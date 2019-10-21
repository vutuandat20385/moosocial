<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery",'mooUser',"mooBehavior"], function($, mooUser ,mooBehavior) {
        mooBehavior.initMoreResults();
        mooUser.initSearchFriend(0);
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooUser', 'mooBehavior'), 'object' => array('$', 'mooUser','mooBehavior'))); ?>
mooBehavior.initMoreResults();
mooUser.initSearchFriend(0);
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>

<style>
#list-content li {
    position: relative;
}
</style>
<div class="content_center_home">
	<?php if (Configure::read("core.allow_invite_friend")):?>
    	<a href="<?php echo $this->request->base?>/home/index/tab:invite-friends" class="topButton button button-action"><?php echo __('Invite Friends')?></a>
    <?php endif;?>
    <h1><?php echo __('Friends')?></h1>
    <input type="text" placeholder="<?php echo __('Search Friends')?>" id="search_friend"/>
    <ul class="users_list" id="list-content">
        <?php echo $this->element( 'lists/users_list' ); ?>
    </ul> 
</div>