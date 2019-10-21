<?php $this->setCurrentStyle(4);?>
<?php if (!empty($comment)): ?>
    <?php if(empty($photoComment)): ?>
        <li id="comment_<?php echo $comment['ActivityComment']['id']?>"><?php echo $this->Moo->getItemPhoto(array('User' => $comment['User']), array( 'prefix' => '50_square'), array('class' => 'user_avatar_small img_wrapper2'))?>
        <div class="dropdown edit-post-icon comment-option">
            <a href="javascript:void(0)" data-toggle="dropdown" class="cross-icon">
                <i class="material-icons">more_vert</i>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <a href="javascript:void(0)" data-activity-comment-id="<?php echo $comment['ActivityComment']['id']?>" class="editActivityComment" >
                        <?php echo __('Edit Comment'); ?>
                    </a>
                </li>
                <li>
                    <a class=" removeActivityComment" data-activity-comment-id="<?php echo $comment['ActivityComment']['id']?>" href="javascript:void(0)"  >
                        <?php echo __('Delete Comment'); ?>
                    </a>
                </li>

            </ul>
        </div>
            <div class="comment hasDelLink">
                <?php echo $this->Moo->getName($comment['User'])?>
                <span class="main_comment" id="activity_feed_comment_text_<?php echo $comment['ActivityComment']['id']?>">
                    <?php echo $this->viewMore(h($comment['ActivityComment']['comment']),null, null, null, true, array('no_replace_ssl' => 1));?>
                    <?php if ($comment['ActivityComment']['thumbnail']):?>
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
                <div class="feed-time date"><?php echo __('Just now')?>
                    <a href="javascript:void(0);" class="reply_action activity_reply_comment_button" data-id="<?php echo $comment['ActivityComment']['id']?>" data-type="core_activity_comment">
                        <i class="material-icons">reply</i><?php echo __('Reply')?>
                    </a>
                            <?php
              $this->MooPopup->tag(array(
                     'href'=>$this->Html->url(array("controller" => "histories",
                                                    "action" => "ajax_show",
                                                    "plugin" => false,
                                                    'core_activity_comment',
                                                    $comment['ActivityComment']['id']
                                                )),
                     'title' => __('Show edit history'),
                     'innerHtml'=> __('Edited'),
                  'style' => empty($comment['ActivityComment']['edited']) ? 'display:none;' : '',
                  'id' => 'history_activity_comment_' . $comment['ActivityComment']['id'],
                  'class' => 'edit-btn',
                  'data-dismiss'=>'modal'
             ));
         ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeButton', $this,array('uid' => $uid,'comment' => array('id' =>  $comment['ActivityComment']['id'], 'like_count' => 0), 'item_type' => 'core_activity_comment' ))); ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeReview', $this,array('uid' => $uid,'comment' => array('id' =>  $comment['ActivityComment']['id'], 'like_count' => 0), 'item_type' => 'core_activity_comment' ))); ?>
<?php if(empty($hide_like)): ?>
                    &nbsp;<a href="javascript:void(0)" data-id="<?php echo $comment['ActivityComment']['id']?>" data-type="core_activity_comment" data-status="1" id="core_activity_comment_l_<?php echo $comment['ActivityComment']['id']?>" class="comment-thumb likeActivity"><i class="material-icons">thumb_up</i></a> <span id="core_activity_comment_like_<?php echo $comment['ActivityComment']['id']?>">0</span>
