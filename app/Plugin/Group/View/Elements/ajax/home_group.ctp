
<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('My Groups')?></h1>
        <a href="<?php echo $this->request->base?>/groups/create" class="topButton button button-action button-mobi-top"><?php echo __('Create New Group')?></a>
        	
    </div>
    <ul class="group-content-list" id="list-content">
            <?php echo $this->element( 'lists/groups_list' ); ?>
    </ul>
</div>