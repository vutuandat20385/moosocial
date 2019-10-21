<?php
$blog = $object;
$blogModel = $blogHelper = MooCore::getInstance()->getHelper('Blog_Blog');
?>

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
    <div class="clear"></div>
