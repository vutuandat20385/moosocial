<?php
$blog = $object;
$blogHelper = MooCore::getInstance()->getHelper('Blog_Blog');
?>
    <div class="activity_feed_content">
       
        <div class="activity_text">
            <?php echo $this->Moo->getName($blog['User'], true, true) ?>
            <?php echo __('created a new blog entry'); ?>
        </div>
        
        <div class="parent_feed_time">
            <span class="date"><?php echo $this->Moo->getTime($blog['Blog']['created'], Configure::read('core.date_format'), $utz) ?></span>
        </div>
        
    </div>
    <div class="clear"></div>
    <div class="activity_feed_content_text">
    <div class="activity_left">
        <a target="_blank" href="<?php echo $blog['Blog']['moo_href'] ?>">
            <img width="150" class="thum_activity" src="<?php echo $blogHelper->getImage($blog, array('prefix' => '150_square')) ?>" />
        </a>
    </div>
    <div class="activity_right ">
        <div class="activity_header">
            <a target="_blank" href="<?php echo $blog['Blog']['moo_href'] ?>"><?php echo $blog['Blog']['moo_title'] ?></a>
        </div>
        <?php echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $blog['Blog']['body'])), 200, array('exact' => false)), Configure::read('Blog.blog_hashtag_enabled')) ?>
    </div>
    </div>
    <div class="clear"></div>
