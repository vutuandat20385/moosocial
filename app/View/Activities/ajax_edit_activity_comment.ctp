<div>
	<?php
		echo $this->viewMore(h($activity_comment['ActivityComment']['comment'])); 
	?>						
	
	<?php if ($activity_comment['ActivityComment']['thumbnail']):?>
	<div class="comment_thumb">
		<a data-dismiss="modal" href="<?php echo $this->Moo->getImageUrl($activity_comment,array());?>">
			<?php if($this->Moo->isGifImage($this->Moo->getImageUrl($activity_comment,array()))) :  ?>
                        <?php echo $this->Moo->getImage($activity_comment,array('class'=>'gif_image'));?>
                    <?php else: ?>
                        <?php echo $this->Moo->getImage($activity_comment,array('prefix'=>'200'));?>
                    <?php endif; ?>
		</a>
	</div> 
	<?php endif;?>
	<script>
		$('#history_activity_comment_<?php echo $activity_comment['ActivityComment']['id']?>').html('<?php echo addslashes(__('Edited')).(isset($other_user) ? ' '.addslashes(__('by')).' '.$other_user['name'] : '');?>');
	</script>
</div>