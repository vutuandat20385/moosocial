<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooActivities"], function($, mooActivities) {
        mooActivities.initOnAjaxLoadActivityEdit();
    });
    require(["jquery","mooToggleEmoji"], function($, mooToggleEmoji) {
        mooToggleEmoji.init('message_edit_<?php echo $activity['Activity']['id'];?>');
    });
</script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji'),  'object' => array('$', 'mooToggleEmoji'))); ?>
    mooToggleEmoji.init('message_edit_<?php echo $activity['Activity']['id'];?>');
    <?php $this->Html->scriptEnd();  ?>
<?php endif; ?>

<div id="activity_edit_<?php echo $activity['Activity']['id']?>">
	<?php echo $this->Form->textarea("message_edit_".$activity['Activity']['id']."",array('name' => "message", 'value' => $activity['Activity']['content'], 'style' => 'width:100%;margin-top:0px;'),true ); ?>

    <div id="message_edit_<?php echo $activity['Activity']['id'];?>-emoji" class="emoji-toggle"></div>

    <div class="edit-post-action">
            <a class="button button-action cancelEditActivity" data-activity-id="<?php echo $activity['Activity']['id'];?>" href="javascript:void(0);" ><?php echo __('Cancel');?></a>
            <a class="btn btn-action confirmEditActivity" data-activity-id="<?php echo $activity['Activity']['id'];?>" href="javascript:void(0);" ><?php echo __('Done Editing');?></a>
	</div>
</div>