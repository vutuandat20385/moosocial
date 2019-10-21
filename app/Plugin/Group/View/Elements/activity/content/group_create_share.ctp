<?php
$groupHelper = MooCore::getInstance()->getHelper('Group_Group');
?>


    <div class="comment_message">
        <?php echo $this->viewMore(h($activity['Activity']['content']), null, null, null, true, array('no_replace_ssl' => 1)); ?>
        <?php if(!empty($activity['UserTagging']['users_taggings'])) $this->MooPeople->with($activity['UserTagging']['id'], $activity['UserTagging']['users_taggings']); ?>
    </div>


<div class="share-content" >
    <?php
    $activityModel = MooCore::getInstance()->getModel('Activity');
    $parentFeed = $activityModel->findById($activity['Activity']['parent_id']);
    $group = MooCore::getInstance()->getItemByType($parentFeed['Activity']['item_type'], $parentFeed['Activity']['item_id']);
    ?>

    <div class="activity_feed_content">
    
        <div class="activity_text">
            <?php echo $this->Moo->getName($parentFeed['User']) ?>
            <?php echo __('created a new group'); ?>
        </div>

        <div class="parent_feed_time">
            <span class="date"><?php echo $this->Moo->getTime($parentFeed['Activity']['created'], Configure::read('core.date_format'), $utz) ?></span>
        </div>
        
    </div>
    <div class="clear"></div>
    <div class="activity_feed_content_text">
        <div class="activity_left">
            <a href="<?php echo $group['Group']['moo_href'] ?>">
                <img src="<?php echo $groupHelper->getImage($group, array('prefix' => '150_square')) ?>" class="img_wrapper2" />
            </a>
        </div>
        <div class="activity_right ">
            <a class="feed_title" href="<?php echo $group['Group']['moo_href'] ?>"><?php echo $group['Group']['moo_title'] ?></a>
            <div class="comment_message feed_detail_text">
                <?php echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $group['Group']['description'])), 200, array('exact' => false)), Configure::read('Group.group_hashtag_enabled')) ?>

            </div>
        </div>
    </div>
    <div class="clear"></div>
</div>
