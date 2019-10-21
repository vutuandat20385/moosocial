<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooNotification"], function($,mooNotification) {
        mooNotification.initRemoveNotification();
        mooNotification.initMarkRead();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooNotification'), 'object' => array('$', 'mooNotification'))); ?>
mooNotification.initRemoveNotification();
mooNotification.initMarkRead();
<?php $this->Html->scriptEnd(); ?> 
<?php endif; ?>

<span class="arr-notify"></span>
<div class="notify_top">
<a href="javascript:void(0);" class="clearAllNotifications pull-right"><?php echo __('Clear All Notifications'); ?></a>
<a href="javascript:void(0);" class="markAllNotificationAsRead pull-right"><?php echo __('Mark All As Read'); ?></a>
</div>
<div class="clear"></div>
<ul class="initSlimScroll">
    <?php if (empty($notifications)): ?>
        <li class="notify_no_content"><?php echo __('No new notifications')?></li>
    <?php else: ?>
        <?php foreach($notifications as $noti): ?>
            <li id="noti_<?php echo $noti['Notification']['id']?>">
                <a <?php echo $noti['Notification']['read'] ? '' : 'class="unread"'?> href="<?php echo $this->request->base ?>/notifications/ajax_view/<?php echo $noti['Notification']['id']?>">
                 <?php if (!empty($noti['Sender']['id'])): ?>   
                <?php echo $this->Moo->getImage(array('User' => $noti['Sender']), array('alt'=>h($noti['Sender']['name']),'class'=> "img_wrapper2", 'width'=>"45", 'prefix' => '50_square'))?>
                
                <?php else: ?>
                    <?php $this->getEventManager()->dispatch(new CakeEvent('View.Notification.renderThumb', $this, array('noti' => $noti))); ?>
                <?php endif; ?>
                <div class="notification_content">
                    <div>
                    <b><?php echo $noti['Sender']['name']?></b>
                    <span><?php echo $this->element('misc/notification_texts', array('noti' => $noti))?></span>
                    <br />
                    </div>
<?php $this->getEventManager()->dispatch(new CakeEvent('element.notification.render', $this,array('noti' => $noti) )); ?>
                <span class="date"><?php echo $this->Moo->getTime($noti['Notification']['created'], Configure::read('core.date_format'), $utz)?></span>
                </div></a>
                <div class="noti_option">
                    <a href="javascript:void(0)" data-id="<?php echo $noti['Notification']['id']?>" class="removeNotification p_0 delete-icon" style="padding:0">
                        <i class="material-icons ">clear</i>
                    </a>
                    
                    <a style="<?php if ($noti['Notification']['read']) echo 'display:none;' ?>" href="javascript:void(0)" data-status="1" data-id="<?php echo $noti['Notification']['id']?>" class="markMsgStatus mark_read tip mark_section" title="<?php echo __( 'Mark as Read')?>">
                        <i class="material-icons">check_circle</i>
                    </a>
                    <a style="<?php if (!$noti['Notification']['read']) echo 'display:none;' ?>" href="javascript:void(0)" data-status="0" data-id="<?php echo $noti['Notification']['id']?>" class="markMsgStatus mark_unread tip mark_section mark_unread" title="<?php echo __( 'Mark as unRead')?>">
                        <i class="material-icons">check_circle</i>
                    </a>
                </div>
                
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>
<li class="more-notify">
    <a id="notifications" rel="home-content" href="<?php echo $this->request->base ?>/home/index/tab:notifications">
        <?php echo __('View All Notifications')?>
    </a>
</li>