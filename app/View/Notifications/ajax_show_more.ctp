<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooNotification"], function($,mooNotification) {
        mooNotification.initAjaxShow();
        mooNotification.initMarkRead();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooNotification'), 'object' => array('$', 'mooNotification'))); ?>
mooNotification.initAjaxShow();
mooNotification.initMarkRead();
<?php $this->Html->scriptEnd(); ?> 
<?php endif; ?>

<?php 
	foreach ($notifications as $noti):
?>
	<li id="noti_<?php echo $noti['Notification']['id']?>">
		<a href="<?php echo $this->request->base?>/notifications/ajax_view/<?php echo $noti['Notification']['id']?>" <?php if (!$noti['Notification']['read']) echo 'class="unread"';?>>
                    <?php echo $this->Moo->getImage(array('User' => $noti['Sender']), array('prefix' => '50_square', 'width' => 45, 'class' => 'img_wrapper2', 'alt' => h($noti['Sender']['name'])))?>
			<b><?php echo $noti['Sender']['name']?></b>
			<?php echo $this->element('misc/notification_texts', array( 'noti' => $noti ));	?>
			<br />
			<span class="date"><?php echo $this->Moo->getTime( $noti['Notification']['created'], Configure::read('core.date_format'), $utz )?></span>
		</a>
		<div class="noti_option">
			<a href="javascript:void(0)" data-id="<?php echo $noti['Notification']['id']?>" style="padding:0" class="removeNotification"><i class="material-icons delete-icon">clear</i></a>
			
                        <a style="<?php if ($noti['Notification']['read']) echo 'display:none;' ?>" href="javascript:void(0)" data-status="1" data-id="<?php echo $noti['Notification']['id']?>" class="markMsgStatus mark_read tip mark_section" title="<?php echo __( 'Mark as Read')?>">
                            <i class="material-icons">check_circle</i>
                        </a>
                        <a style="<?php if (!$noti['Notification']['read']) echo 'display:none;' ?>" href="javascript:void(0)" data-status="0" data-id="<?php echo $noti['Notification']['id']?>" class="markMsgStatus mark_unread tip mark_section mark_unread" title="<?php echo __( 'Mark as unRead')?>">
                            <i class="material-icons">check_circle</i>
                        </a>
        </div>
	</li>
<?php
	endforeach;
?>
	<?php if ($view_more): ?>
		<?php $this->Html->viewMore($view_more_url,'center #notifications_list') ?>
	<?php endif; ?>
