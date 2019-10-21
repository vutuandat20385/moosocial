<script type="text/javascript">
    require(["jquery","mooGroup","mooComment"], function($, mooGroup, mooComment) {
        mooGroup.initOnAjaxViewTopic('<?php echo $topic['Topic']['moo_href']?>');
        mooComment.initReplyCommentItem();
        mooComment.initCloseComment();
    });
</script>
<?php
    if(!empty($item_close_comment)){
        $title =  __('Open Comment');
        $is_close_comment = 1;
    }else{
        $title =   __('Close Comment');
        $is_close_comment = 0;
    }
    if ((!empty($admins) && !empty($cuser) && in_array($cuser['id'], $admins)) || (!empty($cuser) && $cuser['Role']['is_admin']) ){
        $is_owner = 1;
    }else{
        $is_owner = 0;
    }

?>
<div class="bar-content full_content p_m_10">
    <div class="content_center">
        <div class="title_center">
             <h2><?php echo $topic['Topic']['title']; ?></h2>
        </div>
        <div class="date"><?php echo __( 'Posted by %s', $this->Moo->getName($topic['User']))?> <?php echo $this->Moo->getTime($topic['Topic']['created'], Configure::read('core.date_format'), $utz)?></div>
         <div class="bottom_options likes">
            <?php if (!empty($uid)): ?>
             <span class="dropdown" data-buttons="dropdown">
                <a data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#" class="button button-tiny"><?php echo __( 'Actions')?> <i class="material-icons">more_vert</i></a>
                <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                    <?php if ($uid == $topic['Topic']['user_id'] || ( !empty($cuser) && $cuser['Role']['is_admin'] ) || in_array($uid, $admins) ): ?>
                    <li><a href='javascript:void(0)' class="ajaxLoadTopicEdit" data-url="<?php echo $this->request->base?>/topics/group_create/<?php echo $topic['Topic']['id']?>"><?php echo __( 'Edit Topic')?></a></li>
                    <li><a href="javascript:void(0);" class="deleteTopic" data-id="<?php echo $topic['Topic']['id']?>" data-group="<?php echo $this->request->data['group_id']?>"><?php echo  __( 'Delete') ?></a></li>
                    <?php endif; ?>
                    <?php if (!empty($cuser['Role']['is_admin']) || in_array($uid, $admins) ): ?>
                        <?php if ( !$topic['Topic']['pinned'] ): ?>
                        <li><a href="<?php echo $this->request->base?>/topics/do_pin/<?php echo $topic['Topic']['id']?>"><?php echo __( 'Pin Topic')?></a></li>
                        <?php else: ?>
                        <li><a href="<?php echo $this->request->base?>/topics/do_unpin/<?php echo $topic['Topic']['id']?>"><?php echo __( 'Unpin Topic')?></a></li>
                        <?php endif; ?>

                        <?php if ( !$topic['Topic']['locked'] ): ?>
                        <li><a href="<?php echo $this->request->base?>/topics/do_lock/<?php echo $topic['Topic']['id']?>"><?php echo __( 'Lock Topic')?></a></li>
                        <?php else: ?>
                        <li><a href="<?php echo $this->request->base?>/topics/do_unlock/<?php echo $topic['Topic']['id']?>"><?php echo __( 'Unlock Topic')?></a></li>
                        <?php endif; ?>     
                    <?php endif; ?>
                    <li>
                        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "reports",
                                            "action" => "ajax_create",
                                            "plugin" => false,
                                            'Topic_Topic',
                                            $topic['Topic']['id']
                                        )),
             'title' => __( 'Report Topic'),
             'innerHtml'=> __( 'Report Topic'),
     ));
 ?>
                          </li>
                          
                </ul>   
            </span>   
            <?php endif; ?>  

        </div>
    <div class="clear"></div>
    <div class="comment_message" style="margin:5px 0">
        <?php echo $this->Moo->cleanHtml($this->Text->convert_clickable_links_for_hashtags( $topic['Topic']['body'] , Configure::read('Topic.topic_hashtag_enabled')))?>

        <?php if ( !empty( $pictures ) ): ?>
            <div class='topic_attached_file'>
                <div class="date"><?php echo __( 'Attached Images')?></div>
                <ul class="list4 p_photos ">
                    <?php foreach ($pictures as $p): ?>
                        <li class='col-xs-6 col-ms-4 col-md-3' >
                            <div class="p_2">
                                <a style="background-image:url(<?php echo $this->request->webroot?>uploads/attachments/t_<?php echo $p['Attachment']['filename']?>)" href="<?php echo $this->request->webroot?>uploads/attachments/<?php echo $p['Attachment']['filename']?>" class="attached-image layer_square"></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class='clear'></div>
            </div>
        <?php endif; ?>

        <?php if ( !empty( $files ) ): ?>
        <div style="margin:10px 0">
            <div class="date"><?php echo __( 'Attached Files')?></div>
            <ul class="list6 list6sm">
            <?php foreach ($files as $attachment): ?>     
                <li><i class="material-icons">attach_file</i><a href="<?php echo $this->request->base?>/attachments/download/<?php echo $attachment['Attachment']['id']?>"><?php echo $attachment['Attachment']['original_filename']?></a> <span class="date">(<?php echo __n('%s download', '%s downloads', $attachment['Attachment']['downloads'], $attachment['Attachment']['downloads'] )?>)</span></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    

   
