<?php if($this->request->is('ajax')): ?>
<script>
    require(["jquery","mooUser"], function($,mooUser) {
        mooUser.initAjaxRequestPopup();
    });
</script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false,'requires'=>array('jquery','mooUser'),'object'=>array('$','mooUser'))); ?>
    mooUser.initAjaxRequestPopup();
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php $this->setCurrentStyle(4);?>

<div class="title-modal">
    <?php echo __('Friend Requests')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <ul class="list6 comment_wrapper" style="margin-top:0">
		<li id="request_<?php echo $request['FriendRequest']['id']?>">
				<div style="float:right">
					<a href="javascript:void(0)" data-id="<?php echo $request['FriendRequest']['id']?>" data-status="1" class="respondRequest btn btn-action"><?php echo __('Accept')?></a>
					<a href="javascript:void(0)" data-id="<?php echo $request['FriendRequest']['id']?>" data-status="0" class="respondRequest button "><?php echo __('Delete')?></a>
				</div>
				<?php echo $this->Moo->getItemPhoto(array('User' => $request['Sender']), array( 'prefix' => '100_square'), array('class' => 'img_wrapper2 user_avatar_large'))?>
				<div class="friend-request-info">
						<?php echo $this->Moo->getName($request['Sender'])?><br /><?php echo nl2br(h($request['FriendRequest']['message']))?><br />
						<span class="date"><?php echo $this->Moo->getTime( $request['FriendRequest']['created'], Configure::read('core.date_format'), $utz )?></span>
				</div>
		</li>
	</ul>
</div>