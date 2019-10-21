<?php
echo $this->Html->css(array('jquery.mp'), null, array('inline' => false));
echo $this->Html->script(array('jquery.mp.min'), array('inline' => false)); 
?>
<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
	<div class="box2 filter_block">
            <h3 class="visible-xs visible-sm"><?php echo __('Browse')?></h3>
            <div class="box_content">
		<ul class="list2 menu-list" id="browse">
			<li class="current" id="browse_all"><a class="json-view" data-url="<?php echo $this->request->base?>/blogs/browse/all" href="<?php echo $this->request->base?>/blogs"><?php echo __( 'All Entries')?></a></li>
			<?php if (!empty($uid)): ?>
                        <li><a class="json-view" data-url="<?php echo $this->request->base?>/blogs/browse/my" href="<?php echo $this->request->base?>/blogs"><?php echo __('My Entries')?></a></li>
			<li><a class="json-view" data-url="<?php echo $this->request->base?>/blogs/browse/friends" href="<?php echo $this->request->base?>/blogs"><?php echo __("Friends' Entries")?></a></li>
                        <?php endif; ?>
		</ul>
                <?php echo $this->element('lists/categories_list')?>
		<div id="filters" style="margin-top:5px">
                    <?php if(!Configure::read('core.guest_search') && empty($uid)): ?>
                    <?php else: ?>
			<?php echo $this->Form->text( 'keyword', array( 'placeholder' => __('Enter keyword to search'), 'rel' => 'blogs', 'class' => 'json-view') );?>
                    <?php endif; ?>
		</div>
            </div>
	</div>

<?php $this->end(); ?>
<?php $this->setNotEmpty('east');?>



<div class="bar-content">
    <div class="content_center">
	
        
        
        <div class="mo_breadcrumb">
            <h1><?php echo __('Blogs')?></h1>
            <?php if (!empty($uid)): ?>
            <a href="<?php echo $this->request->base?>/blogs/create" class="button button-action topButton button-mobi-top"><?php echo __('Write New Entry')?></a>
            <?php endif; ?>
        </div>
        <ul id="list-content">
	        <?php echo $this->element( 'lists/blogs_list', array( 'more_url' => '/blogs/browse/all/page:2' ) ); ?>
        </ul>
    </div>
</div>
