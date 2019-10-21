<?php

$eventHelper = MooCore::getInstance()->getHelper('Event_Event');
?>
<div class="title-modal">
    <?php echo __( 'Joined Events')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
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
                    <?php echo h($this->Text->truncate($event['Event']['description'], 125, array('exact' => false)))?>
                </div>
            </div>
        </div>
    </li>
<?php endforeach; ?>
</ul>
</div>
