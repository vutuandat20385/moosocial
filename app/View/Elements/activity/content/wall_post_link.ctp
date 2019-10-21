<?php
$link = unserialize($activity['Activity']['params']);
$url = (isset($link['url']) ? $link['url'] : $activity['Activity']['content']);
?>
<div class="activity-title">
	<?php echo $this->viewMore(h($activity['Activity']['content']),null, null, null, true, array('no_replace_ssl' => 1));?>
	<?php if(!empty($activity['UserTagging']['users_taggings'])) $this->MooPeople->with($activity['UserTagging']['id'], $activity['UserTagging']['users_taggings']); ?>
</div>
 <?php  if ( !empty( $link['title'] ) ):?>
<div class="activity_item">

    <?php if ( !empty( $link['image'] ) ): ?>
    <div class="activity_left">
        <?php 
            if ( strpos( $link['image'], 'http' ) === false ):
                                $link_image = $this->storage->getUrl($activity['Activity']["id"],'',$link['image'],"links") ;//$this->request->webroot . 'uploads/links/' .  $link['image'] ;
                            else:
                                $link_image = $link['image'];
                            endif;
        ?>
    <img src="<?php echo $link_image ?>" class="img_wrapper2">
    </div>
    <?php endif; ?>
    <div class="<?php if ( !empty( $link['title'] ) ): ?>activity_right <?php endif; ?>">
        <a class="feed_title" href="<?php echo $url;?>" target="_blank" rel="nofollow">
            <strong><?php echo h($link['title'])?></strong>
        </a>
        
         <?php
        if ( !empty( $link['description'] ) )
            echo '<div class=" comment_message feed_detail_text">' . ($this->Text->truncate($link['description'], 150, array('exact' => false))) . '</div>';
        ?>
    </div>
</div>
<?php elseif ( !empty( $link['type'] ) && $link['type'] == 'img' && !empty( $link['image'])):?>
    <div class="activity_item">
        <div class="activity_parse_img">
            <img src="<?php echo $link['image'] ?>" class="img_wrapper2">
        </div>
    </div>
<?php endif; ?>