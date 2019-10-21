<div>
	<?php
		echo $this->viewMore(h($comment['Comment']['message']), null, null, null, true, array('no_replace_ssl' => 1));
	?>
	
	<?php if ($comment['Comment']['thumbnail']):?>
	<div class="comment_thumb">
		<a data-dismiss="modal" href="<?php echo $this->Moo->getImageUrl($comment,array());?>">
		<?php if($this->Moo->isGifImage($this->Moo->getImageUrl($comment,array()))) :  ?>
				                     <?php echo $this->Moo->getImage($comment,array('class'=>'gif_image'));?>
                                                <?php else: ?>
                                                        <?php echo $this->Moo->getImage($comment,array('prefix'=>'200'));?>
                                                <?php endif; ?>
		</a>
	</div>
	<?php endif;?>
	<script>
		$('#history_item_comment_<?php echo $comment['Comment']['id']?>').html('<?php echo addslashes(__('Edited')).(isset($other_user) ? ' '.addslashes(__('by')).' '.$other_user['name'] : '');?>');
	</script>
</div>