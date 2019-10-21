<?php
$photoHelper = MooCore::getInstance()->getHelper('Photo_Photo');
?>
<div class="photo-comment-feed">
    <div class="comment_message">
        <div class="comment-truncate" data-more-text="<?php echo __( 'Show More')?>" data-less-text="<?php echo __( 'Show Less')?>">
            <?php echo $this->Moo->cleanHtml($this->Text->convert_clickable_links_for_hashtags(h($activity['Activity']['content']), Configure::read('Photo.photo_hashtag_enabled')))?>
        </div>
    </div>
    <div class="photo_item_comment">
        <a class="photoModal" href="<?php echo $object['Photo']['moo_href']?>#content"><img src="<?php echo $photoHelper->getImage($object, array('prefix' => '850'));?>" class="single_img wall_photo_comment"></a>
    </div>
</div>