<div class="logo-default">
	<?php $logo = Configure::read('core.logo');
        if ( !empty( $logo ) ): ?>
	<a href="<?php echo $this->request->base?>/home"><img src="<?php echo $this->Moo->logo(); ?>" alt="<?php echo Configure::read('core.site_name'); ?>"></a>
	<?php else: ?> 
	<a href="<?php echo $this->request->base?>/home" id="logo_default"><?php echo Configure::read('core.site_name'); ?></a>
	<?php endif; ?>
</div>