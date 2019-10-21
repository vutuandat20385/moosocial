<div class="bar-content">
    <div class="content_center">
        <div class="mo_breadcrumb">
            <h1><?php echo $page['Page']['title']?></h1>
        </div>
        <div class="full_content p_m_10">
            <?php echo $page['Page']['content']?>
        </div>
         
     </div>
</div>
    <?php if ( $params['comments'] ): ?>
        <div class="bar-content full_content p_m_10">
    <div class="content_center">
    <h2><?php echo __('Comments')?></h2>
    
        <?php if (Configure::read('core.comment_sort_style') == COMMENT_RECENT): ?>
    
        <div><?php echo $this->element( 'comment_form', array( 'target_id' => $page['Page']['id'], 'type' => APP_PAGE ) ); ?></div>
	<ul class="list6 comment_wrapper" id="comments">
	<?php echo $this->element('comments');?>
	</ul>
    
        <?php elseif(Configure::read('core.comment_sort_style') == COMMENT_CHRONOLOGICAL): ?>
        <ul class="list6 comment_wrapper" id="comments">
	<?php echo $this->element('comments_chrono');?>
	</ul>
    
        <div><?php echo $this->element( 'comment_form', array( 'target_id' => $page['Page']['id'], 'type' => APP_PAGE ) ); ?></div>
    
        <?php endif; ?>
     </div>
</div>
	<?php endif; ?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooComment'), 'object' => array('$', 'mooComment'))); ?>
mooComment.initReplyCommentItem();
<?php $this->Html->scriptEnd(); ?>