<?php endif; ?>
                    <?php if(empty($hide_dislike)): ?>
                    <a href="javascript:void(0)" data-id="<?php echo $comment['ActivityComment']['id']?>" data-type="core_activity_comment" data-status="0" id="core_activity_comment_l_<?php echo $comment['ActivityComment']['id']?>" class="comment-thumb likeActivity"><i class="material-icons">thumb_down</i></a> <span id="core_activity_comment_dislike_<?php echo $comment['ActivityComment']['id']?>">0</span>
                    <?php endif; ?>
                </div>
                <ul class="activity_comments comment_list" id="activitycomments_reply_<?php echo $comment['ActivityComment']['id']?>">
                    <li class="new_reply_comment" style="display:none;" id="activitynewComment_reply_<?php echo $comment['ActivityComment']['id']?>">
                        <?php echo $this->Moo->getItemPhoto(array('User' => $cuser), array( 'prefix' => '50_square'), array('class' => 'user_avatar_small img_wrapper2'))?>
                        <div class="comment">

                            <?php echo $this->Form->textarea("activitycommentReplyForm".$comment['ActivityComment']['id'],array('class' => "commentBox showCommentReplyBtn", 'data-id' => $comment['ActivityComment']['id'], 'placeholder' => __('Write a reply...'), 'rows' => 3 ), true) ?>
                            <?php $this->getEventManager()->dispatch(new CakeEvent('Element.activities.afterRenderCommentForm', $this,array('type' => 'activitycommentReplyForm' ,'id'=>$comment['ActivityComment']['id']))); ?>
                            <div id="activitycommentReplyForm<?php echo $comment['ActivityComment']['id'];?>-emoji" class="emoji-toggle"></div>
                            <?php if($this->request->is('ajax')): ?>
                                <script>
                                    require(["jquery","mooToggleEmoji"], function($, mooToggleEmoji) {
                                        mooToggleEmoji.init('activitycommentReplyForm<?php echo $comment['ActivityComment']['id'];?>');
                                    });
                                </script>
                            <?php else: ?>
                                <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji'),  'object' => array('$', 'mooToggleEmoji'))); ?>
                                mooToggleEmoji.init('activitycommentReplyForm<?php echo $comment['ActivityComment']['id'];?>');
                                <?php $this->Html->scriptEnd();  ?>
                            <?php endif; ?>

                            <div class="clear"></div>
                            <div style="display:block;" class="commentButton" id="activity_commentReplyButton_<?php echo $comment['ActivityComment']['id']?>">
                                <?php if ( !empty( $uid ) ): ?>
                                <input type="hidden" id="activitycomment_reply_image_<?php echo $comment['ActivityComment']['id'];?>" />
                                <div id="activitycomment_reply_button_attach_<?php echo $comment['ActivityComment']['id'];?>"></div>
                                <a href="javascript:void(0)"  class="btn btn-action activity_reply_comment" data-id="<?php echo $comment['ActivityComment']['id'];?>" data-type="core_activity_comment"><i class="material-icons">send</i></a>

                                <?php if($this->request->is('ajax')): ?>
                                <script type="text/javascript">
                                    require(["jquery","mooAttach"], function($,mooAttach) {
                                        mooAttach.registerAttachActivityCommentReplay(<?php echo $comment['ActivityComment']['id'];?>);
                                    });
                                </script>
                                <?php else: ?>
                                <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery','mooAttach'), 'object' => array('$', 'mooAttach'))); ?>
                                mooAttach.registerAttachActivityCommentReplay(<?php echo $comment['ActivityComment']['id'];?>);
                                <?php $this->Html->scriptEnd(); ?>
                                <?php endif; ?>

                                <?php else: ?>
                                <?php echo __('Please login or register')?>
                                <?php endif; ?>
                            </div>
                            <div id="activitycomment_reply_preview_image_<?php echo $comment['ActivityComment']['id'];?>"></div>
                        </div>
                    </li>
                </ul>
            </div>
        </li>
    <?php else: ?>
        <li id="itemcomment_<?php echo $photoComment['Comment']['id']?>"> 
        	<?php echo $this->Moo->getItemPhoto(array('User' => $photoComment['User']),array('prefix' => '50_square'), array('class' => 'user_avatar_small img_wrapper2'))?>

                <div class="dropdown edit-post-icon comment-option">
                    <a href="javascript:void(0)" data-toggle="dropdown" class="cross-icon">
                        <i class="material-icons">more_vert</i>
                    </a>
                    <ul class="dropdown-menu">
                            <li>
                                <a href="javascript:void(0)" data-id="<?php echo $photoComment['Comment']['id']?>" data-photo-comment="1" class="editItemComment">
                                    <?php echo __('Edit Comment'); ?>
                                </a>
                            </li>

                        <li>
                            <a class="removeItemComment" href="javascript:void(0)" data-photo-comment="1" data-id="<?php echo $photoComment['Comment']['id']?>" >
                                <?php echo __('Delete Comment'); ?>
                            </a>
                        </li>


                    </ul>
                </div>

            <div class="comment hasDelLink">
                <?php echo $this->Moo->getName($photoComment['User'])?>
                <span class="main_comment" id="photo_feed_comment_text_<?php echo $photoComment['Comment']['id']?>">
							<?php
                            echo $this->viewMore(h($photoComment['Comment']['message']));
                            ?>

                    <?php if ($photoComment['Comment']['thumbnail']):?>
                        <div class="comment_thumb">
                            <a href="<?php echo $this->Moo->getImageUrl($photoComment,array());?>">
                                <?php if($this->Moo->isGifImage($this->Moo->getImageUrl($photoComment,array()))) :  ?>
				                     <?php echo $this->Moo->getImage($photoComment,array('class'=>'gif_image'));?>
                                                <?php else: ?>
                                                        <?php echo $this->Moo->getImage($photoComment,array('prefix'=>'200'));?>
                                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endif;?>
                        </span>

                <div class="feed-time date">
                    <?php echo __('Just now')?>
                    <a href="javascript:void(0);" class="reply_action activity_reply_comment_button" data-id="<?php echo $photoComment['Comment']['id']?>" data-type="comment"><i class="material-icons">reply</i><?php echo __('Reply');?></a>
                    <?php
                    $this->MooPopup->tag(array(
                            'href'=>$this->Html->url(array("controller" => "histories",
                                        "action" => "ajax_show",
                                        "plugin" => false,
                                        'comment',
                                        $photoComment['Comment']['id']
                                    )),
                            'title' => __('Show edit history'),
                            'innerHtml'=> __('Edited'),
                            'style' => empty($photoComment['Comment']['edited']) ? 'display:none;' : '',
                            'id' => 'history_item_comment_'. $photoComment['Comment']['id'],
                            'class' => 'edit-btn',
                            'data-dismiss'=>'modal'
                        ));
                    ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeButton', $this,array('uid' => $uid, 'comment' => array('id' =>  $photoComment['Comment']['id'], 'like_count' => $photoComment['Comment']['like_count']), 'item_type' => 'comment' ))); ?>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.comments.renderLikeReview', $this,array('uid' => $uid,'comment' => array('id' =>  $photoComment['Comment']['id'], 'like_count' => $photoComment['Comment']['like_count']), 'item_type' => 'comment' ))); ?>