</div>
</div>
<div class="bar-content full_content p_m_10">

    <div class="content_center">
        <?php if ($topic['Group']['moo_privacy'] == PRIVACY_PUBLIC): ?>
            <?php echo $this->element('likes', array('shareUrl' => $this->Html->url(array(
                                        'plugin' => false,
                                        'controller' => 'share',
                                        'action' => 'ajax_share',
                                        'Topic_Topic',
                                        'id' => $topic['Topic']['id'],
                                        'type' => 'topic_item_detail'
                                    ), true), 'item' => $topic['Topic'], 'type' => 'Topic_Topic', 'hide_container' => false)); ?>
        <?php else: ?>
            <?php echo $this->element('likes', array('doNotShare' => true, 'shareUrl' => $this->Html->url(array(
                                        'plugin' => false,
                                        'controller' => 'share',
                                        'action' => 'ajax_share',
                                        'Topic_Topic',
                                        'id' => $topic['Topic']['id'],
                                        'type' => 'topic_item_detail'
                                    ), true), 'item' => $topic['Topic'], 'type' => 'Topic_Topic', 'hide_container' => false)); ?>
        <?php endif; ?>
        
    </div>
</div>
<div class="bar-content full_content p_m_10">
    <div class="content_center content_comment">
        <h2><?php echo __( 'Replies (%s)', $topic['Topic']['comment_count'])?></h2>

        <?php if ($is_owner ): ?>
            <a class="closeComment" data-id="<?php echo $topic['Topic']['id']?>" data-type="<?php echo $topic['Topic']['moo_type']?>" data-close="<?php echo $is_close_comment;?>" href="javascript:void(0)" >
                <?php echo $title; ?>
            </a>
        <?php endif; ?>

        <?php if (Configure::read('core.comment_sort_style') == COMMENT_RECENT): ?>
        
        <?php 
        if ( !isset( $is_member ) || $is_member  )
            if ( $topic['Topic']['locked'] ) {
                echo '<i class="material-icons icon-small">lock</i> ' . __('This topic has been locked');
            }else if($is_close_comment && !$is_owner){
                echo '<div class="closed-comment">'.__('%s turn off commenting for this post', $this->Moo->getName($item_close_comment['User'])). '</div>';
            }
            else {
                echo $this->element('comment_form', array('target_id' => $topic['Topic']['id'], 'type' => 'Topic_Topic'));
            }
        else
                echo __( 'This a group topic. Only group members can leave comment');		
        ?>
        <ul class="list6 comment_wrapper" id="comments">
        <?php echo $this->element('comments');?>
        </ul>
        
        <?php elseif(Configure::read('core.comment_sort_style') == COMMENT_CHRONOLOGICAL): ?>
        
        <ul class="list6 comment_wrapper" id="comments">
        <?php echo $this->element('comments_chrono');?>
        </ul>
        <?php 
        if ( !isset( $is_member ) || $is_member  )
            if ( $topic['Topic']['locked'] ){
                echo '<i class="material-icons icon-small">lock</i> ' . __( 'This topic has been locked');
            }else if($is_close_comment && !$is_owner){
                echo '<div class="closed-comment">'.__('%s turn off commenting for this post', $this->Moo->getName($item_close_comment['User'])). '</div>';
            }else {
                echo $this->element('comment_form', array('target_id' => $topic['Topic']['id'], 'type' => 'Topic_Topic'));
            }
        else
                echo __( 'This a group topic. Only group members can leave comment');		
        ?>
        
        <?php endif; ?>
    </div>
</div>