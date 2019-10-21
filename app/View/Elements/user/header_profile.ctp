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

<?php
if(empty($title)) $title = "Featured Members";
if(empty($num_item_show)) $num_item_show = 10;

$friends = $this->requestAction(
    "users/friends/num_item_show:$num_item_show/user_id:$uid"
);
?>

<div class="profile-header">


    <div id="cover">
         <img id="cover_img_display" width="100%" src="<?php  echo $this->storage->getUrl($user['User']["id"],'',$user['User']['cover'],"moo_covers"); ?>" />

        <?php if ( !empty( $cover_album_id ) ): ?>
            <a href="<?php echo $this->request->base?>/albums/view/<?php echo $cover_album_id?>"></a>
        <?php endif; ?>

        <?php if ( $uid == $user['User']['id'] ): ?>
            <div id="cover_upload">
                <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "users",
                                            "action" => "ajax_cover",
                                            "plugin" => false,

                                        )),
             'title' => __('Edit Cover Picture'),
             'innerHtml'=> __('Edit Cover Picture'),
          'data-backdrop' => 'static',
     ));
 ?>

            </div>
        <?php endif; ?>
    </div>
    <div id="avatar">
            <?php if ( !empty( $profile_album_id ) ): ?>
                <a href="<?php echo $this->request->base?>/albums/view/<?php echo $profile_album_id?>">
                    <?php echo $this->Moo->getItemPhoto(array('User' => $user['User']), array('prefix' => '200_square'), array("id" => "av-img"))?>
                </a>
            <?php else: ?>
                <?php echo $this->Moo->getItemPhoto(array('User' => $user['User']), array('prefix' => '200_square'), array("id" => "av-img"))?>
            <?php endif; ?>

            <?php if ( $uid == $user['User']['id'] ): ?>
                <div id="avatar_upload" >
                    <?php if ($isMobile): ?>
                        <a href="<?php echo $this->request->base?>/users/avatar">
                            <?php echo __('Edit Profile Picture'); ?>
                        </a>
                    <?php else: ?>
                    <?php
                            $this->MooPopup->tag(array(
                                   'href'=>$this->Html->url(array("controller" => "users",
                                                                  "action" => "ajax_avatar",
                                                                  "plugin" => false,
                                                              )),
                                   'title' => __('Edit Profile Picture'),
                                   'innerHtml'=> __('Edit Profile Picture'),
                                   'data-backdrop' => 'static'
                           ));
                       ?>
                    <?php endif; ?>

                </div>
            <?php endif; ?>
        <?php if ( !empty($is_online)): ?>
                <span class="online-stt">
                </span>
        <?php endif; ?>
    </div>
    
    <div class="profile-info-section">
        <h1><?php echo $this->Text->truncate($user['User']['name'], 30, array('exact' => false, 'html'=>true))?></h1>
    </div>
    
    <div class="section-menu"><?php $this->Html->rating($uid,'profile'); ?>
        <div class="profile-action">
            <?php $this->getEventManager()->dispatch(new CakeEvent('View.Elements.User.headerProfile.beforeRenderSectionMenu', $this)); ?>

            <?php if ($user['User']['id'] != $uid && !empty($uid)): ?>


