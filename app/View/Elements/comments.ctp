


<?php
$subject = isset($data['subject']) ? $data['subject'] : MooCore::getInstance()->getSubject();
$historyModel = MooCore::getInstance()->getModel('CommentHistory');
$is_owner = 0;

if(!isset($is_close_comment)) {
    if (!empty($subject)) {
        //close comment
        $closeCommentModel = MooCore::getInstance()->getModel('CloseComment');
        $item_close_comment = $closeCommentModel->getCloseComment($subject[key($subject)]['id'], $subject[key($subject)]['moo_type']);
        if(!empty($item_close_comment)){
            $is_close_comment = 1;
        }else{
            $is_close_comment = 0;
        }

    }else{
        $is_close_comment = 0;
    }
}

if ( ( $this->request->controller != Inflector::pluralize(APP_CONVERSATION) ) && ((!empty($subject) && $subject[key($subject)]['user_id'] == $uid) || ( $uid && $cuser['Role']['is_admin'] ) || ( !empty( $data['admins'] ) && in_array( $uid, $data['admins'] ) ) ) ) {
    $is_owner = 1;
}

if ( !empty( $data['comments'] ) ):
	foreach ($data['comments'] as $comment):
?>
	<li id="itemcomment_<?php echo $comment['Comment']['id']?>" style="position: relative">
		<?php
		// delete link available for commenter, site admin and item author (except convesation)
		if ( ( $this->request->controller != Inflector::pluralize(APP_CONVERSATION) ) && ((!empty($subject) && $subject[key($subject)]['user_id'] == $uid) ||  $comment['Comment']['user_id'] == $uid || ( $uid && $cuser['Role']['is_admin'] ) || ( !empty( $data['admins'] ) && in_array( $uid, $data['admins'] ) ) ) ):?>
			<div class="dropdown edit-post-icon comment-option">
			<a href="javascript:void(0)" data-toggle="dropdown" class="cross-icon">
				<i class="material-icons">more_vert</i>
			</a>
			<ul class="dropdown-menu">
                <?php if ($comment['Comment']['user_id'] == $uid || $cuser['Role']['is_admin'] ):?>
				<li>
                    <a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-photo-comment="0" class="editItemComment"><?php echo __('Edit Comment'); ?></a>
				</li>
                <?php endif;?>
				<li>
                <?php $isTheaterMode = (!empty($blockCommentId) && $blockCommentId == 'theaterComments')? 1 : 0; ?>
                <a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-photo-comment="<?php echo $isTheaterMode; ?>" class="removeItemComment" >
                    <?php echo __('Delete Comment'); ?></a>
				</li>
			</ul>
		</div>
		<?php endif; ?>
		    
		<?php echo $this->Moo->getItemPhoto(array('User' => $comment['User']), array('prefix' => '100_square'), array('class' => 'img_wrapper2 user_avatar_large'))?>
		<div class="comment hasDelLink">
			<div class="comment_message">
				<?php echo $this->Moo->getName($comment['User'])?><?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.afterRenderUserNameComment', $this,array('user'=>$comment['User']))); ?>
				<span  class="main_comment" id="item_feed_comment_text_<?php echo $comment['Comment']['id']?>">
					<?php echo $this->viewMore( h($comment['Comment']['message']),null, null, null, true, array('no_replace_ssl' => 1))?>
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
                <?php if(!empty($activity)):?>
                    <a href="<?php echo $this->request->base?>/users/view/<?php echo $activity['Activity']['user_id']?>/activity_id:<?php echo $activity['Activity']['id']?>/comment_id:<?php echo $comment['Comment']['target_id']?>/reply_id:<?php echo $comment['Comment']['id']?>">
                        <?php echo $this->Moo->getTime( $comment['Comment']['created'], Configure::read('core.date_format'), $utz )?>
                    </a>
                <?php else:?>
                    <?php echo $this->Moo->getTime( $comment['Comment']['created'], Configure::read('core.date_format'), $utz )?>
                <?php endif;?>

                <?php if(!$is_close_comment || $is_owner):?>
                    <?php if ($comment['Comment']['type'] != APP_CONVERSATION):?>
                        <?php if (!in_array($comment['Comment']['type'],array('comment','core_activity_comment'))):?>
                            <a href="javascript:void(0);" class="reply_action item_reply_comment_button" data-id="<?php echo $comment['Comment']['id']?>"><i class="material-icons">reply</i><?php echo __('Reply');?></a>
                        <?php else:?>
                            <a href="javascript:void(0);" class="reply_action reply_reply_comment_button <?php echo $uid == $comment['Comment']['user_id'] ? 'owner' : '';?>" data-type='<?php echo $blockCommentId;?>' data-user="<?php echo $comment['Comment']['user_id'];?>" data-id="<?php echo $comment['Comment']['target_id']?>"><i class="material-icons">reply</i><?php echo __('Reply');?></a>
                            <span style="display: none;"><?php echo $comment['User']['moo_title']?></span>
                        <?php endif;?>
                    <?php endif;?>
                <?php endif;?>

			<?php
                            $this->MooPopup->tag(array(
                                   'href'=>$this->Html->url(array("controller" => "histories",
                                                                  "action" => "ajax_show",
                                                                  "plugin" => false,
                                                                  'comment',
                                                                  $comment['Comment']['id'],
                                                              )),
                                   'title' => __('Show edit history'),
                                   'innerHtml'=> $historyModel->getText('comment',$comment['Comment']['id']),
                                'id' => 'history_item_comment_'.$comment['Comment']['id'],
                                'class'=>'edit-btn',
                                'style' => empty($comment['Comment']['edited']) ? 'display:none' : '',
								'data-dismiss'=>'modal'
                           ));
                       ?>	
        <span class="comment-action">
				<?php if (empty($comment_type)): ?> 
