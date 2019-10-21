<?php
$group = $object;
$groupHelper = MooCore::getInstance()->getHelper('Group_Group');
?>

    <div class="activity_left">
        <a target="_blank" href="<?php echo $group['Group']['moo_href'] ?>">
            <img src="<?php echo $groupHelper->getImage($group, array('prefix' => '150_square')) ?>" class="img_wrapper2" />
        </a>
    </div>
    <div class="activity_right ">
        <a target="_blank" class="feed_title" href="<?php echo $group['Group']['moo_href'] ?>"><?php echo $group['Group']['moo_title'] ?></a>
        <div class="comment_message feed_detail_text">
            <?php echo $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $group['Group']['description'])), 200, array('exact' => false)), Configure::read('Group.group_hashtag_enabled')) ?>

        </div>
    </div>