<?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "conversations",
                                            "action" => "ajax_send",
                                            "plugin" => false,
                                            $user['User']['id']
                                        )),
             'title' => __('Send New Message'),
             'innerHtml'=> '<i class="visible-xs visible-sm material-icons">chat</i><i class="hidden-xs hidden-sm">' . __('Send Message') . '</i>',
          'class'=>'button button-action'
     ));
 ?>


            <?php if ( !empty($request_sent) ): ?>
            <a id="userCancelFriend" href="<?php echo $this->request->base?>/friends/ajax_cancel/<?php echo $user['User']['id']?>" class="button button-action" title="<?php __('Cancel a friend request');?>">
                <i class="visible-xs visible-sm material-icons">clear</i><i class="hidden-xs hidden-sm"><?php echo __('Cancel Request')?></i>
            </a>
            <?php endif; ?>

            <?php if ( !empty($respond) ): ?>
            <div class="dropdown" >
                <a id="respond" data-target="#" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false" class="button button-action" title="<?php __('Respond to Friend Request');?>">
                    <i class="visible-xs visible-sm material-icons">person_add</i><i class="hidden-xs hidden-sm"><?php echo __('Respond to Friend Request')?></i>
                </a>

                <ul class="dropdown-menu" role="menu" aria-labelledby="respond">
                    <li><a data-id="<?php echo  $request_id; ?>" data-status="1" class="respondRequest" href="javascript:void(0)"><?php echo  __('Accept'); ?></a></li>
                    <li><a data-id="<?php echo  $request_id; ?>" data-status="0" class="respondRequest" href="javascript:void(0)"><?php echo  __('Delete'); ?></a></li>
                </ul>
            </div>

            <?php endif; ?>

            <?php if ( !empty($uid) && !$areFriends && empty($request_sent) && empty($respond) ): ?>
                <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "friends",
                                            "action" => "ajax_add",
                                            "plugin" => false,
                                            $user['User']['id']
                                        )),
             'title' => sprintf( __('Send %s a friend request'), $user['User']['name'] ),
             'innerHtml'=> '<i class="visible-xs visible-sm material-icons">person_add</i><i class="hidden-xs hidden-sm">' . __('Add as Friend') .'</i>',
          'id' => 'addFriend_'. $user['User']['id'],
          'class' => 'button button-action'
     ));
 ?>
            <?php endif; ?>

        <?php endif;?>

        <?php if ($uid && Configure::read("core.enable_follow") && $uid != $user['User']['id']): ?>
            <?php
            $followModel = MooCore::getInstance()->getModel("UserFollow");
            $follow = $followModel->checkFollow($uid,$user['User']['id']);
            ?>
            <?php if (!$follow): ?>
                <a href="javascript:void(0);" class="button button-action user_action_follow" data-uid="<?php echo $user['User']['id']; ?>" data-follow="0" >
                    <i class="visible-xs visible-sm material-icons">rss_feed</i><i class="hidden-xs hidden-sm">
                        <?php echo __('Follow')?></i></a>
            <?php else : ?>
                <a href="javascript:void(0);" class="button button-action user_action_follow" data-uid="<?php echo $user['User']['id']; ?>" data-follow="1" >
                    <i class="visible-xs visible-sm material-icons"">check</i><i class="hidden-xs hidden-sm">
                        <?php echo __('Unfollow')?></i></a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($user['User']['id'] == $uid): ?>
            <a href="<?php echo $this->request->base?>/users/profile" class="button button-action" >
            <i class="visible-xs visible-sm material-icons">mode_edit</i><i class="hidden-xs hidden-sm">
                <?php echo __('Edit Profile')?></i></a>
        <?php endif; ?>
        
        <div class="dropdown">
                    <a href="#" data-toggle="dropdown" >
                        <i class="material-icons">more_vert</i>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if ( !empty($cuser['role_id']) && $cuser['Role']['is_admin'] && !$user['User']['featured'] ): ?>
							<li><a href="<?php echo $this->request->base?>/admin/users/feature/<?php echo $user['User']['id']?>"><?php echo __('Feature User')?></a></li>
							<?php endif; ?>
							<?php if ( !empty($cuser['role_id']) && $cuser['Role']['is_admin'] && $user['User']['featured'] ): ?>
							<li><a href="<?php echo $this->request->base?>/admin/users/unfeature/<?php echo $user['User']['id']?>"><?php echo __('Unfeature User')?></a></li>
							<?php endif; ?>
							<?php if ( !empty($cuser['role_id']) && $cuser['Role']['is_admin'] && !$user['Role']['is_admin'] ): ?>
							<li><a href="<?php echo $this->request->base?>/admin/users/edit/<?php echo $user['User']['id']?>"><?php echo __('Edit User')?></a></li>
							<?php endif; ?>
							<li>
				                            <?php
				      $this->MooPopup->tag(array(
				             'href'=>$this->Html->url(array("controller" => "reports",
				                                            "action" => "ajax_create",
				                                            "plugin" => false,
				                                            'user',
				                                            $user['User']['id']
				                                        )),
				             'title' => __('Report User'),
				             'innerHtml'=> __('Report User'),
				     ));
				 ?>
				                          </li>
							<?php if ( !empty($uid) && $areFriends ): ?>
				            <li><?php
				      $this->MooPopup->tag(array(
				             'href'=>$this->Html->url(array("controller" => "friends",
				                                            "action" => "ajax_remove",
				                                            "plugin" => false,
				                                            $user['User']['id']
				                                            
				                                        )),
				             'title' => __('Unfriend'),
				             'innerHtml'=> __('Unfriend'),
				     ));
				 ?></li>
				            <?php endif; ?>		
				            <?php if ( !empty($uid) && ($uid != $user['User']['id'] ) && !$user['Role']['is_admin'] && !$user['Role']['is_super']): ?>
				            <li><?php
				                if(!$is_viewer_block){
				                    $this->MooPopup->tag(array(
				                        'href'=>$this->Html->url(array("controller" => "user_blocks",
				                                            "action" => "ajax_add",
				                                            "plugin" => false,
				                                             $user['User']['id']
				                                            
				                                        )),
				                            'title' => __('Block'),
				                            'innerHtml'=> __('Block'),
				                         ));
				                }else{
				                    $this->MooPopup->tag(array(
				                        'href'=>$this->Html->url(array("controller" => "user_blocks",
				                                            "action" => "ajax_remove",
				                                            "plugin" => false,
				                                            $user['User']['id']
				                                            
				                                        )),
				                            'title' => __('Unblock'),
				                            'innerHtml'=> __('Unblock'),
				                         ));
				                }
				 ?></li>
				            <?php endif; ?>	
				            <?php $this->getEventManager()->dispatch(new CakeEvent('View.Elements.User.headerProfile.afterRenderActionMenu', $this)); ?>
                    </ul>
         </div>
        
         </div>


    </div>
    <div class="clear"></div>
    <?php if ( $canView ): ?>
            <ul class="list3 profile_info">
                    <?php if ( !empty( $user['User']['gender'] ) ): ?>
                        <li style="background:none;padding:0"><span class="date"><?php echo __('Gender')?>:</span> <?php $this->Moo->getGenderTxt($user['User']['gender']); ?></li>
                    <?php endif; ?>
                <?php if ( !empty( $user['User']['birthday'] ) && $user['User']['birthday'] != '0000-00-00'): ?>
                        <li><span class="date"><?php echo __('Born on')?>:</span> <?php echo $this->Time->format($user['User']['birthday'], '%B %d', false, $utz)?></li>
                    <?php endif; ?>
                    <?php 
                	//add profile type
	                ?>
	                <?php if ($user['ProfileType']['id']):?>
	                	<?php if (Configure::read('core.enable_show_profile_type')):?>
	                	<li>
	                		<span class="date"><?php echo __('Profile type');?>: </span>
	                		<a href="<?php echo $this->request->base;?>/users/index/profile_type:<?php echo $user['ProfileType']['id'];?>"><?php echo $user['ProfileType']['name'];?></a>
	                	</li>	
	                	<?php endif;?>
                    <?php $helper = MooCore::getInstance()->getHelper("Core_Moo");?>
                     <?php foreach ($fields as $field):
                         if (!in_array($field['ProfileField']['type'],$helper->profile_fields_default))
                         {
                             $options = array();
                             if ($field['ProfileField']['plugin'])
                             {
                                 $options = array('plugin' => $field['ProfileField']['plugin']);
                             }

                             echo $this->element('profile_field/' . $field['ProfileField']['type'].'_profile', array('field' => $field,'user'=>$user),$options);
                             continue;
                         }
                           if ( !empty( $field['ProfileFieldValue']['value'] ) && $field['ProfileField']['type'] != 'heading' ) :
                        ?>
                                <li><span class="date"><?php echo $field['ProfileField']['name']?>: </span>
                                        <?php echo $this->element( 'misc/custom_field_value', array( 'field' => $field ) ); ?>
                                </li>
                        <?php endif;
                    endforeach;
                        ?>
                     <?php endif;?>
                                
                    <!-- Should be hook for third party -->
                    <?php $this->getEventManager()->dispatch(new CakeEvent('Elements.user.headerProfile', $this)); ?>
                    <!-- Should be hook for third party -->
                </ul>
        <?php endif; ?>
    <div class="profile_info">
        <?php echo $this->Html->rating($user['User']['id'],'users'); ?>
    </div>
</div>