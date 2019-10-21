<?php 
$event = $object; 
$eventHelper = MooCore::getInstance()->getHelper('Event_Event');
?>

    <div class="activity_left">
	<a target="_blank" class="event_feed_image <?php if($event['Event']['photo'] == ''): ?> event_no_image<?php endif; ?>" href="<?php echo $event['Event']['moo_href']?>" >
            <img src="<?php echo $eventHelper->getImage($event, array('prefix' => '150_square'));?>"/>
            <div class="event-date"><?php echo $this->Time->event_format($event['Event']['from'])?></div>
        </a>
    </div>
    <div class="activity_right ">
	<div class="event_feed_info">
            <div class="event_info_title">
                <a target="_blank" href="<?php echo $event['Event']['moo_href']?>">
                   <?php echo $event['Event']['moo_title']?>
                </a>
            </div>
            <div class="event_feed_extrainfo event_time">
                <span><?php echo __('Time')?>:</span><?php echo $this->Time->event_format($event['Event']['from'])?> <?php echo $event['Event']['from_time']?> - 
               
                <?php echo $this->Time->format($event['Event']['to'], '%B %d, %Y', null, $utz)?> <?php echo $event['Event']['to_time']?>
            </div>
            <div class="event_feed_extrainfo event_location">
                <span><?php echo __('Location')?>:</span> <?php echo h($event['Event']['location'])?>
            </div>
            <div class="event_feed_extrainfo event_location">
                <span><?php echo __('Address')?>:</span> <?php echo h($event['Event']['address'])?>
            </div>
	</div>
    </div>
