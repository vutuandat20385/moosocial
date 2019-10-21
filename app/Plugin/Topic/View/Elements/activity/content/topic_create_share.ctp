<?php
$topicHelper = MooCore::getInstance()->getHelper('Topic_Topic');
?>
<div class="comment_message">
    <?php echo $this->viewMore(h($activity['Activity']['content']), null, null, null, true, array('no_replace_ssl' => 1)); ?>
    <?php if (!empty($activity['UserTagging']['users_taggings'])) $this->MooPeople->with($activity['UserTagging']['id'], $activity['UserTagging']['users_taggings']); ?>
</div>
<div class="share-content">
    <div class="parent-feed">
        <?php
        $activityModel = MooCore::getInstance()->getModel('Activity');
        $parentFeed = $activityModel->findById($activity['Activity']['parent_id']);
        $topic = MooCore::getInstance()->getItemByType($parentFeed['Activity']['item_type'], $parentFeed['Activity']['item_id']);
        ?>

        <div class="activity_feed_content">
         
            <div class="activity_text">
               
                    <?php echo $this->Moo->getName($parentFeed['User']) ?>
                    <?php
                    $subject = MooCore::getInstance()->getItemByType($parentFeed['Activity']['type'], $parentFeed['Activity']['target_id']);
                    $name = key($subject);
                    ?>
                    <?php if ($parentFeed['Activity']['target_id']): ?>

                        <?php $show_subject = MooCore::getInstance()->checkShowSubjectActivity($subject); ?>

                        <?php if ($show_subject): ?>
                            &rsaquo; <a href="<?php echo $subject[$name]['moo_href'] ?>"><?php echo $subject[$name]['moo_title'] ?></a>
                        <?php else: ?>
                            <?php echo __('created a new topic'); ?>
                        <?php endif; ?>

                    <?php else: ?>
                        <?php echo __('created a new topic'); ?>
                    <?php endif; ?>
                </div>

                <div class="parent_feed_time">
                    <span class="date"><?php echo $this->Moo->getTime($parentFeed['Activity']['created'], Configure::read('core.date_format'), $utz) ?></span>
                </div>
            
        </div>
        <div class="clear"></div>
        <div class="activity_feed_content_text">
            <div class="activity_left">
                <a href="<?php echo $topic['Topic']['moo_href'] ?>">
                    <img width="150" class="thum_activity" src="<?php echo $topicHelper->getImage($topic, array('prefix' => '150_square')) ?>"/>
                </a>
            </div>
            <div class="activity_right ">
                <div class="activity_header">
                    <a class="feed_title" href="<?php if (!empty($topic['Topic']['group_id'])): ?><?php echo $this->request->base ?>/groups/view/<?php echo $topic['Topic']['group_id'] ?>/topic_id:<?php echo $topic['Topic']['id'] ?><?php else: ?><?php echo $this->request->base ?>/topics/view/<?php echo $topic['Topic']['id'] ?>/<?php echo seoUrl($topic['Topic']['title']) ?><?php endif; ?>"><b><?php echo $topic['Topic']['title'] ?></b></a>
                </div>
                <div class="feed_detail_text">
                    <?php echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $topic['Topic']['body'])), 200, array('exact' => false)), Configure::read('Topic.topic_hashtag_enabled')) ?>
                </div>
            </div>
            <div class="clear"></div>
       </div>
    </div>
</div>
