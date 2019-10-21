

<?php if (Configure::read('Event.event_enabled') == 1): ?>
<ul class="event_content_list">
<?php
if(!isset($events)){
    if($uid !== null){
        $events = $this->requestAction("events/upcomming/uid:".$uid);
    }else{
        $events = array();
    }
}
$eventHelper = MooCore::getInstance()->getHelper('Event_Event');
    if (count($events) > 0):
        foreach ($events as $event):
    ?>
        <li class="full_content p_m_10">
                
                <div class="">
                    
                    <a class='event-list-thumb' style="background-image:url(<?php echo $eventHelper->getImage($event, array('prefix' => '250'));?>)" href="<?php echo $this->request->base?>/events/view/<?php echo $event['Event']['id']?>/<?php echo seoUrl($event['Event']['title'])?>">
                        
                        <div class="event-date"><?php echo $this->Time->event_format($event['Event']['from'])?></div>
                    </a>
                    <div class="event-info-list">
                        <a class="title" href="<?php echo $this->request->base?>/events/view/<?php echo $event['Event']['id']?>/<?php echo seoUrl($event['Event']['title'])?>">
                            <b><?php echo $event['Event']['title']?></b>
                        </a>
                        
                        <div class="event-info">
                            
                            <div class="m_b_5">
                            <?php if ($event['Event']['type'] == PRIVACY_PUBLIC): ?>
                            <?php echo __('Public')?>
                            <?php elseif ($event['Event']['type'] == PRIVACY_PRIVATE): ?>
                            <?php echo __('Private')?>
                            <?php endif; ?>
                            &middot; <?php echo __( '%s attending', $event['Event']['event_rsvp_count'])?>
                            </div>
                            <div class="m_b_5">
                                <span><?php echo __('Time') ?></span>
                                <div>
                                <?php echo $this->Time->event_format($event['Event']['from'])?> <?php echo $event['Event']['from_time']?> - 
                                <?php echo $this->Time->event_format($event['Event']['to'])?> <?php echo $event['Event']['to_time']?>
                                </div>
                            </div>
                            <div class="m_b_5">
                                <span><?php echo __('Location') ?></span>
                                <div>
                                    <?php echo h($event['Event']['location'])?>
                                </div>
                            </div>
                            <?php if (!empty($event['Event']['address'])): ?>
                            <div class="m_b_5">
                                <span><?php echo __('Address') ?></span>
                                <div>
                                    <?php echo h($event['Event']['address'])?> (<?php
                                        $this->MooPopup->tag(array(
                                               'href'=>$this->Html->url(array("controller" => "events",
                                                                              "action" => "show_g_map",
                                                                              "plugin" => 'event',
                                                                              $event['Event']['id'],

                                                                          )),
                                               'title' => __( 'View Map'),
                                            'rel' => 'google_map',
                                               'innerHtml'=> __( 'View Map'),
                                                'target' => 'mapmodals'
                                       ));
                                   ?>)
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="m_b_5">
                                <?php $this->Html->rating($event['Event']['id'],'events','Event'); ?>
                            </div>
                        </div>

                    </div>

                    <?php if( !empty($uid) && (($event['Event']['user_id'] == $uid ) || ( !empty($cuser) && $cuser['Role']['is_admin'] ) ) ): ?>
                    <div class="list_option">
                        <div class="dropdown">
                            <button id="dropdown-edit" data-target="#" data-toggle="dropdown" >
                                <i class="material-icons">more_vert</i>
                            </button>

                            <ul role="menu" class="dropdown-menu" aria-labelledby="dropdown-edit" style="float: right;">
                                <?php if ($event['User']['id'] == $uid || ( !empty($cuser) && $cuser['Role']['is_admin'] )): ?>
                                    <li style="border-top:none"><a href="<?php echo $this->request->base?>/events/create/<?php echo $event['Event']['id']?>"> <?php echo __( 'Edit Event')?></a></li>
                                <?php endif; ?>
                                <?php if ( ($event['Event']['user_id'] == $uid ) || ( !empty( $event['Event']['id'] ) && !empty($cuser) && $cuser['Role']['is_admin'] ) ): ?>
                                    <li style="border-top:none"><a href="javascript:void(0)" data-id="<?php echo $event['Event']['id']?>" class="deleteEvent" > <?php echo __( 'Delete Event')?></a></li>
                                    <li class="seperate" style="border-top:none"></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

        </li>
    <?php
        endforeach;
    else:
        echo '<div class="clear text-center no-result-found">' . __( 'No more results found') . '</div>';
    endif;

?>

<?php
if (!empty($more_result)):
?>

    <?php $this->Html->viewMore($more_url) ?>
<?php
endif;

?>
</ul>
<?php endif; ?>
<section class="modal fade in" id="mapmodals">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php echo  $this->MooGMap->loadGoogleMap('',530,300,true); ?>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</section><!-- /.modal -->

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooEvent"], function($,mooEvent) {
        mooEvent.initOnListing();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooEvent'), 'object' => array('$', 'mooEvent'))); ?>
mooEvent.initOnListing();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>