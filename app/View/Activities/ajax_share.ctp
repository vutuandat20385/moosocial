<?php if ($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooActivities", "mooShare"], function($, mooActivities, mooShare) {
        mooActivities.init();
        mooShare.init();
    });

    require(["jquery","mooToggleEmoji"], function($, mooToggleEmoji) {
        mooToggleEmoji.init('commentForm_<?php echo $activity['Activity']['id'];?>');
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery', "mooActivities", 'mooShare'), 'object' => array('$', "mooActivities", 'mooShare'))); ?>
mooActivities.init();
mooShare.init();
<?php $this->Html->scriptEnd(); ?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji'),  'object' => array('$', 'mooToggleEmoji'))); ?>
mooToggleEmoji.init('commentForm_<?php echo $activity['Activity']['id'];?>');
<?php $this->Html->scriptEnd();  ?>

<?php endif; ?>

<?php $this->setCurrentStyle(4);?>
<?php if (!empty($activity)): ?>
<?php 
    $item_type = $activity['Activity']['item_type'];
    if ($activity['Activity']['plugin'])
    {
        $options = array('plugin'=>$activity['Activity']['plugin']);
    }
    else
    {
        $options = array();
    }

    if ($item_type)
    {
        list($plugin, $name) = mooPluginSplit($item_type);
        $object = MooCore::getInstance()->getItemByType($item_type,$activity['Activity']['item_id']);

    }
    else
    {
        $plugin = '';
        $name ='';
        $object = null;
    }

    $item_type =  empty($activity['Activity']['item_type']) ? 'activity' : $activity['Activity']['item_type'];
    $item_id = !empty($activity['Activity']['item_id']) ? $activity['Activity']['item_id'] : $activity['Activity']['id'];
?>
    
<li class="slide" id="activity_<?php echo $activity['Activity']['id']?>">
    <div class="feed_main_info">
    	<div class="dropdown edit-post-icon">
            <a href="javascript:void(0)" data-toggle="dropdown" class="cross-icon">
               <i class="material-icons">more_vert</i>
            </a>
            <ul class="dropdown-menu">
                <!-- New hook -->
                <?php $this->getEventManager()->dispatch(new CakeEvent('element.activities.beforeRenderMenuAction', $this,array('activity'=>$activity))); ?>
                <!-- New hook -->
                <li>
                    <?php
                        $this->MooPopup->tag(array(
                            'href'=>$this->Html->url(array("controller" => "notifications",
                                                          "action" => "stop",
                                                          "plugin" => false,
                                                        'activity',
                                                          $activity['Activity']['id']
                                                      )),
                            'title' => __('Stop Notifications'),
                            'innerHtml'=> __('Stop Notifications'),
                            'id' => 'stop_notification_activity' . $activity['Activity']['id']
                       ));
                   ?> 
                </li>
                
                <?php if(!empty($activity['UserTagging']['users_taggings']) && $activity['Activity']['user_id'] == $uid): ?>
                <li>
                    <?php
                            $this->MooPopup->tag(array(
                                   'href'=>$this->Html->url(array("controller" => "friends",
                                                                  "action" => "tagged",
                                                                  "plugin" => false,
                                                                  $activity['Activity']['id']
                                                              )),
                                   'title' => __('Tag Friends'),
                                   'innerHtml'=> __('Tag Friends'),
                           ));
                       ?> 
                </li>
                <?php endif; ?>
                
                <?php if (isset($activity['UserTagging']['users_taggings']) && in_array($uid, explode(',', $activity['UserTagging']['users_taggings']))): ?>
                <li>
                    <a href=""><?php echo __('Remove Tags'); ?></a>
                </li>
                <?php endif; ?>
                
                <?php if ($activity['Activity']['user_id'] == $uid && $activity['Activity']['action'] == 'wall_post'):?>
                <li>
                    <a class="editActivity" data-activity-id="<?php echo $activity['Activity']['id']?>" href="javascript:void(0)" >
                        <?php echo __('Edit Post'); ?>
                    </a>
                </li>
                <?php endif;?>
                <li>
                    <a class="removeActivity" data-activity-id="<?php echo $activity['Activity']['id']?>" href="javascript:void(0)">
                        <?php echo __('Delete Post'); ?>
                    </a>
                </li>
                <li>
                    <?php if ( $activity['Activity']['params'] == 'item' && (isset($object[$name]['like_count']))): ?>
                        <?php
                        $item_close_comment = $this->Moo->getCloseComment($item_id, $item_type);
                        if($item_close_comment['status']){
                            $title =  __('Open Comment');
                            $is_close_comment = 1;
                        }else{
                            $title =   __('Close Comment');
                            $is_close_comment = 0;
                        }
                        ?>
                        <a class="closeComment" data-id="<?php echo $item_id?>" data-type="<?php echo $item_type?>" data-close="<?php echo $is_close_comment;?>" href="javascript:void(0)" >
                            <?php echo $title; ?>
                        </a>
                    <?php else: ?>
                        <?php
                        $item_close_comment = $this->Moo->getCloseComment($activity['Activity']['id'], 'activity', $activity);
                        if($item_close_comment['status']){
                            $title =  __('Open Comment');
                            $is_close_comment = 1;
                        }else{
                            $title =   __('Close Comment');
                            $is_close_comment = 0;
                        }
                        ?>
                        <a class="closeComment" data-id="<?php echo  $activity['Activity']['id']?>" data-type="activity" data-close="<?php echo $is_close_comment;?>" href="javascript:void(0)" >
                            <?php echo $title; ?>
                        </a>
                    <?php endif; ?>
                </li>
                <?php if (( (!empty($admins) && in_array($uid, $admins) && strtolower($activity['Activity']['type']) != 'user') || $cuser['Role']['is_admin'])): ?>
                	<?php
	                	$pin_type = '';
	                	if (strtolower($subject_type) != 'user')
	                	{
	                		$pin_type = 'activity';
	                		if ($subject_type)
	                		{
	                			$pin_type = 'item';
	                		}
	                	}
                	?>
	                <?php if ($pin_type):?>
	                <?php
	                	$action_pin = 'ajax_activity_pin';
	                	$text_pin = __('Pin to top');
	                	if ($pin_type == 'activity')
	                	{
	                		if ($activity['Activity']['activity_pin'])
	                		{
	                			$text_pin = __('Unpin from top');
	                		}
	                	}
	                	else
	                	{
	                		$action_pin = 'ajax_pin';
	                		if ($activity['Activity']['pin'])
	                		{
	                			$text_pin = __('Unpin from top');
	                		}
	                	}
	                ?>
						<li class="">
		                    <a href="<?php echo $this->request->base?>/activities/<?php echo $action_pin?>/<?php echo $activity['Activity']['id']?>"><?php echo $text_pin?></a>
		                </li>
	                <?php endif;?>
                <?php endif;?>
            </ul>
          </div>
        <div class="activity_feed_image">
            <?php echo $this->Moo->getItemPhoto(array('User' => $activity['User']),array( 'prefix' => '50_square'), array('class' => 'img_wrapper2 user_avatar_large'))?>
        </div>
        <div class="activity_feed_content">
            <div class="comment">
            <?php echo $this->Moo->getName($activity['User'])?><?php $this->getEventManager()->dispatch(new CakeEvent('element.activities.afterRenderUserNameFeed', $this,array('user'=>$activity['User']))); ?>
            <?php $this->getEventManager()->dispatch(new CakeEvent('element.activities.renderFeelingFeed', $this,array('user'=>$activity['User'], 'activity' => $activity['Activity']))); ?>
            <?php
				echo $this->element('activity/text/' . $activity['Activity']['action'], array('activity' => $activity,'object'=>$object),$options);
			?>
                <div class="feed_time">
                   <a href="<?php echo $this->request->base?>/users/view/<?php echo $activity['Activity']['user_id']?>/activity_id:<?php echo $activity['Activity']['id']?>" class="date"><?php echo __('Just now')?></a>
                    <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "histories",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'activity',
                                            $activity['Activity']['id']
                                        )),
             'title' => __('Show edit history'),
             'innerHtml'=> __('Edited'),
          'style' => empty($activity['Activity']['edited']) ? 'display:none' : '',
          'id' => 'history_activity_'. $activity['Activity']['id'],
          'class' => 'edit-btn',
		  'data-dismiss'=>'modal'
     ));
 ?>
                   
                   <?php if (!$activity['Activity']['target_id']):?>
                 	<?php
                 	 switch ($activity['Activity']['privacy']) {
                 	 	case '1':
                 	 		$text = __('Shared with: Everyone');
                 	 		$icon = 'public';                 	 	
                 	 	break;                 	 		
                 	 	case '2':
                 	 		$text = __('Shared with: Friend');
                 	 		$icon = 'people';      
                 	 	break;
                 	 	case '3':
                 	 		$text = __('Shared with: Only Me');
                 	 		$icon = 'lock';      
                 	 	break;
                 	 } 
                 	?>
					<?php if(($activity['Activity']['user_id'] == $uid || $cuser['Role']['is_admin']) && $activity['Activity']['action'] == 'wall_post'): ?>

                        <span class="dropdown">
                            <a id="permission_<?php echo $activity['Activity']['id'] ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" class="tip" href="javascript:void(0);" original-title="<?php echo $text;?>"> <i class="material-icons"><?php echo $icon;?></i>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="permission_<?php echo $activity['Activity']['id'] ?>">
                                <li><a data-privacy="1" data-activity-id="<?php echo $activity['Activity']['id']; ?>" class="change-activity-privacy<?php if($activity['Activity']['privacy'] == 1) echo ' n52'; ?>" href="javascript:void(0)"><?php echo __('Everyone'); ?></a></li>
                                <li><a data-privacy="2" data-activity-id="<?php echo $activity['Activity']['id']; ?>" class="change-activity-privacy<?php if($activity['Activity']['privacy'] == 2) echo ' n52'; ?>" href="javascript:void(0)"><?php echo __('Friends Only'); ?></a></li>
                            </ul>
                        </span>
                    <?php else: ?>
                 	    <a class="tip" href="javascript:void(0);" original-title="<?php echo $text;?>"> <i class="material-icons"><?php echo $icon;?></i></a>
                    <?php endif; ?>
                 	
                 <?php elseif (strtolower($activity['Activity']['type']) == 'user'):?>
                   <?php 
                   	$target = MooCore::getInstance()->getItemByType($activity['Activity']['type'],$activity['Activity']['target_id']);
                   ?>
                   <?php if ($activity['Activity']['privacy'] == PRIVACY_FRIENDS) :?>
                 	<a class="tip" href="javascript:void(0);" original-title="<?php echo __('Shared with: %s\'Friends instead of %s\'Friends of friends',$target['User']['moo_title'],$target['User']['moo_title']);?>"> <i class="material-icons">people</i></a>
                 	<?php else:?>
                 	<a class="tip" href="javascript:void(0);" original-title="<?php echo __('Shared with: Everyone');?>"> <i class="material-icons">public</i></a>
                 	<?php endif;?>
                 	                
                 <?php endif;?>
                </div>
            </div>
        </div>
         <div class="clear"></div>
         <div class="activity_feed_content_text" id="activity_feed_content_text_<?php echo $activity['Activity']['id'];?>">
            <?php
               
                echo $this->element('activity/content/' . $activity['Activity']['action'], array('activity' => $activity,'object'=>$object),$options);
            ?>
         </div>
    </div>
    <div class="feed_comment_info">
