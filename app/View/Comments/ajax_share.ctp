

<?php $this->setCurrentStyle(4);?>
<?php if (!empty($comment)): ?>
<li class="slide" id="itemcomment_<?php echo $comment['Comment']['id']?>" style="position: relative">
	<?php if ($this->request->is('ajax')): ?>
	<script type="text/javascript">
	    require(["jquery","mooComment", "mooActivities"], function($, mooComment, mooActivities) {
	        mooActivities.init();
	        mooComment.initEditItemComment();
	        mooComment.initRemoveItemComment();
	    });
	</script>
	<?php endif; ?>
	<?php if ( $comment['Comment']['type'] != APP_CONVERSATION ): ?>
	<div class="dropdown edit-post-icon comment-option">
		<a href="javascript:void(0)" data-toggle="dropdown" class="cross-icon">
			<i class="material-icons">more_vert</i>
		</a>
		<ul class="dropdown-menu">
			<?php if ($comment['Comment']['user_id'] == $uid):?>
			<li>
				<a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-photo-comment="0" class="editItemComment">
					<?php echo __('Edit Comment'); ?>
				</a>	
			</li>
			<?php endif;?>
			
			<li>
				<a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-photo-comment="0" class="removeItemComment" class="removeItemComment">
					<?php echo __('Delete Comment'); ?>
				</a>
			</li>
			
			
		</ul>
	</div>
	<?php endif; ?>
	<?php
	if ( !empty( $activity ) )
		echo $this->Moo->getItemPhoto(array('User' => $comment['User']),array( 'prefix' => '50_square'), array('class' => 'img_wrapper2 user_avatar_small'));
	else
		echo $this->Moo->getItemPhoto(array('User' => $comment['User']),array( 'prefix' => '100_square'), array('class' => 'img_wrapper2 user_avatar_large'));
	?>
	<div class="comment">

		<div class="comment_message">
                    <?php echo $this->Moo->getName($comment['User'])?><?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.afterRenderUserNameComment', $this,array('user'=>$comment['User']))); ?>
            <span class="main_comment" id="item_feed_comment_text_<?php echo $comment['Comment']['id']?>">
			    <?php
			    if ( !empty( $activity ) )
	                echo $this->viewMore($this->Moo->formatText( h($comment['Comment']['message']), false, true ,array('no_replace_ssl' => 1)));
	            else
	                echo $this->Moo->formatText( h($comment['Comment']['message']), false, true, array('no_replace_ssl' => 1) );
	            ?>
	            
	            <?php if ($comment['Comment']['thumbnail']):?>
	            	<div class="comment_thumb">
			            <a data-dismiss="modal" href="<?php echo $this->Moo->getImageUrl($comment,array());?>">
		                	<?php if($this->Moo->isGifImage($this->Moo->getImageUrl($comment,array()))) :  ?>
				                     <?php echo $this->Moo->getImage($comment,array('class'=>'gif_image'));?>
                                                <?php else: ?>
                                                        <?php echo $this->Moo->getImage($comment,array('prefix'=>'200'));?>
                                                <?php endif; ?>
		                </a>
	                </div>
	            <?php endif;?>
            </span>
		</div>
		<div class="feed-time date">
			<?php $has_reply = !in_array($comment['Comment']['type'],array(APP_CONVERSATION,'comment','core_activity_comment'));?>
			<?php echo __('Just now')?>

			<?php if ($comment['Comment']['type'] != APP_CONVERSATION):?>
                <?php if ($has_reply):?>
                    <?php if ( !empty( $activity ) ):?>
                        <a href="javascript:void(0);" class="reply_action activity_reply_comment_button" data-id="<?php echo $comment['Comment']['id']?>" data-type="comment"><i class="material-icons">reply</i><?php echo __('Reply');?></a>
                    <?php else:?>
                        <a href="javascript:void(0);" class="reply_action item_reply_comment_button" data-id="<?php echo $comment['Comment']['id']?>"><i class="material-icons">reply</i><?php echo __('Reply');?></a>
                    <?php endif;?>
                <?php else:
                    if($comment['Comment']['type'] == 'core_activity_comment')    {
                        $type = 'activitycomments_reply_';
                    }else if(isset($on_activity)){
                        $type = 'comments_reply_';
                    }else{
                        $type = 'item_comments_reply_';
                    }
                ?>
                    <a href="javascript:void(0);" class="reply_action reply_reply_comment_button <?php echo $uid == $comment['Comment']['user_id'] ? 'owner' : '';?>" data-type='<?php echo $type. $comment['Comment']['target_id'];?>' data-user="<?php echo $comment['Comment']['user_id'];?>" data-id="<?php echo $comment['Comment']['target_id']?>"><i class="material-icons">reply</i><?php echo __('Reply');?></a>
                <?php endif;?>
			<?php endif;?>

                    <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "histories",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'comment',
                                            $comment['Comment']['id']
                                        )),
             'title' => __('Show edit history'),
             'innerHtml'=> __('Edited'),
          'style' => empty($comment['Comment']['edited']) ? 'display:none;' : '',
          'class' => 'edit-btn',
          'id' => 'history_item_comment_'.$comment['Comment']['id'],
          'data-dismiss'=>'modal'
     ));
 ?>
            <span class="comment-action">
			<?php if ( $comment['Comment']['type'] != APP_CONVERSATION ): ?>
                <?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeButton', $this,array('uid' => $uid,'comment' => array('id' =>  $comment['Comment']['id'], 'like_count' => 0), 'item_type' => 'comment' ))); ?>
                <?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeReview', $this,array('uid' => $uid,'comment' => array('id' =>  $comment['Comment']['id'], 'like_count' => 0), 'item_type' => 'comment' ))); ?>
                <?php if(empty($hide_like)): ?>
                    <a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-type="comment" data-status="1" id="comment_l_<?php echo $comment['Comment']['id']?>" class="comment-thumb likeActivity"><i class="material-icons">thumb_up</i></a> <span id="comment_like_<?php echo $comment['Comment']['id']?>">0</span>
                <?php endif; ?>
                <?php if(empty($hide_dislike)): ?>
                    <a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-type="comment" data-status="0" id="comment_d_<?php echo $comment['Comment']['id']?>" class="comment-thumb likeActivity"><i class="material-icons">thumb_down</i></a> <span id="comment_dislike_<?php echo $comment['Comment']['id']?>">0</span>
                <?php  endif;?>
            <?php endif; ?>
            </span>
		</div>

        <?php if ($has_reply):?>
            <?php if ( !empty( $activity ) ):?>
                <ul class="activity_comments comment_list" id="comments_reply_<?php echo $comment['Comment']['id']?>">
                    <li class="new_reply_comment" style="display:none;" id="newComment_reply_<?php echo $comment['Comment']['id']?>">
                        <?php echo $this->Moo->getItemPhoto(array('User' => $cuser), array( 'prefix' => '50_square'), array('class' => 'user_avatar_small img_wrapper2'))?>
                        <div class="comment comment-form">

                            <?php echo $this->Form->textarea("commentReplyForm".$comment['Comment']['id'],array('class' => "commentBox showCommentReplyBtn", 'data-id' => $comment['Comment']['id'], 'placeholder' => __('Write a reply...') ), true) ?>
                            <?php $this->getEventManager()->dispatch(new CakeEvent('Element.activities.afterRenderCommentForm', $this,array('type' => 'commentReplyForm' ,'id'=>$comment['Comment']['id']))); ?>
                            <div id="commentReplyForm<?php echo $comment['Comment']['id'];?>-emoji" class="emoji-toggle"></div>
                            <?php if($this->request->is('ajax')): ?>
                                <script>
                                    require(["jquery","mooToggleEmoji"], function($, mooToggleEmoji) {
                                        mooToggleEmoji.init('commentReplyForm<?php echo $comment['Comment']['id'];?>');
                                    });
                                </script>
                            <?php else: ?>
                                <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji'),  'object' => array('$', 'mooToggleEmoji'))); ?>
                                mooToggleEmoji.init('commentReplyForm<?php echo $comment['Comment']['id'];?>');
                                <?php $this->Html->scriptEnd();  ?>
                            <?php endif; ?>

                            <div class="clear"></div>
                            <div style="display:block;" class="commentButton" id="commentReplyButton_<?php echo $comment['Comment']['id']?>">
                                <?php if ( !empty( $uid ) ): ?>
                                    <input type="hidden" id="comment_reply_image_<?php echo $comment['Comment']['id'];?>" />
                                    <div id="comment_reply_button_attach_<?php echo $comment['Comment']['id'];?>"></div>
                                    <a href="javascript:void(0)"  class="btn btn-action activity_reply_comment" data-id="<?php echo $comment['Comment']['id'];?>" data-type="comment"><i class="material-icons">send</i></a>

                                <?php if($this->request->is('ajax')): ?>
                                    <script type="text/javascript">
                                        require(["jquery","mooAttach"], function($,mooAttach) {
                                            mooAttach.registerAttachCommentReplay(<?php echo $comment['Comment']['id'];?>);
                                        });
                                    </script>
                                <?php else: ?>
                                    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery','mooAttach'), 'object' => array('$', 'mooAttach'))); ?>
                                    mooAttach.registerAttachCommentReplay(<?php echo $comment['Comment']['id'];?>);
                                    <?php $this->Html->scriptEnd(); ?>
                                <?php endif; ?>

                                <?php else: ?>
                                    <?php echo __('Please login or register')?>
                                <?php endif; ?>
                            </div>
                            <div id="comment_reply_preview_image_<?php echo $comment['Comment']['id'];?>"></div>
                        </div>
                    </li>
                </ul>
            <?php else:?>
                <ul class="item_comments comment_list" id="item_comments_reply_<?php echo $comment['Comment']['id']?>">
                    <li class="new_reply_comment" style="display:none;" id="item_newComment_reply_<?php echo $comment['Comment']['id']?>">
                        <?php echo $this->Moo->getItemPhoto(array('User' => $cuser), array( 'prefix' => '50_square'), array('class' => 'user_avatar_small img_wrapper2'))?>
                        <div class="comment comment-form">

                            <?php echo $this->Form->textarea("item_commentReplyForm".$comment['Comment']['id'],array('class' => "commentBox showCommentReplyBtn", 'data-id' => $comment['Comment']['id'], 'placeholder' => __('Write a reply...'), 'rows' => 3 ), true) ?>
                            <?php $this->getEventManager()->dispatch(new CakeEvent('Element.activities.afterRenderCommentForm', $this,array('type' => 'item_commentReplyForm' ,'id'=>$comment['Comment']['id']))); ?>
                            <div id="item_commentReplyForm<?php echo $comment['Comment']['id'];?>-emoji" class="emoji-toggle"></div>
                            <?php if($this->request->is('ajax')): ?>
                                <script>
                                    require(["jquery","mooToggleEmoji"], function($, mooToggleEmoji) {
                                        mooToggleEmoji.init('item_commentReplyForm<?php echo $comment['Comment']['id'];?>');
                                    });
                                </script>
                            <?php else: ?>
                                <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji'),  'object' => array('$', 'mooToggleEmoji'))); ?>
                                mooToggleEmoji.init('item_commentReplyForm<?php echo $comment['Comment']['id'];?>');
                                <?php $this->Html->scriptEnd();  ?>
                            <?php endif; ?>

                            <div class="clear"></div>
                            <div style="display:block;" class="commentButton" id="item_commentReplyButton_<?php echo $comment['Comment']['id']?>">
                                <?php if ( !empty( $uid ) ): ?>
                                    <input type="hidden" id="item_comment_reply_image_<?php echo $comment['Comment']['id'];?>" />
                                    <div id="item_comment_reply_button_attach_<?php echo $comment['Comment']['id'];?>"></div>
                                    <a href="javascript:void(0)"  class="btn btn-action item_reply_comment" data-id="<?php echo $comment['Comment']['id'];?>"><i class="material-icons">send</i></a>

                                <?php if($this->request->is('ajax')): ?>
                                    <script type="text/javascript">
                                        require(["jquery","mooAttach"], function($,mooAttach) {
                                            mooAttach.registerAttachCommentItemReplay(<?php echo $comment['Comment']['id'];?>);
                                        });
                                    </script>
                                <?php else: ?>
                                    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery','mooAttach'), 'object' => array('$', 'mooAttach'))); ?>
                                    mooAttach.registerAttachCommentItemReplay(<?php echo $comment['Comment']['id'];?>);
                                    <?php $this->Html->scriptEnd(); ?>
                                <?php endif; ?>

                                <?php else: ?>
                                    <?php echo __('Please login or register')?>
                                <?php endif; ?>
                            </div>
                            <div id="item_comment_reply_preview_image_<?php echo $comment['Comment']['id'];?>"></div>
                        </div>
                    </li>
                </ul>
            <?php endif;?>
        <?php endif;?>
	</div>
</li>
<?php endif;?>