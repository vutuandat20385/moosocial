<div class="comment_message">
    <?php
    
    $blogHelper = MooCore::getInstance()->getHelper('Blog_Blog');
    ?>

        <?php echo $this->viewMore(h($activity['Activity']['content']), null, null, null, true, array('no_replace_ssl' => 1)); ?>
        <?php if(!empty($activity['UserTagging']['users_taggings'])) $this->MooPeople->with($activity['UserTagging']['id'], $activity['UserTagging']['users_taggings']); ?>
  
    <div class="share-content">
        <?php
        $activityModel = MooCore::getInstance()->getModel('Activity');
        $parentFeed = $activityModel->findById($activity['Activity']['parent_id']);
        $blog = MooCore::getInstance()->getItemByType($parentFeed['Activity']['item_type'], $parentFeed['Activity']['item_id']);
        ?>

         <div class="activity_feed_content">
            
            <div class="activity_text">
                <?php echo $this->Moo->getName($parentFeed['User']) ?>
                <?php echo __('created a new blog entry'); ?>
            </div>
            
            <div class="parent_feed_time">
                <span class="date"><?php echo $this->Moo->getTime($parentFeed['Activity']['created'], Configure::read('core.date_format'), $utz) ?></span>
            </div>
            
        </div>
        <div class="clear"></div>
        <div class="activity_feed_content_text">
        <div class="activity_left">
            <a href="<?php echo $blog['Blog']['moo_href']?>">
            <img width="150" class="thum_activity" src="<?php echo $blogHelper->getImage($blog, array('prefix' => '150_square')) ?>" />
            </a>
        </div>

        <div class="activity_right ">
            <div class="activity_header">
                <a href="<?php echo $blog['Blog']['moo_href'] ?>"><?php echo $blog['Blog']['moo_title'] ?></a>
            </div>
            <?php echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $blog['Blog']['body'])), 200, array('exact' => false)), Configure::read('Blog.blog_hashtag_enabled')) ?>
        </div>
        </div>
        <div class="clear"></div>
    </div>

</div>

