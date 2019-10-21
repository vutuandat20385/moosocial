<?php if($this->request->is('ajax')): ?>
<script>
    require(["jquery","mooUser"], function($,mooUser) {
        mooUser.initRespondRequest();
    });
</script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false,'requires'=>array('jquery','mooUser'),'object'=>array('$','mooUser'))); ?>
    mooUser.initRespondRequest();
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php if (!empty($user)): ?>
    <div class="list-content">
        <div class="user-idx-item">
                   <?php echo $this->Moo->getItemPhoto(array('User' => $user['User']), array('prefix' => '200_square'))?>
                   
                   <?php if (!empty($uid)): ?>
                            <?php if ( $this->MooPeople->sentFriendRequest($user['User']['id'])): ?>
                                <a href="<?php echo $this->request->base?>/friends/ajax_cancel/<?php echo $user['User']['id']?>" id="cancelFriend_<?php echo $user['User']['id']?>" class="add_people" title="<?php __('Cancel a friend request');?>">
                                    <i class="material-icons">clear</i> <?php echo __('Cancel Request')?>
                                </a>
                            <?php elseif ($this->MooPeople->respondFriendRequest($user['User']['id'])): ?>
                               <?php
                               if(empty($request_id)) {
                                   $friendRequestModel = MooCore::getInstance()->getModel('FriendRequest');
                                   $respond = $friendRequestModel->getRequests($uid);
                                   $request_id = Hash::combine($respond, '{n}.FriendRequest.sender_id', '{n}.FriendRequest.id');
                               }
                               ?>
                                <div class="dropdown" style="" >
                                    <a href="#" id="respond" data-target="#" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false" class="add_people" title="<?php __('Respond to Friend Request');?>">
                                        <i class="material-icons">person_add</i> <?php echo __('Respond')?>
                                    </a>

                                    <ul class="dropdown-menu" role="menu" aria-labelledby="respond">
                                        <li><a data-id="<?php echo  $request_id[$user['User']['id']]; ?>" data-status="1" class="respondRequest" href="javascript:void(0)"><?php echo  __('Accept'); ?></a></li>
                                        <li><a data-id="<?php echo  $request_id[$user['User']['id']]; ?>" data-status="0" class="respondRequest" href="javascript:void(0)"><?php echo  __('Delete'); ?></a></li>
                                    </ul>
                                </div>
                                
                            <?php elseif ($this->MooPeople->isFriend($user['User']['id'])): ?>
                                <?php
                                    $this->MooPopup->tag(array(
                                           'href'=>$this->Html->url(array("controller" => "friends",
                                                                          "action" => "ajax_remove",
                                                                          "plugin" => false,
                                                                          $user['User']['id']
                                                                      )),
                                           'title' => __('Remove'),
                                           'innerHtml'=> '<i class="material-icons">clear</i> ' . __('Remove'),
                                        'id' => 'removeFriend_'.$user['User']['id'],
                                        'class' => 'add_people'
                                   ));
                               ?>           
                            <?php elseif($user['User']['id'] != $uid): ?>
                                <?php
                                    $this->MooPopup->tag(array(
                                           'href'=>$this->Html->url(array("controller" => "friends",
                                                                          "action" => "ajax_add",
                                                                          "plugin" => false,
                                                                          $user['User']['id']
                                                                      )),
                                           'title' => sprintf( __('Send %s a friend request'), $user['User']['name'] ),
                                           'innerHtml'=> '<i class="material-icons">person_add</i>&nbsp;'. __('Add'),
                                        'id' => 'addFriend_'. $user['User']['id'],
                                        'class'=> 'add_people'
                                   ));
                               ?>
                            <?php endif; ?>
                    <?php endif; ?>
        </div>
        <div class="user-list-info">
            <div class="user-name-info">
                <?php echo $this->Moo->getName($user['User']) ?>
            </div>
            <div class="">
                <span class="date">
                    <?php echo __n('%s friend', '%s friends', $user['User']['friend_count'], $user['User']['friend_count']) ?> .
                    <?php echo __n('%s photo', '%s photos', $user['User']['photo_count'], $user['User']['photo_count']) ?><br />
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>