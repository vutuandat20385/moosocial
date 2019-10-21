<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
    <div class="box2 filter_block">
        <h3 class="visible-xs visible-sm"><?php echo __( 'Browse')?></h3>
        <div class="box_content">
            <?php echo $this->element('sidebar/menu'); ?>
            <?php echo $this->element('lists/categories_list')?>
            <?php echo $this->element('sidebar/search'); ?>
        </div>
    </div>
<?php $this->end(); ?>

<div class="bar-content">
    <div class="content_center">

    <div class="mo_breadcrumb">
        <h1><?php echo __( 'Videos')?></h1>
        <?php if (!empty($uid)): ?>	
        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "videos",
                                            "action" => "create",
                                            "plugin" => 'video',
                                            
                                        )),
             'title' => __( 'Share New Video'),
             'innerHtml'=> __( 'Share New Video'),
          	'data-backdrop' => 'static',
          'class' => 'button button-action topButton button-mobi-top'
     ));
 ?>
        <!-- Hook for video upload -->
        <?php $this->getEventManager()->dispatch(new CakeEvent('Video.View.Elements.uploadVideo', $this)); ?>
        <!-- Hook for video upload -->
        
        <?php endif; ?>
    </div>
    <ul class="video-content-list" id="list-content">
        <?php 
        if ( !empty( $this->request->named['category_id'] )  || !empty($cat_id) ){
            
            if (empty($cat_id)){
                $cat_id = $this->request->named['category_id'];
            }
            
            echo $this->element( 'lists/videos_list', array( 'more_url' => '/videos/browse/category/' . $cat_id . '/page:2' ) );
        }
        else{
            echo $this->element( 'lists/videos_list', array( 'more_url' => '/videos/browse/all/page:2' ) );
        }
        ?>		
    </ul>
    </div>
</div>