<?php
if(Configure::read('Event.event_enabled')):
    if(empty($title)) $title = "Upcoming Events";
    if(empty($num_item_show)) $num_item_show = 10;
    if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

    $eventHelper = MooCore::getInstance()->getHelper('Event_Event');
    $upcomming_events = $upcomingEventWidget;    
    ?>
    <?php if (!empty($upcomming_events)): ?>
    <div class="box2">
        <?php if($title_enable): ?>
        <h3><?php echo __( $title)?></h3>
        <?php endif; ?>
        <div class="box_content">

            <?php
            if (!empty($upcomming_events)):
                ?>
                <ul class="event_block_list">
                    <?php foreach ($upcomming_events as $event): ?>
                        <li>
                            <a class="event_thumb" href="<?php echo $this->request->base?>/events/view/<?php echo $event['Event']['id']?>/<?php echo seoUrl($event['Event']['title'])?>">                                
                                <img style="width:75px;" src="<?php echo $eventHelper->getImage($event, array('prefix' => '75_square'));?>" />

                            </a>
                            <div class="event_info">
                                <a class="title" href="<?php echo $this->request->base?>/events/view/<?php echo $event['Event']['id']?>/<?php echo seoUrl($event['Event']['title'])?>">
                                    <?php echo $event['Event']['title']?></a>
                                <div ><?php echo __( '%s attending', $event['Event']['event_rsvp_count'])?></div>
                                <div><?php echo $this->Time->event_format($event['Event']['from'])?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php
            else:
                echo __( 'Nothing found');
            endif;
            ?>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>