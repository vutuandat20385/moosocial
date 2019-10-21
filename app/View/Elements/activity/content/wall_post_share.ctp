<div class="comment_message">
    <?php
    echo $this->viewMore(h($activity['Activity']['content']), null, null, null, true, array('no_replace_ssl' => 1));
    ?>
    <?php
    if (!empty($activity['UserTagging']['users_taggings']))
        $this->MooPeople->with($activity['UserTagging']['id'], $activity['UserTagging']['users_taggings']);
    ?>
    <div class="share-content">
        <div class="parent-feed">
            <?php
            $activityModel = MooCore::getInstance()->getModel('Activity');
            $parentFeed = $activityModel->findById($activity['Activity']['parent_id']);
            ?>

            <div class="activity_feed_content">
                
                    <?php echo $this->Moo->getName($parentFeed['User']) ?>
                    <?php if ($parentFeed['Activity']['target_id']): ?>
                        <?php
                        $subject = MooCore::getInstance()->getItemByType($parentFeed['Activity']['type'], $parentFeed['Activity']['target_id']);

                        list($plugin, $name) = mooPluginSplit($parentFeed['Activity']['type']);
                        $show_subject = MooCore::getInstance()->checkShowSubjectActivity($subject);

                        if ($show_subject):
                            ?>
                            &rsaquo; <a href="<?php echo $subject[$name]['moo_href'] ?>"><?php echo $subject[$name]['moo_title'] ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="feed_time">
                        <span class="date"><?php echo $this->Moo->getTime($parentFeed['Activity']['created'], Configure::read('core.date_format'), $utz) ?></span>
                    </div>
               
            </div>

            <div class="comment_message">
                <?php echo $this->viewMore(h($parentFeed['Activity']['content']), null, null, null, true, array('no_replace_ssl' => 1)); ?>
                <?php
                if (!empty($parentFeed['UserTagging']['users_taggings']))
                    $this->MooPeople->with($parentFeed['UserTagging']['id'], $parentFeed['UserTagging']['users_taggings']);
                ?>
            </div>
        </div>

        <div class="">
            <?php if ($parentFeed['Activity']['item_type']): ?>
                <?php
                list($plugin, $name) = mooPluginSplit($parentFeed['Activity']['item_type']);
                ?>
                <?php echo $this->element('activity/content/' . strtolower($name) . '_post_feed', array('activity' => $parentFeed, 'object' => $object, 'had_comment_message' => 1 ), array('plugin' => $plugin)); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
