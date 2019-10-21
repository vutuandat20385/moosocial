<div class="comment_message">
    <?php
    $activityModel = MooCore::getInstance()->getModel('Activity');
    $parentFeed = $activityModel->findById($activity['Activity']['parent_id']);
    $link = unserialize($parentFeed['Activity']['params']);
	$url = ((isset($link['url']) && $link['url'] != 'http://')  ? $link['url'] : $activity['Activity']['content']);
    ?>
    <?php
    echo $this->viewMore(h($activity['Activity']['content']), null, null, null, true, array('no_replace_ssl' => 1));
    ?>
    <?php
    if (!empty($activity['UserTagging']['users_taggings']))
        $this->MooPeople->with($activity['UserTagging']['id'], $activity['UserTagging']['users_taggings']);
    ?>
    <div class="share-content">
        <div class="activity_feed_image">
            <?php echo $this->Moo->getItemPhoto(array('User' => $parentFeed['User']), array('prefix' => '50_square'), array('class' => 'img_wrapper2 user_avatar_large')) ?>
        </div>
        <div class="activity_feed_content">
            <div class="comment ">
                <div class="activity_text">
                    <?php echo $this->Moo->getName($parentFeed['User']) ?>
                </div>
                <div class="parent_feed_time">
                    <span class="date"><?php echo $this->Moo->getTime($parentFeed['Activity']['created'], Configure::read('core.date_format'), $utz) ?></span>
                </div>
            </div>
        </div>
        <div class="clear"></div>
		<div class="activity-title">
			<?php echo $this->viewMore(h($parentFeed['Activity']['content']),null, null, null, true, array('no_replace_ssl' => 1));?>
		</div>
        <div class="activity_feed_content_text">
            <?php if ( !empty( $link['type'] ) && $link['type'] == 'img'):?>
                <?php if ( !empty( $link['image'] ) ): ?>
                    <div class="activity_parse_img">
                        <img src="<?php echo $link['image'] ?>" class="img_wrapper2">
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if (!empty($link['image'])):
                            if ( strpos( $link['image'], 'http' ) === false ):
                                    $link_image = $this->request->webroot . 'uploads/links/' .  $link['image'] ;
                                else:
                                    $link_image = $link['image'];
                                endif;
                    ?>
                    <div class="activity_left">
                        <img src="<?php echo $link_image ?>" class="img_wrapper2">
                    </div>
                <?php endif; ?>
                <div class="<?php if (!empty($link['image'])): ?>activity_right <?php endif; ?>">
                    <a class="feed_title" href="<?php echo $url;?>" target="_blank" rel="nofollow">
                        <?php if(!empty($link['title'])): ?>
                            <strong><?php echo h($link['title']) ?></strong>
                        <?php endif; ?>
                    </a>

                    <?php
                    if (!empty($link['description']))
                        echo '<div class=" comment_message feed_detail_text">' . ($this->Text->truncate($link['description'], 150, array('exact' => false))) . '</div>';
                    ?>
                </div>
            <?php endif;?>
        </div>
    </div>
</div>