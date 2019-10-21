<div id="filters" style="margin-top:5px">
    <?php if (!Configure::read('core.guest_search') && empty($uid)): ?>
    <?php else: ?>
        <?php echo $this->Form->text('keyword', array('class' => 'json-view', 'placeholder' => __('Enter keyword to search'), 'rel' => 'albums')); ?>
    <?php endif; ?>
</div>