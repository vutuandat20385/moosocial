<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooPhoto"], function($,mooPhoto) {
        mooPhoto.initOnCreateAlbum();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooPhoto'), 'object' => array('$', 'mooPhoto'))); ?>
mooPhoto.initOnCreateAlbum();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php
$tags_value = '';
if (!empty($tags)) $tags_value = implode(', ', $tags);
?>
<div class="title-modal">
    <?php if (isset($album['Album']['id']) && $album['Album']['id']):?>
    <?php echo __( 'Edit Album')?>
    <?php else: ?>
    <?php echo __( 'Create New Album')?>
    <?php endif; ?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
<div class="create_form">
<form id="createForm">
<?php echo $this->Form->hidden('id', array('value' => $album['Album']['id'])); ?>
<ul class="list6 list6sm2" style="position:relative">
	<li>
            <div class="col-md-2">
                <label><?php echo __( 'Album Title')?></label>
            </div>
            <div class="col-md-10">
                <?php echo $this->Form->text('title', array('value' => html_entity_decode($album['Album']['title']))); ?>
            </div>
            <div class="clear"></div>
	</li>
	<li>
            <div class="col-md-2">
                <label><?php echo __( 'Category')?></label>
            </div>
            <div class="col-md-10">
                <?php echo $this->Form->select( 'category_id', $categories, array( 'value' => $album['Album']['category_id'] ) ); ?>
            </div>
            <div class="clear"></div>
	</li>
	<li>
            <div class="col-md-2">
                <label><?php echo __( 'Description')?></label>
            </div>
            <div class="col-md-10">
                <?php echo $this->Form->textarea('description', array('value' => $album['Album']['description'])); ?>
            </div>
            <div class="clear"></div>
	</li>
	<li>
            <div class="col-md-2">
                <label><?php echo __( 'Tags')?></label>
            </div>
            <div class="col-md-10">
                <?php echo $this->Form->text('tags', array('value' => $tags_value)); ?> <a href="javascript:void(0)" class="tip profile-tip" title="<?php echo __( 'Separated by commas or space')?>">(?)</a>
            </div>
            <div class="clear"></div>
	</li>
	<li>
            <div class="col-md-2">
                 <label><?php echo __( 'Privacy')?></label>
            </div>
            <div class="col-md-10">
           
		<?php echo $this->Form->select('privacy', array( PRIVACY_EVERYONE => __( 'Everyone'), 
														 PRIVACY_FRIENDS  => __( 'Friends Only'), 
														 PRIVACY_ME 	  => __( 'Only Me') 
												  ), 
												  array( 'value' => $album['Album']['privacy'], 
												  		 'empty' => false
										) ); 
		?>
           </div>
            <div class="clear"></div>
	</li>
	<li>
            <div class="col-md-2">
                <label>&nbsp;</label>
            </div>
            <div class="col-md-10">
                <button type='button' class='btn btn-action' id="saveBtn"><?php echo __( 'Save Album')?></button>
            </div>
            <div class="clear"></div>
	</li>
</ul>
</form>
</div>
</div>
<div class="error-message" style="display:none;"></div>