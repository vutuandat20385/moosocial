<?php
$eventHelper = MooCore::getInstance()->getHelper('Event_Event');
?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooEvent', 'hideshare'), 'object' => array('$', 'mooEvent'))); ?>
mooEvent.initOnView();
$(".sharethis").hideshare({media: '<?php echo $eventHelper->getImage($event, array('prefix' => '300_square'));?>', linkedin: false});
<?php $this->Html->scriptEnd(); ?>

<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
        <div class="menu">
            <ul id="menu-event" class="list2 block-body menu_top_list">
                    <?php $this->getEventManager()->dispatch(new CakeEvent('events.view.renderMenuAction', $this,array('event'=>$event))); ?>

                    <?php 
                    // invite only available for public event and owner
                    if ( ( !empty($uid) && $event['Event']['type'] == PRIVACY_PUBLIC ) || ( $uid == $event['User']['id'] ) ): 
                    ?>
                    <li>
                        <a class="inviteMore" href="javascript:void(0);" data-url="<?php echo $this->request->base?>/events/invite/<?php echo $event['Event']['id']?>" class="" title="<?php echo __( 'Invite Friends to Attend')?>"><i class="material-icons">mail</i> <?php echo __( 'Invite Friends')?></a>
                    </li>
                    <?php 
                    endif;		
                    if ( $event['Event']['user_id'] == $uid || ( $uid && !empty($cuser) && $cuser['Role']['is_admin'] ) ):
                    ?>
                    <li>
                        <a href="<?php echo $this->request->base?>/events/create/<?php echo $event['Event']['id']?>"><i class="material-icons">mode_edit</i> <?php echo __( 'Edit Event')?></a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" data-id="<?php echo $event['Event']['id']?>" class="deleteEvent"><i class="material-icons">delete</i> <?php echo __( 'Delete Event')?></a>                    </li>
                    <?php endif; ?>		
            </ul>
        </div>
	<div class="box2">
            <div class='box_content event-box-content'>
		<ul class="list6 list6sm">
			<li>
                            <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "reports",
                                            "action" => "ajax_create",
                                            "plugin" => false,
                                            'event_event',
                                            $event['Event']['id'],
                                        )),
             'title' => __( 'Report Event'),
             'innerHtml'=> __( 'Report Event'),
     ));
 ?>
                            </li>
                            <?php if ($event['Event']['type'] != PRIVACY_PRIVATE): ?>
                            
                            <li>
                                <a href="javascript:void(0);" share-url="<?php echo $this->Html->url(array(
                                    'plugin' => false,
                                    'controller' => 'share',
                                    'action' => 'ajax_share',
                                    'Event_Event',
                                    'id' => $event['Event']['id'],
                                    'type' => 'event_item_detail'
                                ), true); ?>" class="shareFeedBtn"><?php echo __('Share'); ?></a>
                            </li>
                            <?php endif; ?>
		</ul>	
            </div>
	</div>	
<?php $this->end();?>

