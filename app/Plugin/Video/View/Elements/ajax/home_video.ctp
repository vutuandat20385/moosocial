<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __( 'My Videos')?></h1>
        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "videos",
                                            "action" => "create",
                                            "plugin" => 'video',
   
                                        )),
             'title' => __( 'Share New Video'),
             'innerHtml'=> __( 'Share New Video'),
          'class' => 'topButton button button-action button-mobi-top'
     ));
 ?>
        <!-- Hook for video upload -->
        <?php $this->getEventManager()->dispatch(new CakeEvent('Video.View.Elements.uploadVideo', $this)); ?>
        <!-- Hook for video upload -->
        	
    </div>
    <ul class="video-content-list" id="list-content">
            <?php echo $this->element( 'lists/videos_list' ); ?>
    </ul>
</div>