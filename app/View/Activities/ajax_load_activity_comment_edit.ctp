<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooComment"], function($, mooComment) {
        mooComment.initOnAjaxLoadActivityCommentEdit();
    });

    require(["jquery","mooToggleEmoji"], function($, mooToggleEmoji) {
        mooToggleEmoji.init('message_activity_comment_edit_<?php echo $activity_comment['ActivityComment']['id'];?>');
    });
</script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires' => array('jquery', 'mooToggleEmoji'),  'object' => array('$', 'mooToggleEmoji'))); ?>
    mooToggleEmoji.init('message_activity_comment_edit_<?php echo $activity_comment['ActivityComment']['id'];?>');
    <?php $this->Html->scriptEnd();  ?>
<?php endif; ?>

<div id="activity_comment_edit_<?php echo $activity_comment['ActivityComment']['id']?>">
	<textarea id="message_activity_comment_edit_<?php echo $activity_comment['ActivityComment']['id']?>" name="message" ><?php echo $activity_comment['ActivityComment']['comment']?></textarea>

    <div id="message_activity_comment_edit_<?php echo $activity_comment['ActivityComment']['id'];?>-emoji" class="emoji-toggle"></div>
    <?php $this->getEventManager()->dispatch(new CakeEvent('Element.activities.afterRenderCommentForm', $this,array('type' => 'activity_comment_edit' ,'id'=>$activity_comment['ActivityComment']['id']))); ?>
	<input type="hidden" value="<?php echo $activity_comment['ActivityComment']['thumbnail'];?>" name="comment_attach" id="activity_comment_attach_id_<?php echo $activity_comment['ActivityComment']['id']?>">
	<div <?php if ($activity_comment['ActivityComment']['thumbnail']) echo "style='display:none;'";?> id="activity_comment_attach_<?php echo $activity_comment['ActivityComment']['id'];?>"></div>
	<div id="activity_comment_preview_attach_<?php echo $activity_comment['ActivityComment']['id'];?>">
		<?php
			if ($activity_comment['ActivityComment']['thumbnail']): 
		?>
			<span style="background-image:url(<?php echo $this->Moo->getImageUrl($activity_comment);?>)"><a class="removePhotoComment" data-type="activity" data-id="<?php echo $activity_comment['ActivityComment']['id']?>" href="javascript:void(0);"><i class="material-icons thumb-review-delete">clear</i></span></a>
		<?php endif;?>
	</div>
	<div class="edit-post-action">
		<a class="button button-action cancelEditActivityComment" href="javascript:void(0);" data-id="<?php echo $activity_comment['ActivityComment']['id'];?>"><?php echo __('Cancel');?></a> 
                <a class="btn btn-action confirmEditActivityComment" href="javascript:void(0);" data-id="<?php echo $activity_comment['ActivityComment']['id'];?>"><?php echo __('Done Editing');?></a>
	</div>
</div>