<?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeButton', $this,array('uid' => $uid,'comment' => array('id' =>  $comment['Comment']['id'], 'like_count' => $comment['Comment']['like_count']), 'item_type' => 'comment' ))); ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeReview', $this,array('uid' => $uid,'comment' => array('id' =>  $comment['Comment']['id'], 'like_count' => $comment['Comment']['like_count']), 'item_type' => 'comment' ))); ?>
<?php if(empty($hide_like)): ?>
	            <a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-type="comment" data-status="1" id="comment_l_<?php echo $comment['Comment']['id']?>" class="comment-thumb likeActivity <?php if ( !empty( $uid ) && !empty( $data['comment_likes'][$comment['Comment']['id']] ) ): ?>active<?php endif; ?>"><i class="material-icons">thumb_up</i></a>
	            <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'comment',
                                            $comment['Comment']['id'],
                                        )),
             'title' => __('People Who Like This'),
             'innerHtml'=> '<span id="comment_like_' . $comment['Comment']['id'] . '">' . $comment['Comment']['like_count'] . '</span>',
          'data-dismiss' => 'modal'
     ));
 ?>
    </span>
    <span>                
<?php endif; ?>
	                <?php if(empty($hide_dislike)): ?>
	            <a href="javascript:void(0)" data-id="<?php echo $comment['Comment']['id']?>" data-type="comment" data-status="0" id="comment_d_<?php echo $comment['Comment']['id']?>" class="comment-thumb likeActivity <?php if ( !empty( $uid ) && isset( $data['comment_likes'][$comment['Comment']['id']] ) && $data['comment_likes'][$comment['Comment']['id']] == 0 ): ?>active<?php endif; ?>"><i class="material-icons">thumb_down</i></a>
	            <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'comment',
                                            $comment['Comment']['id'],1
                                        )),
             'title' => __('People Who Dislike This'),
             'innerHtml'=> '<span id="comment_dislike_' . $comment['Comment']['id'] . '">' .  $comment['Comment']['dislike_count'] . '</span>',
          'data-dismiss' => 'modal'
     ));
 ?>
                     <?php endif; ?>
	            <?php endif; ?> 
              </span>
            </div>

            <?php if (!in_array($comment['Comment']['type'],array(APP_CONVERSATION,'comment','core_activity_comment'))):?>
            <ul class="item_comments comment_list <?php echo !empty($comment['Replies']) ? 'isLoadNew' : '';?>" id="item_comments_reply_<?php echo $comment['Comment']['id']?>">
                <?php if(!empty($comment['RepliesIsLoadMore']) && $comment['RepliesIsLoadMore']):?>
                    <li>
                        <a class="item_reply_comment_viewmore" data-id="<?php echo $comment['Comment']['id']?>" data-type="core_activity_comment" data-close="<?php echo $is_close_comment?>" href="javascript:void(0);">
                            <?php echo __('View all replies'); ?>
                        </a>
                    </li>
                    <?php endif;?>

                    <?php if(!empty($comment['Replies'])):
                                            $reply_data['comments'] = $comment['Replies'];
                                            $reply_data['comment_likes'] = $comment['RepliesCommentLikes'];
                                            $reply_data['bIsCommentloadMore'] = 0;
                                            $reply_data['subject'] = $subject;
                                            $blockCommentId = 'item_comments_reply_'. $comment['Comment']['id'];
                                        ?>
                    <?php echo $this->element('comments', array('data' => $reply_data, 'uid' => $uid, 'blockCommentId' => $blockCommentId));?>
                <?php endif;?>

                <?php if(!$is_close_comment  || $is_owner):?>
                    <li class="new_reply_comment" style="display:none;" id="item_newComment_reply_<?php echo $comment['Comment']['id']?>">
                    <?php echo $this->Moo->getItemPhoto(array('User' => $cuser), array( 'prefix' => '50_square'), array('class' => 'user_avatar_small img_wrapper2'))?>
                    <div class="comment comment-form">

                        <?php echo $this->Form->textarea("item_commentReplyForm".$comment['Comment']['id'],array('class' => "commentBox showCommentReplyBtn", 'data-id' => $comment['Comment']['id'], 'placeholder' => __('Write a reply...'), 'rows' => 3 ), false) ?>
                        <?php $this->getEventManager()->dispatch(new CakeEvent('Element.activities.afterRenderCommentForm', $this,array('type' => 'item_commentReplyForm' ,'id'=>$comment['Comment']['id']))); ?>
                        <div id="item_commentReplyForm<?php echo $comment['Comment']['id'];?>-emoji" class="emoji-toggle"></div>
                        <?php if($this->request->is('ajax')): ?>
                            <script>
                                require(["jquery","mooToggleEmoji", "mooEmoji"], function($, mooToggleEmoji, mooEmoji) {
                                    mooToggleEmoji.init('item_commentReplyForm<?php echo $comment['Comment']['id'];?>');
                                    mooEmoji.init('item_commentReplyForm<?php echo $comment['Comment']['id'];?>');
                                });
                            </script>
                        <?php else: ?>
                            <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji', 'mooEmoji'),  'object' => array('$', 'mooToggleEmoji', 'mooEmoji'))); ?>
                                mooToggleEmoji.init('item_commentReplyForm<?php echo $comment['Comment']['id'];?>');
                                mooEmoji.init('item_commentReplyForm<?php echo $comment['Comment']['id'];?>');
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
                <?php endif;?>

                <?php if ($comment['Comment']['count_reply'] && empty($comment['Replies'])):?>
                <li>
                    <a class="item_reply_comment_viewmore" data-id="<?php echo $comment['Comment']['id']?>" data-close="<?php echo $is_close_comment?>" href="javascript:void(0);">
                        <?php
                			if ($comment['Comment']['count_reply'] == 1)
                				echo $comment['Comment']['count_reply']. ' '. __('Reply');
                			else
                				echo $comment['Comment']['count_reply']. ' '. __('Replies');
                		?>
                    </a>
                </li>
                <?php endif;?>
            </ul>
            <?php endif;?>

		</div>
	</li>
