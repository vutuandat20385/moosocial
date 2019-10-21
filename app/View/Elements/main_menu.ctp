<?php if (!empty($uid)): ?>
<div id="fb-root"></div>
<?php if ($this->Moo->socialIntegrationEnable('facebook')): ?>
<script type="text/javascript">
    window.fbAsyncInit = function() {
        FB.init({
            appId      : '<?php echo Configure::read('FacebookIntegration.facebook_app_id')?>',
            cookie     : true,  // enable cookies to allow the server to access
            // the session
            xfbml      : true,  // parse social plugins on this page
            version    : 'v2.1' // use version 2.1
        });
    };

    // Load the SDK asynchronously
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    function FBLogout(){

        FB.getLoginStatus(function(response) {
            if (response && response.status === 'connected') {
                FB.logout(function(response) {
                    
                    window.location ="<?php echo $this->request->base?>/users/do_logout";
                });
            }else{
                // To do:
                gapi.auth.signOut();
                window.location ="<?php echo $this->request->base?>/users/do_logout";
            }
        });
    }
</script>
<?php endif; ?>
<?php
	$helper = MooCore::getInstance()->getHelper('Subscription_Subscription');
	$subscribe = $helper->getSubscribeActive($cuser); 
	if ($subscribe):
?>
	<div class='notify_group'>
	<div class="btn-group">
	
	    <div class="dropdown notify_content">
	        <a class="dropdown-toggle <?php if (!empty($cuser['notification_count'])): ?>hasNotify<?php endif; ?>" href="#" id="notificationDropdown" data-toggle="dropdown">
	            <i class="material-icons">notifications</i>
	
	            <?php if (!empty($cuser['notification_count'])): ?>
	            <span class="notification_count">
	            <?php echo $cuser['notification_count']?>
	            </span>
	            <?php endif; ?>
	
	        </a>
	
	        <ul class="dropdown-menu notification_list keep_open" id="notifications_list" role="menu" aria-labelledby="dropdownMenu1">
	
	        </ul>
	
	    </div>
	    <!-- END GET NOTIFICATION -->
	</div>
	
	<div class="btn-group">
	     <!-- GET MSG -->
	    <div class="dropdown notify_content">
	        <a class="dropdown-toggle <?php if (!empty($cuser['conversation_user_count'])): ?>hasNotify<?php endif; ?>" href="#" id="conversationDropdown" data-toggle="dropdown">
	            <i class="material-icons">question_answer</i>
	
	            <?php if (!empty($cuser['conversation_user_count'])): ?>
	            <span class="conversation_count">
	            <?php echo $cuser['conversation_user_count']?>
	             </span>
	            <?php endif; ?>
	
	        </a>
	        <ul class="dropdown-menu" id="conversation_list" role="menu" aria-labelledby="dropdownMenu1">
	        </ul>
	
	    </div>
	    <!-- END GET MSG -->
	</div>
	</div>
	<?php endif;?>
