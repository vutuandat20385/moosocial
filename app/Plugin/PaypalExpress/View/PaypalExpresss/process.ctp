<div class="bar-content">
	<div class="content_center">		
			<?php if ($error):?>
				<div>
					<?php echo $error;?>
				</div>
			<?php else:?>
				<div>
					<?php echo __('Waiting redirect to Paypal');?>
					<script>
						setTimeout(function() {
							window.location = "<?php echo $url_redirect;?>";
						}, 1000);
					</script>
				</div>
			<?php endif;?>
	</div>    
</div>