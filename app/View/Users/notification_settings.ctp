<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
<div class="bar-content">
    <div class="profile-info-menu">
        <?php echo $this->element('profilenav', array("cmenu" => "notification_settings"));?>
    </div>
</div>
<?php $this->end(); ?>
<?php
    $array = array(
        'comment_item' => __('When people comment on things I posted such as status, blog, event....'),
        'reply_comment'=> __('When people reply on things I commented'),
        'reply_of_reply' => __('When people reply on things I replied to'),
        'like_item' => __('When people like things I posted such as status, topic, blog...'),
        'comment_of_comment' => __('When people comment on things I commented such as status, blog, event....'),
        'share_item' => __('When people share things I posted such as status, topic, blog...'),
        'post_profile' => __('When people post on my profile'),
        'tag_photo' => __('When people tagged in photos'),
        'comment_tag_photo' => __('When people comment on photo that someone tagged me'),
        'like_tag_photo' => __('When people like photo that someone tagged me'),
        'mention_user' => __('When people mentioned me'),
        'like_comment_mention' => __('When people like comment that someone mentioned me'),
        'comment_mention_status' => __('When people comment on status that someone mentioned me'),
        'like_mention_status' => __('When people like status that someone mentioned me'),
        'tag_user' => __('When people tagged me'),
        'like_tag_user' => __('When people like status that someone tagged me'),
        'comment_tag_user' => __('When people comment on status that someone tagged me')    	
    );
    
    if (Configure::read('core.time_notify_message_unread'))
    {
    	$array['notify_message_user'] = __('When people send me a message but i have not read it in %s minutes',Configure::read('core.time_notify_message_unread'));
    }
?>
<div class="bar-content ">
    <div class="content_center profile-info-edit">
        <form method="post">
        <div id="center" class="post_body">
            <div class="mo_breadcrumb">
                 <h1><?php echo __('Notification Settings')?></h1>
            </div>
            <div class="full_content">
                <div class="content_center">
                    <div class="edit-profile-section">
                        <p><?php echo __('Which of the these do you want to receive notify alerts about?')?>
                            <br/>
                        <?php echo __('Un-check on item that you don\'t want to get notify.')?></p>
                        <ul class="profile-checkbox">
                            <?php foreach ($array as $key=>$text): ?>
                            <li class="checkbox" >
                                <label>
                                    <?php echo $this->Form->checkbox($key, array('checked' => isset($notification_setting[$key]) ? $notification_setting[$key] : true)); ?>
                                    <?php echo $text?>
                                </label>
                            </li>
                            <?php endforeach; ?>
                            <?php
                                $this->getEventManager()->dispatch(new CakeEvent('User.NotificationSettings.View', $this));
                            ?>
                        </ul>
                        <div class="col-md-9">
                            <div style="margin-top:10px"><input type="submit" value="<?php echo __('Save Changes'); ?>" class="btn btn-action"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>