<?php endif; ?>
<div id="mobi_menu">
    <div class="visible-xs visible-sm closeButton">
        <button id="closeMenuMain" type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span> <span class="sr-only">Close</span></button>
    </div>
    <!--Userbox-->
    <div class="navbar-form navbar-right main-menu-content">


            <?php if (!empty($cuser)): ?>
	            <?php
				$helper = MooCore::getInstance()->getHelper('Subscription_Subscription');
				$subscribe = $helper->getSubscribeActive($cuser);
				if ($subscribe) : 
					?>
		            <!-- GET NOTIFICATION -->
		            <?php $this->Html->scriptStart(array('inline' => false,'requires'=>array('jquery','mooNotification'),'object'=>array('$','mooNotification'))); ?>
		            mooNotification.setUrl({
		            'show_notification': "<?php echo $this->request->base.'/notifications/show';?>",
		            'show_conversation': "<?php echo $this->request->base.'/conversations/show';?>",
		            'refresh_notification_url': "<?php echo $this->request->base.'/notifications/refresh';?>",
		            });
		            mooNotification.setInterval(<?php echo Configure::read('core.notification_interval'); ?>);
		            <?php $this->Html->scriptEnd(); ?>
		            <div class="global-search"> 
		            	<i class="material-icons">search</i>
		                <input type="text" id="global-search" placeholder="<?php echo __('Search')?>">
		                <ul id="display-suggestion" style="display: none" class="suggestionInitSlimScroll">
		
		                </ul>
		            </div>
	
	            <?php else:?>
	            	<!-- GET NOTIFICATION -->
		            <?php $this->Html->scriptStart(array('inline' => false,'requires'=>array('jquery','mooNotification'),'object'=>array('$','mooNotification'))); ?>
		            mooNotification.setActive(false);
		            <?php $this->Html->scriptEnd(); ?>
	            <?php endif;?>
                
	            <div class='hidden-xs hidden-sm menu_large'>
	
	                <div class="btn-group menu_acc_content">
	                    <a href="<?php echo $this->Moo->getProfileUrl( $cuser )?>">
	                        <?php echo $this->Moo->getImage(array('User' => $cuser), array("width" => "45px", "id" => "member-avatar", "alt" => $cuser['name'], 'prefix' => '50_square'))?>
	                    </a>
	                    <a class="dropdown-user-box dropdown-toggle" data-toggle="dropdown" href="#" >
	                        <i class="material-icons">expand_more</i>
	                    </a>
	
	                    <ul class="dropdown-menu" role="menu">
	                        <span class="arr-down"></span>
	                        <?php $hide_admin_link = Configure::read('core.hide_admin_link');
	                            if ( $cuser['Role']['is_admin'] && empty( $hide_admin_link ) ): ?>
	                        <li><a href="<?php echo $this->request->base?>/admin/home"><?php echo __('Admin Dashboard')?></a></li>
	                        <?php endif; ?>
	                        <li><a href="<?php echo $this->request->base?>/users/profile"><?php echo __('Profile Information')?></a></li>
	                        <?php
	                        	$helperSubscription = MooCore::getInstance()->getHelper('Subscription_Subscription');
	                        	if ($helperSubscription->checkEnableSubscription() && $cuser['Role']['is_super'] != 1):
	                        ?>
	                        	<li><?php echo $this->Html->link(__('Subscription Management'), array('plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'upgrade')) ?></li>
	                        <?php endif;?>                        
	                        <li><a href="<?php echo $this->request->base?>/users/avatar"><?php echo __('Change Profile Picture')?></a></li>
	
	                        <?php if ( $cuser['conversation_user_count'] > 0 ): ?>
	                        <li><a href="<?php echo $this->request->base?>/home/index/tab:messages"><?php echo __('New Messages (%s)', $cuser['conversation_user_count'])?></a></li>
	                        <?php endif; ?>
	                        <?php if ( $cuser['friend_request_count'] > 0 ): ?>
	                        <li><a href="<?php echo $this->request->base?>/home/index/tab:friend-requests"><?php echo __('Friend Requests') . " (<span id='friend_request_count'>" . $cuser['friend_request_count'] . "</span>)" ?></a></li>
	                        <?php endif; ?>
	                        <li><a href="<?php echo $this->request->base?>/home/index/tab:invite-friends"><?php echo __('Invite Friends')?></a>
	                        <li><a href="<?php echo $this->request->base?>/users/do_logout"><?php echo __('Log Out')?></a></li>
	                    </ul>
	                    <div id="gSignOutWrapper" style="display:none">
	                        <div id="customBtn" class="customGPlusSignIn">
	                            <span class="icon"></span>
	                            <span class="buttonText">Sign out</span>
	                        </div>
	                    </div>
	               </div>
	
	
	                <?php if (empty($cuser['notification_count'])): ?>
	                <?php $this->Html->scriptStart(array('inline' => false,'requires'=>array('jquery','tinycon'),'object'=>array('$','Tinycon'))); ?>
	                $(document).ready(function(){Tinycon.setBubble(<?php echo $cuser['notification_count']?>);});
	                <?php $this->Html->scriptEnd(); ?>
                	<?php endif; ?>
            	</div>

            <?php else: ?>
        <!-- Login Form  -->
        
        <div class="guest-action">
           <?php if(Configure::read('core.disable_registration') != 1): ?>
                <a class="btn btn-success" href="<?php echo $this->request->base . '/users/register' ?>"> <?php echo __('Sign Up')?></a>
           <?php endif; ?>
                <a class="button" href="<?php echo $this->request->base . '/users/member_login' ?>"> <?php echo __('Login')?></a>

        </div>
        <!-- End login form -->
            <?php endif; ?>
        </div>
    
    <!--End  userbox-->
    <a class="btn_open_large" href="javascript:void(0)" onclick="$('.open_large_menu').toggle();return false;">
        <span class='arr-menu'></span>
            <span class='line'></span>
            <span class='line'></span>
            <span class='line'></span>
    </a>
    <div class="open_large_menu">
        <?php
        echo $this->Menu->generate('main-menu', null, array('class' => 'nav navbar-nav menu_top_list', 'id' => 'main_menu'));
        ?>
    </div>
    <!--Menu acc-->
     <?php if (!empty($uid)): ?>
    <div class='visible-xs visible-sm'>
        <div class='title_small_list'>
            <?php echo __('Account')?>
        </div>
        <ul class="menu-account menu_top_list">
            <?php $hide_admin_link = Configure::read('core.hide_admin_link');
                if ( $cuser['Role']['is_admin'] && empty( $hide_admin_link ) ): ?>
            <li><a href="<?php echo $this->request->base?>/admin/home"><?php echo __('Admin Dashboard')?></a></li>
            <?php endif; ?>
            <li><a href="<?php echo $this->request->base?>/users/profile"><?php echo __('Profile Information')?></a></li>
            <li><a href="<?php echo $this->request->base?>/users/avatar"><?php echo __('Change Profile Picture')?></a></li>
            <?php
                        	$helperSubscription = MooCore::getInstance()->getHelper('Subscription_Subscription');
                        	if ($helperSubscription->checkEnableSubscription() && $cuser['Role']['is_super'] != 1):
                        ?>
                        	<li><?php echo $this->Html->link(__('Subscription Management'), array('plugin' => 'subscription', 'controller' => 'subscribes', 'action' => 'upgrade')) ?></li>
                        <?php endif;?>   

            <?php if ( $cuser['conversation_user_count'] > 0 ): ?>
            <li><a href="<?php echo $this->request->base?>/home/index/tab:messages"><?php echo __('New Messages (%s)', $cuser['conversation_user_count'])?></a></li>
            <?php endif; ?>
            <?php if ( $cuser['friend_request_count'] > 0 ): ?>
            <li><a href="<?php echo $this->request->base?>/home/index/tab:friend-requests"><?php echo __('Friend Requests') . " (<span id='friend_request_count'>" . $cuser['friend_request_count'] . "</span>)" ?></a></li>
            <?php endif; ?>
            <li><a href="<?php echo $this->request->base?>/home/index/tab:invite-friends"><?php echo __('Invite Friends')?></a>
            <li><a href="<?php echo $this->request->base?>/users/do_logout"><?php echo __('Log Out')?></a></li>
        </ul>
    </div>
    <?php endif; ?>
</div>