<?php $this->getEventManager()->dispatch(new CakeEvent('element.activities.renderLikeReview', $this,array('uid' => $uid,'activity' => array('id' => $activity['Activity']['id'], 'like_count' => 0), 'item_type' => 'activity' ))); ?>
	<div class="date">

			<?php if ( $activity['Activity']['params'] == 'mobile' ) echo __('via mobile'); ?>
			<a href="javascript:void(0)" class="showCommentForm" data-id="<?php echo $activity['Activity']['id']?>"><i class="material-icons">comment</i><?php echo __('Comment')?></a>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.activities.renderLikeButton', $this,array('uid' => $uid,'activity' => array('id' => $activity['Activity']['id'], 'like_count' => 0), 'item_type' => 'activity' ))); ?>
<?php if(empty($hide_like)): ?>
			&nbsp;<a href="javascript:void(0)" data-id="<?php echo $activity['Activity']['id']?>" data-type="activity" data-status="1" id="activity_l_<?php echo $activity['Activity']['id']?>" class="comment-thumb likeActivity"><i class="material-icons">thumb_up</i></a> <span id="activity_like_<?php echo $activity['Activity']['id']?>">0</span>
<?php endif; ?>
        <?php if(empty($hide_dislike)): ?>
            <a href="javascript:void(0)" data-id="<?php echo $activity['Activity']['id']?>" data-type="activity" data-status="0" id="activity_d_<?php echo $activity['Activity']['id']?>" class="comment-thumb likeActivity"><i class="material-icons">thumb_down</i></a> <span id="activity_dislike_<?php echo $activity['Activity']['id']?>">0</span>
        <?php endif; ?>
            
            <?php echo $this->element('share', array('activity' => $activity)); ?>
            
        </span>
        </div>
	<ul class="activity_comments" style="display:none" id="comments_<?php echo $activity['Activity']['id']?>">
		<li id="newComment_<?php echo $activity['Activity']['id']?>" style="display:none"><?php echo $this->Moo->getItemPhoto(array('User' => $cuser), array( 'prefix' => '50_square'), array('class' => 'img_wrapper2 user_avatar_small'))?>
			<div class="comment">
                            <textarea class="commentBox showCommentBtn" data-id="<?php echo $activity['Activity']['id']?>" placeholder="<?php echo __('Write a comment...')?>" id="commentForm_<?php echo $activity['Activity']['id']?>"></textarea>

                            <div id="commentForm_<?php echo $activity['Activity']['id'];?>-emoji" class="emoji-toggle"></div>
                            <?php $this->getEventManager()->dispatch(new CakeEvent('Element.activities.afterRenderCommentForm', $this,array('type' => 'commentForm' ,'id'=>$activity['Activity']['id']))); ?>
                            <div class="clear"></div>
				<div style="display:block" class="commentButton" id="commentButton_<?php echo $activity['Activity']['id']?>">
					<input type="hidden" id="comment_image_<?php echo $activity['Activity']['id'];?>" />
					<div data-id="<?php echo $activity['Activity']['id'];?>" id="comment_button_attach_<?php echo $activity['Activity']['id'];?>"></div>
				    <a class="btn btn-action viewer-submit-comment" data-activity-id="<?php echo $activity['Activity']['id']?>" href="javascript:void(0)"  class="btn btn-action "><em class="material-icons">send</em></a>
				</div>
				<div id="comment_preview_image_<?php echo $activity['Activity']['id'];?>"></div>
			</div>
		</li>
	</ul>
    </div>
    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooAttach'), 'object' => array('$', 'mooAttach'))); ?>
    mooAttach.registerAttachComment(<?php echo $activity['Activity']['id'];?>);
    <?php $this->Html->scriptEnd(); ?>
</li>
<?php endif;?>