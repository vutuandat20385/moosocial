<div class="box2 menu">
	<h3><?php echo __('User Menu')?></h3>
	<ul class="list2">					
		<li <?php if ($cmenu == 'profile') echo 'class="current"'; ?>>
			<a href="<?php echo $this->request->base?>/users/profile"><i class="material-icons">description</i> <?php echo __('Profile Information')?></a>
		</li>

		<li <?php if ($cmenu == 'notification_settings') echo 'class="current"'; ?>>
			<a href="<?php echo $this->request->base?>/users/notification_settings"><i class="material-icons">settings</i> <?php echo __('Notification System')?></a>
		</li>

		<li <?php if ($cmenu == 'password') echo 'class="current"'; ?>>
                        <a href="<?php echo $this->request->base?>/users/password"><i class="material-icons">lock</i> <?php echo __('Change Password')?></a>
                </li>

		<li <?php if ($cmenu == 'email_settings') echo 'class="current"'; ?>>
			<a href="<?php echo $this->request->base?>/users/email_settings"><i class="material-icons">email</i> <?php echo __('Email notification settings')?></a>
		</li>
        
		<?php
			$helperSubscription = MooCore::getInstance()->getHelper('Subscription_Subscription');
			if ($helperSubscription->checkEnableSubscription() && $cuser['Role']['is_super'] != 1):
		?>
       
		<li <?php if ($cmenu == 'upgrade_membership') echo 'class="current"'; ?>>
			<?php echo $this->Html->link('<i class="material-icons">unarchive</i>' . __('Subscription Management'), array('plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'upgrade'), array('escape' => false)) ?>
		</li>
		<?php endif;?>
                
		<?php
		if ( $this->elementExists('menu/profile') )
			echo $this->element('menu/profile');
		?>
                
                <!-- Should be hook for third party -->
                <?php $this->getEventManager()->dispatch(new CakeEvent('Elements.profilenav', $this, array('cmenu' => $cmenu))); ?>
                <!-- Should be hook for third party -->
	</ul>
</div>
