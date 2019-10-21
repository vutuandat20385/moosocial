<div>
    <div class="tooltip_main">
    
        <div class="tooltip_photos">    
            <?php echo $this->Moo->getItemPhoto(array($type => $object[$type]), array('prefix' => '100_square'), array('class'=>'tooltip_image'))?>
           	<?php if (Configure::read("core.enable_show_join_profile_popup")):?>
             	<div><?php echo __('Joined from %s', $this->Time->format($object['User']['created'],'%B %Y', null, $utz))?></div>
            <?php endif;?>
        </div>    

        <div class="item_geneal_info">
            <div class="item_title">
                <?php echo $this->Text->truncate(isset($object[$type]['name'])?$object[$type]['name']:$object[$type]['title'], 30, array('exact' => false))?>
                <?php if ( !empty($is_online)): ?><span class="online-stt"></span><?php endif; ?>
            </div>
            <?php if($type == 'User'): ?>
            	<?php $gender = $this->Moo->getGenderTxt($object['User']['gender'],true);?>
            	<?php if ($gender && Configure::read("core.enable_show_gender_profile_popup")):?>
                	<div class="item_stat extra_info"><span class="date"><?php echo __('Gender')?>:</span> <?php echo $gender; ?></div>
                <?php endif;?>
                <?php if (Configure::read("core.enable_show_birthday_profile_popup") && !empty( $object['User']['birthday'] ) && $object['User']['birthday'] != '0000-00-00'): ?>
                    <div class="item_stat extra_info"><span class="date"><?php echo __('Born on')?>:</span> <?php echo $this->Time->format($object['User']['birthday'], '%B %d', false, $utz)?></div>
                <?php endif; ?>
            <?php endif; ?> 
            <?php if($type == 'User'): ?>
              <div class="item_stat">                  
                 <div class="star_block">
                      <span class="people_btn"><i class="material-icons">people</i></span>
                              <div>
                                  <?php  echo __n( 'Friend <span>%s</span>', 'Friends <span>%s</span>', $object['User']['friend_count'], $object['User']['friend_count'] ); ?>
                              </div>
                 </div>
                          <div class="star_block">
                              <span class="review_star"><i class="material-icons">photo</i></span>
                              <div style="padding-left: 6px">
                                  <?php echo __n( 'Photo', 'Photos', $object['User']['photo_count'], $object['User']['photo_count'] ); ?> <span><?php echo $object['User']['photo_count'] ?></span>
                               </div>
                          </div>
             </div>
          <?php endif; ?> 
          <div class="item_adding_info">       
             <?php $this->getEventManager()->dispatch(new CakeEvent('View.Activities.ajaxLoadTooltip.loadItemInfo', $this)); ?>
        </div>        
        </div>
        
       
            
        
        
          
    </div>
    <?php if($type == 'User' && $object['User']['id'] != $uid && !empty($uid)): ?>   
              <div class="item_tooltip_actions">                                         
                     <?php if ( isset($friends_request) && in_array($object['User']['id'], $friends_request) && $object['User']['id'] != $uid): ?>
                            <a href="<?php echo $this->request->base?>/friends/ajax_cancel/<?php echo $object['User']['id']?>" id="cancelFriend_<?php echo $object['User']['id']?>" class=" button button-action" title="<?php echo __('Cancel friend request');?>">

                                <?php echo __('Cancel friend request');?>
                            </a>
                    <?php elseif ( !empty($respond) && in_array($object['User']['id'], $respond ) && $object['User']['id'] != $uid): ?>
                            <div class="dropdown" style="" >

                                <a data-target="#themeModal" data-toggle="modal" data-dismiss="" data-backdrop="true" href="<?php echo $this->request->base?>/friends/ajax_request/<?php echo $object['User']['id']?>" title="<?php echo __('Respond to Friend Request');?>">

                                   <?php echo __('Respond to Friend Request');?>
                                </a>

                                <ul class="dropdown-menu" role="menu" aria-labelledby="respond">
                                    <li><a class="respondRequest" data-id="<?php echo  $request_id[$object['User']['id']]; ?>" data-status="1" href="javascript:void(0)"><?php echo  __('Accept'); ?></a></li>
                                    <li><a class="respondRequest" data-id="<?php echo  $request_id[$object['User']['id']]; ?>" data-status="0" href="javascript:void(0)"><?php echo  __('Delete'); ?></a></li>
                                </ul>
                            </div>
                        
                    <?php elseif (isset($friends) && in_array($object['User']['id'], $friends) && $object['User']['id'] != $uid): ?>
                        <?php
                            $this->MooPopup->tag(array(
                                   'href'=>$this->Html->url(array("controller" => "friends",
                                                                  "action" => "ajax_remove",
                                                                  "plugin" => false,
                                                                  $object['User']['id']
                                                              )),
                                   'title' => sprintf( __('Remove %s from your friends list'), h($object['User']['name']) ),
                                   'innerHtml'=> __('Remove friend'),
                                'id' => 'removeFriend_'.$object['User']['id'],
                                'class' => ' button button-action'
                           ));
                        ?>
                        
                    <?php elseif(isset($friends) && isset($friends_request) && !in_array($object['User']['id'], $friends) && !in_array($object['User']['id'], $friends_request) && $object['User']['id'] != $uid): ?>
                        <?php
                                $this->MooPopup->tag(array(
                                       'href'=>$this->Html->url(array("controller" => "friends",
                                                                      "action" => "ajax_add",
                                                                      "plugin" => false,
                                                                      $object['User']['id']
                                                                  )),
                                       'title' => sprintf( __('Send %s a friend request'), h($object['User']['name']) ),
                                       'innerHtml'=> __('Add Friend'),
                                    'id' => 'addFriend_'. $object['User']['id'],
                                    'class'=> ' button button-action'
                               ));
                           ?>
                       
                    <?php endif; ?>
                               
                    <?php
                         $this->MooPopup->tag(array(
                                'href'=>$this->Html->url(array("controller" => "conversations",
                                                               "action" => "ajax_send",
                                                               "plugin" => false,
                                                               $object['User']['id']
                                                           )),
                                'title' => __('Send New Message'),
                                'innerHtml'=> '<i class="visible-xs visible-sm material-icons">chat</i><i class="hidden-xs hidden-sm">' . __('Message') . '</i>',
                             'class'=>' button button-action'
                        ));
                    ?>

                  <?php if (Configure::read("core.enable_follow")): ?>
                      <?php
                      $followModel = MooCore::getInstance()->getModel("UserFollow");
                      $follow = $followModel->checkFollow($uid,$object['User']['id']);
                      ?>
                      <?php if (!$follow): ?>
                          <a href="javascript:void(0);" class="button button-action usertip_action_follow  " data-uid="<?php echo $object['User']['id']; ?>" data-follow="0" >
                              <i class="visible-xs visible-sm material-icons">rss_feed</i><i class="hidden-xs hidden-sm">
                                  <?php echo __('Follow')?></i></a>
                      <?php else : ?>
                          <a href="javascript:void(0);" class="button button-action usertip_action_follow  " data-uid="<?php echo $object['User']['id']; ?>" data-follow="1" >
                              <i class="visible-xs visible-sm material-icons">check</i><i class="hidden-xs hidden-sm">
                                  <?php echo __('Unfollow')?></i></a>
                      <?php endif; ?>
                  <?php endif; ?>
            </div>                                               
          <?php endif; ?>
</div>