<?php if(empty($hide_like)): ?>
                    &nbsp;<a href="javascript:void(0)" data-id="<?php echo $photoComment['Comment']['id']?>" data-type="comment" data-status="1" id="comment_l_<?php echo $photoComment['Comment']['id']?>" class="comment-thumb likeActivity <?php if ( !empty( $uid ) && !empty( $activity_likes['item_comment_likes'][$photoComment['Comment']['id']] ) ): ?>active<?php endif; ?>"><i class="material-icons">thumb_up</i></a>
                    <?php
                    $this->MooPopup->tag(array(
                            'href'=>$this->Html->url(array("controller" => "likes",
                                        "action" => "ajax_show",
                                        "plugin" => false,
                                        'comment',
                                        $photoComment['Comment']['id'],
                                    )),
                            'title' => __('People Who Like This'),
                            'innerHtml'=> '<span id="comment_like_'.  $photoComment['Comment']['id'] . '">' . $photoComment['Comment']['like_count'] . '</span>',
                            'data-dismiss' => 'modal'
                        ));
                    ?>
<?php endif; ?>
                    <?php if(empty($hide_dislike)): ?>
                        <a href="javascript:void(0)" data-id="<?php echo $photoComment['Comment']['id']?>" data-type="comment" data-status="0" id="comment_d_<?php echo $photoComment['Comment']['id']?>" class="comment-thumb likeActivity <?php if ( !empty( $uid ) && isset( $activity_likes['item_comment_likes'][$photoComment['Comment']['id']] ) && $activity_likes['item_comment_likes'][$photoComment['Comment']['id']] == 0 ): ?>active<?php endif; ?>"><i class="material-icons">thumb_down</i></a>



                        <?php
                        $this->MooPopup->tag(array(
                                'href'=>$this->Html->url(array("controller" => "likes",
                                            "action" => "ajax_show",
                                            "plugin" => false,
                                            'comment',
                                            $photoComment['Comment']['id'],1
                                        )),
                                'title' => __('People Who Dislike This'),
                                'innerHtml'=> '<span id="comment_dislike_' .  $photoComment['Comment']['id'] . '">' . $photoComment['Comment']['dislike_count'] . '</span>',
                            ));
                        ?>
                    <?php endif; ?>
                </div>
                <ul class="activity_comments comment_list" id="comments_reply_<?php echo $photoComment['Comment']['id']?>">
                    <li class="new_reply_comment" style="display:none;" id="newComment_reply_<?php echo $photoComment['Comment']['id']?>">
                        <?php echo $this->Moo->getItemPhoto(array('User' => $cuser), array( 'prefix' => '50_square'), array('class' => 'user_avatar_small img_wrapper2'))?>
                        <div class="comment">

                            <?php echo $this->Form->textarea("commentReplyForm".$photoComment['Comment']['id'],array('class' => "commentBox showCommentReplyBtn", 'data-id' => $photoComment['Comment']['id'], 'placeholder' => __('Write a reply...') ), true) ?>
                            <?php $this->getEventManager()->dispatch(new CakeEvent('Element.activities.afterRenderCommentForm', $this,array('type' => 'commentReplyForm' ,'id'=>$photoComment['Comment']['id']))); ?>
                            <div id="commentReplyForm<?php echo $photoComment['Comment']['id'];?>-emoji" class="emoji-toggle"></div>
                            <?php if($this->request->is('ajax')): ?>
                                <script>
                                    require(["jquery","mooToggleEmoji"], function($, mooToggleEmoji) {
                                        mooToggleEmoji.init('commentReplyForm<?php echo $photoComment['Comment']['id'];?>');
                                    });
                                </script>
                            <?php else: ?>
                                <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji'),  'object' => array('$', 'mooToggleEmoji'))); ?>
                                mooToggleEmoji.init('commentReplyForm<?php echo $photoComment['Comment']['id'];?>');
                                <?php $this->Html->scriptEnd();  ?>
                            <?php endif; ?>

                            <div class="clear"></div>
                            <div style="display:block;" class="commentButton" id="commentReplyButton_<?php echo $photoComment['Comment']['id']?>">
                                <?php if ( !empty( $uid ) ): ?>
                                <input type="hidden" id="comment_reply_image_<?php echo $photoComment['Comment']['id'];?>" />
                                <div id="comment_reply_button_attach_<?php echo $photoComment['Comment']['id'];?>"></div>
                                <a href="javascript:void(0)"  class="btn btn-action activity_reply_comment" data-id="<?php echo $photoComment['Comment']['id'];?>" data-type="comment"><i class="material-icons">send</i></a>

                                <?php if($this->request->is('ajax')): ?>
                                <script type="text/javascript">
                                    require(["jquery","mooAttach"], function($,mooAttach) {
                                        mooAttach.registerAttachCommentReplay(<?php echo $photoComment['Comment']['id'];?>);
                                    });
                                </script>
                                <?php else: ?>
                                <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery','mooAttach'), 'object' => array('$', 'mooAttach'))); ?>
                                mooAttach.registerAttachCommentReplay(<?php echo $photoComment['Comment']['id'];?>);
                                <?php $this->Html->scriptEnd(); ?>
                                <?php endif; ?>

                                <?php else: ?>
                                <?php echo __('Please login or register')?>
                                <?php endif; ?>
                            </div>
                            <div id="comment_reply_preview_image_<?php echo $photoComment['Comment']['id'];?>"></div>
                        </div>
                    </li>
                </ul>
            </div>
        </li>
    <?php endif; ?>
<?php endif;?>