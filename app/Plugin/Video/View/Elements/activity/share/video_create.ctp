<?php $videoHelper = MooCore::getInstance()->getHelper('Video_Video'); ?>
<div class="">
    <div class="activity_feed_content">
        
            <?php echo $this->Moo->getName($object['User'], true, true) ?>
            <?php
            $subject = MooCore::getInstance()->getItemByType($activity['Activity']['type'], $activity['Activity']['target_id']);
            $name = key($subject);
            ?>
            <?php if ($activity['Activity']['target_id']): ?>
                <?php $show_subject = MooCore::getInstance()->checkShowSubjectActivity($subject); ?>
                <?php if ($show_subject): ?>
                    &rsaquo; <a target="_blank" href="<?php echo $subject[$name]['moo_href'] ?>"><?php echo $subject[$name]['moo_title'] ?></a>
                <?php else: ?>
                    <?php echo __('shared a new video'); ?>
                <?php endif; ?>
            <?php else: ?>

                <?php echo __('shared a new video'); ?>

            <?php endif; ?>
            <div class="feed_time">
                <span class="date"><?php echo $this->Moo->getTime($object['Video']['created'], Configure::read('core.date_format'), $utz) ?></span>
            </div>
    </div>
    <div class="clear"></div>
    <div class="activity_feed_content_text">
        <div class="video-feed-content">
            <?php
            $flag_enable = false;
            if(in_array('video_view',$uacos))
            {
            	$flag_enable = true;
            	echo $this->element('Video./video_snippet', array('video' => $object));
            }
            else
            {
            	echo $this->element('Video./video_thumb',array('video' => $object));
            }
            ?>
        </div>
        <div class="video-feed-info video_feed_content">
            <div class="video-title" >
                <a
                    <?php if(!$flag_enable):?>
                        class="feed_title"
                        data-target="#portlet-config" data-toggle="modal" href="<?php echo $object['Video']['moo_href']?>"
                    <?php else:?>
                        class="feed_title"
                        href="<?php if ( !empty( $object['Video']['group_id'] ) ): ?><?php echo $this->request->base?>/groups/view/<?php echo $object['Video']['group_id']?>/video_id:<?php echo $object['Video']['id']?><?php else: ?><?php echo $this->request->base?>/videos/view/<?php echo $object['Video']['id']?>/<?php echo seoUrl($object['Video']['title'])?><?php endif; ?>"
                    <?php endif;?>
                >
                    <?php echo $object['Video']['title']?>
                </a>
            </div>

            <div class="video-description comment_message feed_detail_text">
                <?php echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $object['Video']['description'])), 200, array('exact' => false)), Configure::read('Video.video_hashtag_enabled')) ?>

            </div>
        </div>
    </div>
</div>