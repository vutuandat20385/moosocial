
<div class="<?php echo $class_feed?>">
    <?php if ( $check_post_status): ?>
	    <div id="status_box" class="statusHome" style="display: none;">
			<?php  echo $this->element( 'activity_form',array('video_categories' => $video_categories, 'type'=>$subject_type,'text'=>$text,'target_id'=>$target_id)); ?>
			<div class="clear"></div>
	    </div>
    <?php endif; ?>
    
    <ul class="list6 comment_wrapper" id="list-content">
        <?php if (Configure::read('core.comment_sort_style') == COMMENT_RECENT): ?>
        <?php echo $this->element('activities', array('check_post_status' => $check_post_status, 'bIsACtivityloadMore' => $bIsACtivityloadMore, 'more_url' => $url_more,'activity_likes'=>$activity_likes,'activities'=>$activities, 'admins' => $admins)); ?>
        <?php elseif(Configure::read('core.comment_sort_style') == COMMENT_CHRONOLOGICAL): ?>
        <?php echo $this->element('activities_chrono', array('check_post_status' => $check_post_status, 'bIsACtivityloadMore' => $bIsACtivityloadMore, 'more_url' => $url_more,'activity_likes'=>$activity_likes,'activities'=>$activities, 'admins' => $admins)); ?>
        <?php endif; ?>
    </ul>
</div>
