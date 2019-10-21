<div class="title-modal">
    <?php echo __( 'Friend Added')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
<ul class="activity_content activity_friend_add">
<?php if (!count($users)):?>
	<?php echo __('No more results found');?>
<?php else:?>
	<?php foreach ( $users as $user ): ?>
	    <li class="user-list-index"><?php echo $this->element('user/item', array('user' => $user)); ?></li>
	<?php endforeach; ?>
<?php endif;?>
</ul>
</div>
