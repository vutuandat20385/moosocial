<?php 
	$block_users = array();
	if (!empty($uid))
	{
		$model = MooCore::getInstance()->getModel('UserBlock');
		$block_users = $model->getBlockedUsers($uid);
	}
?>
<div class="user_mini">
	<?php echo $this->Moo->getItemPhoto(array('User' => $user),array( 'prefix' => '100_square'), array('class' => 'user_avatar_large img_wrapper2'))?>
	<div class="user-info">
		<?php echo $this->Moo->getName($user)?>
		<div>
			<span class="extra_info">
				<?php echo __n( '%s friend', '%s friends', $user['friend_count'], $user['friend_count'] )?> .
				<?php echo __n( '%s photo', '%s photos', $user['photo_count'], $user['photo_count'] )?>
			</span><br />
			<?php if ( !empty($uid) && $uid != $user['id'] && !$areFriends && !in_array($user['id'], $block_users)): ?>
                        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "friends",
                                            "action" => "ajax_add",
                                            "plugin" => false,
                                            $user['id']
                                            
                                        )),
             'title' => sprintf( __('Send %s a friend request'), $user['name'] ),
             'innerHtml'=> __('Add as Friend'),
          'id' => 'addFriend_' . $user['id']
     ));
 ?>
			<br />
			<?php endif; ?>
		</div>
	</div>
</div>