<?php
	endforeach;
endif;
?>

<?php if ( isset($data['cmt_id']) && $data['cmt_id'] && !empty($subject) ): ?>
    <li style="position: relative" id=""><i class="material-icons icon-small">question_answer</i><a href="<?php echo $subject[key($subject)]['moo_href'];?>" class="showAllComments"><?php echo __('View all comments')?></a></li>
<?php else:?>
    <?php if ($data['bIsCommentloadMore'] > 0): ?>
        <?php
        $more_link = '';
        if(!empty($activity)){
            $more_link = '/activity_id:'.$activity['Activity']['id'];
        }?>
            <?php if (empty($blockCommentId)): ?>
                <?php $this->Html->viewMore($data['more_comments'].'/is_close_comment:'.$is_close_comment.$more_link,'comments') ?>
            <?php else: ?>
                <?php $this->Html->viewMore($data['more_comments'].'/is_close_comment:'.$is_close_comment.'/id_content:'.$blockCommentId.$more_link,$blockCommentId) ?>
            <?php endif; ?>

    <?php endif; ?>
<?php endif; ?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooComment"], function($,mooComment) {
        mooComment.initOnCommentListing();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooComment'), 'object' => array('$', 'mooComment'))); ?>
mooComment.initOnCommentListing();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>