<!--Bengin center-->
<div class='bar-content full_content p_m_10'>
    <div class='event_detail-wrap'>
        <a href="<?php echo $eventHelper->getImage($event, array('' => ''));?>" style="background-image:url('<?php echo $eventHelper->getImage($event, array('prefix' => '300_square'));?>')" class="event-detail-thumb">
            <div class="event_gradient_bg"></div>
        </a>
        
        <div class="content_center">
	<h1><?php echo $event['Event']['title']?></h1>

	<h2><?php echo __( 'Information')?></h2>
	<ul class="list6 info">
            <li><label><?php echo __('Privacy') ?>:</label>
                <div> <?php if ($event['Event']['type'] == PRIVACY_PUBLIC): ?>
                <?php echo __('Public')?>
                <?php elseif ($event['Event']['type'] == PRIVACY_PRIVATE): ?>
                <?php echo __('Private')?>
                <?php endif; ?></div>
            </li>
		<li><label><?php echo __( 'Time')?>:</label><div>
			<?php echo $this->Time->event_format($event['Event']['from'])?> <?php echo $event['Event']['from_time']?> - 
			<?php echo $this->Time->event_format($event['Event']['to'])?> <?php echo $event['Event']['to_time']?>
			(<?php if (!empty($event['Event']['timezone'])) echo $this->Moo->getTimeZoneByKey($event['Event']['timezone']); else echo Configure::read('core.timezone');?>)</div>
		</li>
		<li><label><?php echo __( 'Location')?>:</label><div><?php echo h($event['Event']['location'])?></div></li>
		<?php if ( !empty( $event['Event']['address'] ) ): ?>
		<li><label><?php echo __( 'Address')?>:</label><div><?php echo h($event['Event']['address'])?> (<?php
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
                                   ?>)</div></li>
        <?php endif; ?>
		<?php if ( !empty( $event['Event']['category_id'] ) ): ?>
		<li><label><?php echo __( 'Category')?>:</label><div><a href="<?php echo $this->request->base?>/events/index/<?php echo $event['Event']['category_id']?>/<?php echo seoUrl($event['Category']['name'])?>"><?php echo $event['Category']['name']?></a></div></li>
		<?php endif; ?>
		<li><label><?php echo __( 'Created by')?>:</label><div><?php echo $this->Moo->getName($event['User'], false)?></div></li>
		<li><label><?php echo __( 'Info')?>:</label>
                    <div>
                        <div class="video-description truncate" data-more-text="<?php echo __( 'Show More')?>" data-less-text="<?php echo __( 'Show Less')?>">
                            <?php echo $this->Moo->cleanHtml($this->Text->convert_clickable_links_for_hashtags( $event['Event']['description'] , Configure::read('Event.event_hashtag_enabled')))?>
                        </div>
                        </div>
                </li>
        <li>
            <?php $this->Html->rating($event['Event']['id'],'events','Event'); ?>
        </li>
	</ul>
        </div>
    </div>
</div>
<div class='event-detail'>
	<div class="p_7">
		 <?php $this->MooActivity->wall($eventActivities)?>
	</div>
<?php if ( !empty( $event['Event']['address'] ) ): ?>
    <!-- MAPS -->
    <style>
        #mapmodals label { width: auto!important; display:inline!important; }
        #mapmodals img { max-width: none!important; }
        #map-canvas {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        #map-canvas {
            width:100%;
            height: 300px;
        }
    </style>

    <section class="modal fade in" id="mapmodals">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="title-modal">
                    <?php echo __('Map View'); ?>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span>&times;</span></button>
                    
                </div>
                <div class="modal-body">
                    <?php echo  $this->MooGMap->loadGoogleMap($event['Event']['address'],530,300); ?>
                    <?php $google_link = 'http://maps.google.com/?q='. $event['Event']['address'] ; ?>
                    <a class="google_map_view" target="_blank" href="<?php echo $google_link ?>"><?php echo __('View on google map') ?></a>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </section><!-- /.modal -->
<?php $this->Html->scriptStart(array('inline' => false)); ?>

    function initialize() {
        var mapOptions = {
            center: myLatlng,
            zoom: 16,
            mapTypeControl: false,
            center:myLatlng,
            panControl:false,
            rotateControl:false,
            streetViewControl: false,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        map = new google.maps.Map(document.getElementById("map_canvas"),
        mapOptions);

        var contentString = '<div id="mapInfo">'+
            '</div>';

        var infowindow = new google.maps.InfoWindow({
        content: contentString
        });

        var marker = new google.maps.Marker({
        position: myLatlng,
        map: map,
        title:"",
        //maxWidth: 200,
        //maxHeight: 200
        });


        google.maps.event.addListener(marker, 'click', function() {
            infowindow.open(map,marker);
        });
    }

    google.maps.event.addDomListener(window, 'load', initialize);

    //start of modal google map
    $('#mapmodals').on('shown.bs.modal', function () {
        google.maps.event.trigger(map, "resize");
        map.setCenter(myLatlng);
    });
    google.maps.event.addDomListener(window, "resize", function() {
        var center = map.getCenter();
        google.maps.event.trigger(map, "resize");
        map.setCenter(center);
    });
    //end of modal google map
    if( !$.trim( $('#menu-event').html() ).length ) {
        $('#menu-event').parent().addClass("hidden");
    }
    
<?php $this->Html->scriptEnd(); ?>

<?php endif; ?>
</div>