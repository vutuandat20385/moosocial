<?php
if (Configure::read('UploadVideo.uploadvideo_enabled')) {
    echo $this->Html->css(array('video-js/video-js'), null, array('inline' => false));
    echo $this->Html->script(array('video-js/video-js'), array('inline' => false));
}

$videoHelper = MooCore::getInstance()->getHelper('Video_Video');

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
<script>
    window.history.pushState({}, "", "<?php echo $video['Video']['moo_href'] ?>");

    require(["jquery","mooVideo","mooComment"], function($, mooVideo,mooComment) {
        mooVideo.initOnView();
        mooComment.initReplyCommentItem();
        mooComment.initCloseComment();
    });
</script>
<div class="bar-content">
    <div>
        <div class="video-detail">
            <?php echo $this->element('Video./video_snippet', array('video' => $video)); ?>

        </div>

        <div class="content_center full_content p_m_10 video_group_detail">
                <?php if ($uid == $video['Video']['user_id'] || ( !empty($cuser['Role']['is_admin']) ) || in_array($uid, $admins) ): ?>
                <div class="list_option">
                    <div class="dropdown">
                         <button id="dLabel" type="button" data-toggle="dropdown" aria-haspopup="true" role="button" aria-expanded="false">
                            <i class="material-icons">more_vert</i>
                        </button>
                         <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">

                                <li>
                                    <?php
                                    $this->MooPopup->tag(array(
                                           'href'=>$this->Html->url(array("controller" => "videos",
                                                                          "action" => "create",
                                                                          "plugin" => 'video',
                                                                          $video['Video']['id']

                                                                      )),
                                           'title' => __('Edit Video'),
                                           'innerHtml'=> __( 'Edit Video'),
                                   ));
                               ?>
                                </li>
                                <li>
                                	<a href="javascript:void(0)" class="deleteVideo" data-id="<?php echo $video['Video']['id']?>">
                                		<?php echo __('Delete Video');?>
                                	</a>
                                </li>

                         </ul>
                    </div>
                </div>
                <?php endif; ?>
                <div class="title_center">
                    <h2><?php echo $video['Video']['title']?></h2>
                </div>


                <div style="margin:10px 0">
                        <?php echo $this->Moo->formatText( $video['Video']['description'], false, true , array('no_replace_ssl' => 1))?>
                </div>
                <span class="date"><?php echo __( 'Posted by %s', $this->Moo->getName($video['User']))?> <?php echo $this->Moo->getTime($video['Video']['created'], Configure::read('core.date_format'), $utz)?></span><br />
                <div class="likes bottom_options">
                        <?php echo $this->element('likes', array('item' => $video['Video'], 'type' => 'Video_Video', 'hide_container' => true)); ?>



                </div>
        </div>
    </div>
</div>
<div class="bar-content full_content p_m_10">
    <div class="content_center">
        <?php if ($video['Group']['moo_privacy'] == PRIVACY_PUBLIC): ?>
            <?php echo $this->element('likes', array('shareUrl' => $this->Html->url(array(
                    'plugin' => false,
                    'controller' => 'share',
                    'action' => 'ajax_share',
                    'Video_Video',
                    'id' => $video['Video']['id'],
                    'type' => 'video_item_detail'
                ), true), 'item' => $video['Video'], 'type' => $video['Video']['moo_type'])); ?>
        <?php else: ?>
            <?php echo $this->element('likes', array('doNotShare' => true, 'shareUrl' => $this->Html->url(array(
                    'plugin' => false,
                    'controller' => 'share',
                    'action' => 'ajax_share',
                    'Video_Video',
                    'id' => $video['Video']['id'],
                    'type' => 'video_item_detail'
                ), true), 'item' => $video['Video'], 'type' => $video['Video']['moo_type'])); ?>
        <?php endif; ?>
    </div>
</div>
<div class="bar-content full_content p_m_10">
    <div class="content_center content_comment">
            <h2><?php echo __( 'Comments (%s)', $video['Video']['comment_count'])?></h2>

            <?php if ($is_owner ): ?>
                <a class="closeComment" data-id="<?php echo $video['Video']['id']?>" data-type="<?php echo $video['Video']['moo_type']?>" data-close="<?php echo $is_close_comment;?>" href="javascript:void(0)" >
                    <?php echo $title; ?>
                </a>
            <?php endif; ?>

            <?php if (Configure::read('core.comment_sort_style') == COMMENT_RECENT): ?>

            <?php
                if ( !isset( $is_member ) || $is_member  ){
                    if($is_close_comment && !$is_owner){
                        echo '<div class="closed-comment">'.__('%s turn off commenting for this post', $this->Moo->getName($item_close_comment['User'])). '</div>';
                    }else {
                        echo $this->element('comment_form', array('target_id' => $video['Video']['id'], 'type' => 'Video_Video'));
                    }
                } else {
                    echo __('This a group video. Only group members can leave comment');
                }
            ?>
            <ul class="list6 comment_wrapper" id="comments">
            <?php echo $this->element('comments');?>
            </ul>

            <?php elseif(Configure::read('core.comment_sort_style') == COMMENT_CHRONOLOGICAL): ?>

            <ul class="list6 comment_wrapper" id="comments">
            <?php echo $this->element('comments_chrono');?>
            </ul>
            <?php
                if ( !isset( $is_member ) || $is_member  ) {
                    if($is_close_comment && !$is_owner){
                        echo '<div class="closed-comment">'.__('%s turn off commenting for this post', $this->Moo->getName($item_close_comment['User'])). '</div>';
                    }else {
                        echo $this->element('comment_form', array('target_id' => $video['Video']['id'], 'type' => 'Video_Video'));
                    }
                }else {
                    echo __('This a group video. Only group members can leave comment');
                }
            ?>

            <?php endif; ?>
    </div>
</div>
