<?php //if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>
<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('Upcoming Events')?></h1>
        <a href="<?php echo $this->request->base?>/events/create" class="topButton button button-action button-mobi-top"><?php echo __('Create New Event')?></a>
        
    </div>
    <ul class="event_content_list" id="list-content">
            <?php echo $this->element( 'lists/events_list' ); ?>
    </ul>
</div>