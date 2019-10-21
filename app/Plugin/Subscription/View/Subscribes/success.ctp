<div class="bar-content ">
    <div class="content_center">
    	<?php echo __('Payment is processing. Please check your email for subscription status.');?>
    </div>
</div>
<?php $this->Html->scriptStart(array('inline' => false)); ?>
setTimeout(function() {
   	parent.window.location.href = '<?php echo $this->request->base?>/';
},3000);
<?php $this->Html->scriptEnd(); ?>