<?php
$ids = explode(',',$activity['Activity']['items']);
$eventModel = MooCore::getInstance()->getModel('Event_Event');	
$events = $eventModel->find( 'all', array( 'conditions' => array( 'Event.id' => $ids ), 'limit' => 3));
$events_count = $eventModel->find( 'count', array( 'conditions' => array( 'Event.id' => $ids )));
$eventModel->cacheQueries = false;
$eventHelper = MooCore::getInstance()->getHelper('Event_Event');
?>
<ul class="activity_content">
<?php foreach ( $events as $event ): ?>
    <li>
        <div class="activity_item">
            <div class="activity_left">
                <a href="<?php echo $event['Event']['moo_href']?>">
                    <img src="<?php echo $eventHelper->getImage($event, array('prefix' => '150_square'))?>" class="img_wrapper2" />
                </a>
            </div>
            <div class="activity_right ">
                <a class="feed_title" href="<?php echo $event['Event']['moo_href']?>"><?php echo $event['Event']['moo_title']?></a>
                <div class="date comment_message feed_detail_text">
                	<?php echo  $this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $event['Event']['description'])), 200, array('exact' => false)); ?>
                </div>
            </div>
        </div>
    </li>
<?php endforeach; ?>
    <?php if ($events_count > 3): ?>
    <div>
        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "events",
                                            "action" => "ajax_event_joined",
                                            "plugin" => 'event',
                                            'activity_id:' . $activity['Activity']['id'],
                                            
                                        )),
             'title' => __('View more events'),
             'innerHtml'=> __('View more events'),
     ));
 ?>
        </div>
    <?php endif; ?>
</ul>

