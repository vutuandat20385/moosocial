<?php
if (count($users) > 0)
{
	foreach ($users as $user):
?>
	<li <?php if ( isset($type) && $type == 'home' ): ?>id="friend_<?php echo $user['Friend']['friend_id']?>"<?php endif; ?>
		<?php if ( isset($group) ): ?>id="member_<?php echo $user['GroupUser']['id']?>"<?php endif; ?>
        class="user-list-index">
            <div class="list-content">
                <div class="user-idx-item">
                   <a href="<?php echo $this->request->base?>/<?php echo (!empty( $user['User']['username'] )) ? '-' . $user['User']['username'] : 'users/view/'.$user['User']['id']?>"><?php echo $this->Moo->getItemPhoto(array('User' => $user['User']), array('prefix' => '200_square'))?></a>
                    <?php if ( isset($friends_request) && in_array($user['User']['id'], $friends_request) && $user['User']['id'] != $uid): ?>
                        <a href="<?php echo $this->request->base?>/friends/ajax_cancel/<?php echo $user['User']['id']?>" id="cancelFriend_<?php echo $user['User']['id']?>" class="add_people" title="<?php __('Cancel a friend request');?>">
                            <i class="material-icons">clear</i> <?php echo __('Cancel Request')?>
                        </a>
                    <?php elseif ( !empty($respond) && in_array($user['User']['id'], $respond ) && $user['User']['id'] != $uid): ?>
                        <div class="dropdown" style="" >
                            <a href="#" id="respond" data-target="#" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false" class="add_people" title="<?php __('Respond to Friend Request');?>">
                                <i class="material-icons">person_add</i> <?php echo __('Respond to Friend Request')?>
                            </a>

                            <ul class="dropdown-menu" role="menu" aria-labelledby="respond">
                                <li><a class="respondRequest" data-id="<?php echo  $request_id[$user['User']['id']]; ?>" data-status="1" href="javascript:void(0)"><?php echo  __('Accept'); ?></a></li>
                                <li><a class="respondRequest" data-id="<?php echo  $request_id[$user['User']['id']]; ?>" data-status="0" href="javascript:void(0)"><?php echo  __('Delete'); ?></a></li>
                            </ul>
                        </div>
                        
                    <?php elseif (isset($friends) && in_array($user['User']['id'], $friends) && $user['User']['id'] != $uid): ?>
                        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "friends",
                                            "action" => "ajax_remove",
                                            "plugin" => false,
                                            $user['User']['id']
                                        )),
             'title' => __('Remove'),
             'innerHtml'=> '<i class="material-icons">clear</i> ' . __('Remove Friend'),
          'id' => 'removeFriend_'.$user['User']['id'],
          'class' => 'add_people'
     ));
 ?>
                        
                    <?php elseif(isset($friends) && isset($friends_request) && !in_array($user['User']['id'], $friends) && !in_array($user['User']['id'], $friends_request) && $user['User']['id'] != $uid): ?>
                        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "friends",
                                            "action" => "ajax_add",
                                            "plugin" => false,
                                            $user['User']['id']
                                        )),
             'title' => sprintf( __('Send %s a friend request'), $user['User']['name'] ),
             'innerHtml'=> '<i class="material-icons">person_add</i>&nbsp;'. __('Add Friend'),
          'id' => 'addFriend_'. $user['User']['id'],
          'class'=> 'add_people'
     ));
 ?>
                       
                    <?php endif; ?>
                   
                   <?php
                   if(isset($user_block)){
                        $this->MooPopup->tag(array(
                               'href'=>$this->Html->url(array("controller" => "user_blocks",
                                                              "action" => "ajax_remove",
                                                              "plugin" => false,
                                                              $user['User']['id']
                                                          )),
                               'title' => __('Unblock'),
                               'innerHtml'=> '<i class="material-icons icon-large delete-icon">clear</i> ' . __('Unblock'),
                            'id' => 'unblock_'.$user['User']['id'],
                            'class' => 'add_people unblock'
                       ));
                   }
                ?>

                </div>

		<?php if ( isset($type) && $type == 'home' ): ?>
                <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "friends",
                                            "action" => "ajax_remove",
                                            "plugin" => false,
                                            $user['User']['id']
                                        )),
             'title' => '',
             'innerHtml'=> '<i class="material-icons icon-large delete-icon">clear</i>',
          'id' => 'removeFriend_'. $user['User']['id']
     ));
 ?>
                    
		<?php endif; ?>

		<div class="user-list-info">
                        <div class="user-name-info">
			<?php echo $this->Moo->getName($user['User'])?>
                        </div>
			<div class="">
				<span class="date">
					<?php echo __n( '%s friend', '%s friends', $user['User']['friend_count'], $user['User']['friend_count'] )?> .
					<?php echo __n( '%s photo', '%s photos', $user['User']['photo_count'], $user['User']['photo_count'] )?><br />

					<?php if ( isset($group) && isset($admins) && $user['User']['id'] != $uid && $group['User']['id'] != $user['User']['id'] &&
							   ( !empty($cuser['Role']['is_admin']) || in_array($uid, $admins) ) ):
					?>
					<a href="javascript:void(0)" class="removeMember" data-id="<?php echo $user['GroupUser']['id']?>"><?php echo __('Remove Member')?></a> .
					<?php endif; ?>

					<?php if ( isset($group) && isset($admins) && !in_array($user['User']['id'], $admins) &&
							   ( !empty($cuser['Role']['is_admin']) || $uid == $group['User']['id'] ) ):
					?>
					<a href="javascript:void(0)" class="changeAdmin" data-id="<?php echo $user['GroupUser']['id']?>" data-type="make"><?php echo __('Make Admin')?></a>
					<?php endif; ?>

					<?php if ( isset($group) && isset($admins) && in_array($user['User']['id'], $admins) && $user['User']['id'] != $group['User']['id'] &&
							   ( !empty($cuser['Role']['is_admin']) || $uid == $group['User']['id'] ) ):
					?>
					<a href="javascript:void(0)" class="changeAdmin" data-id="<?php echo $user['GroupUser']['id']?>" data-type="remove"><?php echo __('Remove Admin')?></a>
					<?php endif; ?>


				</span>
			</div>
		</div>
            </div>
        <?php $this->Html->rating($user['User']['id'],'users'); ?>
	</li>
<?php
	endforeach;
}
else
	echo '<div class="clear">' . __('No more results found') . '</div>';
?>
    