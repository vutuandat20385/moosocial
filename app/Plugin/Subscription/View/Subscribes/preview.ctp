<div class="title-modal">
    <?php echo __('Subscription Plans')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">x</span></button>
</div>
<?php 
	$element = (Configure::read('Subscription.select_theme_subscription_packages') ? 'Subscription.theme_compare' : 'Subscription.theme_default');
?>
<?php echo $this->element($element,array('type'=>'register'));?>
<?php if (!$type):?>
<script>
	$('.button_select').click(function(){
		$('#plan-view').modal('hide');
		package_id = $(this).attr('ref');		
		$('#select-plan').val($('.package_register_'+package_id).val());
	});
</script>
<?php else:?>
<script>
	$('.button_select').click(function(){
		$('#plan-view').modal('hide');
		package_id = $(this).attr('ref');		
		$('#select-plan').val($('.package_register_'+package_id).val());
		$('#form_upgrade').submit();
	});
</script>
<?php endif;?>