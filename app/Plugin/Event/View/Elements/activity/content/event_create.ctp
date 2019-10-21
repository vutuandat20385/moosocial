<?php 
$event = $object; 
$eventHelper = MooCore::getInstance()->getHelper('Event_Event');
?>

<?php if (!empty($activity['Activity']['content'])): ?>
<div class="comment_message">
<?php echo $this->viewMore(h($activity['Activity']['content']),null, null, null, true, array('no_replace_ssl' => 1)); ?>
</div>
<?php endif; ?>

<div class="activity_item">
    <div class="activity_left">
	<a class="event_feed_image <?php if($event['Event']['photo'] == ''): ?> event_no_image<?php endif; ?>" href="<?php echo $event['Event']['moo_href']?>" >
            <img src="<?php echo $eventHelper->getImage($event, array('prefix' => '150_square'));?>"/>
            <div class="event-date"><?php echo $this->Time->event_format($event['Event']['from'])?></div>
        </a>
    </div>
    <div class="activity_right ">
	<div class="event_feed_info">
            <div class="event_info_title">
                <a href="<?php echo $event['Event']['moo_href']?>">
                   <?php echo $event['Event']['moo_title']?>
                </a>
            </div>
            <div class="event_feed_extrainfo event_time">
                <span><?php echo __('Time')?>:</span><?php echo $this->Time->event_format($event['Event']['from'])?> <?php echo $event['Event']['from_time']?> - 
               
                <?php echo $this->Time->event_format($event['Event']['to'])?> <?php echo $event['Event']['to_time']?>
            </div>
            <div class="event_feed_extrainfo event_location">
                <span><?php echo __('Location')?>:</span> <?php echo h($event['Event']['location'])?>
            </div>
            <div class="event_feed_extrainfo event_location">
                <span><?php echo __('Address')?>:</span> <?php echo h($event['Event']['address'])?>
            </div>
	</div>
    </div>
</div>