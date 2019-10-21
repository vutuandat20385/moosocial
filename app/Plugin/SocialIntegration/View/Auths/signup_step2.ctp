<?php $this->setCurrentStyle(4); ?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooUser'), 'object' => array('$', 'mooUser'))); ?>
mooUser.initOnSocialRegistration(<?php echo $this->Moo->isRecaptchaEnabled() ? $this->Moo->isRecaptchaEnabled() : '0'  ?>, '<?php echo $provider ?>');
<?php $this->Html->scriptEnd(); ?>

<div class="bar-content">
<div id="signUpForm" class="row no-social">
    <div >
        <div class="register_main_form">
            <div class="box51">
                <form id="regForm" class="form-horizontal" >
                    <h1 class="page-header"><?php echo __('Join')?> <?php echo Configure::read('core.site_name')?></h1>
                    <?php if(strstr($email, 'facebook.com')): ?>
                    <div class="fb_email form-group required">
                        <?php echo $this->Form->input('email', array('value' => '')); ?>
                        
                    </div>
                    <?php else: ?>
                    <?php echo $this->Form->hidden('email', array('value' => $email)); ?>
                    <?php endif; ?>
                    
                    <?php if (Configure::read('core.show_username_signup')): ?>
						<div class="form-group">
							<label class="col-md-3 control-label" for="birthday">
	                            <?php echo __('User name')?>
	                        </label>
	                        <div class="col-md-9 ">
	                            <?php echo $this->Form->text('username',array('class'=>'form-control')) ?>
								<div>
								<?php
								$ssl_mode = Configure::read('core.ssl_mode');
								$http = (!empty($ssl_mode)) ? 'https' :  'http';
								?>
								<?php echo $http.'://'.$_SERVER['SERVER_NAME'].$this->base;?>/<span id="profile_user_name"></span>
								</div>
	                        </div>
						</div>
					<?php endif;?>

                    <?php $registration_code = Configure::read('core.enable_registration_code');  ?>
                    <?php if ( $registration_code ): ?>
                    <div class="form-group required">
                        <label class="col-md-3 control-label" for="birthday">
                            <?php echo __('Registration Code')?> <a href="javascript:void(0)" class="tip" title="<?php echo __('A registration code is required in order to register on this site')?>">(?)</a>
                        </label>
                        <div class="col-md-9 ">
                            <?php echo $this->Form->text('registration_code',array('class' => 'form-control')) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php $show_birthday_signup = Configure::read('core.show_birthday_signup');
                    if ( !empty($show_birthday_signup) ): ?>
                    <div class="form-group required">
                        <label class="col-md-3 control-label" for="birthday">
                            <?php echo __('Birthday')?><a href="javascript:void(0)" class="tip" title="<?php echo __('Only month and date will be shown on your profile')?>">(?)</a>
                        </label>
                        <div class="col-md-9 form-inline">
                            <div class="col-xs-4">
                                <?php echo $this->Form->month('birthday',array('class'=>'form-control'))?>
                            </div>
                            <div class="col-xs-4">
                                <div class="p_l_2">
                                    <?php echo $this->Form->day('birthday',array('class'=>'form-control'))?>
                                </div>

                            </div>
                            <div class="col-xs-4">
                                <?php echo $this->Form->year('birthday', 1930, date('Y'),array('class'=>'form-control'))?>
                            </div>
                            <div class="clear"></div>
                        </div>
                    </div>

                    <?php endif; ?>
                    <?php $enable_timezone_selection = Configure::read('core.enable_timezone_selection');
                    if ( !empty($enable_timezone_selection) ): ?>
                    <div class="form-group required">
                        <label class="col-md-3 control-label" for="timezone">
                            <?php echo __('Timezone')?>
                        </label>
                        <div class="col-md-9 ">

                            <?php echo $this->Form->select('timezone', $this->Moo->getTimeZones(), array('value' => Configure::read('core.timezone')), array('class'=>'form-control')); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php $show_gender_signup = Configure::read('core.show_gender_signup');
					if ( !empty($show_gender_signup) ): ?>
                    <div class="form-group required">
                        <label class="col-md-3 control-label" for="gender">
                            <?php echo __('Gender')?>
                        </label>
                        <div class="col-md-9 ">
                            <?php echo $this->Form->select('gender', $this->Moo->getGenderList(), array('value' => 'Male'),array('class'=>'form-control')); ?>
                        </div>
                    </div>
                    <?php endif;?>
                    <?php $show_about_signup= Configure::read('core.show_about_signup');
                    if ( !empty($show_about_signup) ): ?>
                    <div class="form-group required">
                        <label class="col-md-3 control-label" for="gender">
                            <?php echo __('About')?>
                        </label>
                        <div class="col-md-9 ">
                            <?php echo $this->Form->textarea('about',array('class'=>'form-control')); ?>
                        </div>
                    </div>
                    <?php endif;?>
                    
                    <div class="form-group required" id="captcha" style="margin-left:25%;">  
                        <?php if ( !empty( $challenge ) ): ?>
                        <div>
                            <p><?php echo __('To avoid spam, please answer the following question')?>:</p>
                                <?php echo $challenge['SpamChallenge']['question']?><br /><br />
                            <?php echo $this->Form->text('spam_challenge');?>
                        </div>
                        <?php endif; ?>   

                        <?php 
                            if ( $this->Moo->isRecaptchaEnabled()): ?>
                            <div class="captcha_box">
                                <script src='<?php echo $this->Moo->getRecaptchaJavascript();?>'></script>
                                <div class="g-recaptcha" data-sitekey="<?php echo $this->Moo->getRecaptchaPublickey();?>"></div>
                            </div>
                            <?php endif; ?>
                    </div>                    
                    <?php
                    echo $this->element( 'custom_fields', array( 'show_heading' => true ) );
                    ?>
                    <?php if ($isGatewayEnabled): ?>
					    <?php 	
					    	$helper = MooCore::getInstance()->getHelper('Subscription_Subscription');
					    ?>
						<h3 class="page-header"><?php echo __('Membership')?></h3>
						<div class="form-group required">
							
							<?php $this->Html->scriptStart(array('inline' => false)); ?>							
								$('#plan-view').html($('#content_package').html());
								var first = false;
								$('#content_package').remove();
								
								$('.button_select').click(function(){
									$('#plan-view').modal('hide');
									package_id = $(this).attr('ref');		
									$('#select-plan').val($('.package_register_'+package_id).val());
								});
								$('#plan-view').on('shown.bs.modal',function (e) {
									if (first)
										return;
									first = true;
									
									<?php 
										$package_exit = array();
										foreach ($packages as $column): if (!count($column['SubscriptionPackagePlan'])) continue;
											$package_exit[] = $column;
										endforeach;
									?>
									<?php foreach ($package_exit as $package):?>
										$('.package_register_<?php echo $package['SubscriptionPackage']['id'];?>').change(function(){
											$('.package_register_content_<?php echo $package['SubscriptionPackage']['id'];?>').html(plans[$('.package_register_<?php echo $package['SubscriptionPackage']['id'];?>').val()]);
										});
									<?php endforeach?>
									
									$('.compare-register .content').attr('style','');
									max = 0;
									$('.compare-register .content').each(function(e){
										if ($(this).height() > max)
										 	max = $(this).height();
									});
									$('.compare-register .content').each(function(e){
										if ($(this).css('padding-top'))
										{
											$(this).css('height',parseInt($(this).css('padding-top').replace("px", "")) + parseInt($(this).css('padding-bottom').replace("px", "")) + max + 10);
										}
									});
								});
							<?php $this->Html->scriptEnd(); ?>
						    <label for="timezone" class="col-md-3 control-label"><?php echo __('Membership')?></label>
						    <div class="col-md-9 ">
						        <select id="select-plan" name="plan_id">
						            <?php
						            foreach ($packages as $package):
						                $plans = $package['SubscriptionPackagePlan'];
						                if (!count($plans))
						                	continue;
						                $package = $package['SubscriptionPackage'];
						                $plan = array();
						                if(!empty($plans)){
						                    $plan = $plans[0];
						                }
						                ?>
						            <optgroup label="<?php echo $package['name'] ?>">
						                <?php foreach($plans as $index => $plan): ?>
						                	<?php $plan = $plan['SubscriptionPackagePlan']?>
						                    <option <?php if($index == 0 && $package['default'] == 1) echo 'selected'; ?>  value="<?php echo $plan['id']?>"><?php echo $package['name']. ' - '. $helper->getPlanDescription($plan,$currency['Currency']['currency_code'])?></option>
						                <?php endforeach; ?>
						            </optgroup>
						            <?php
						            endforeach;
						            ?>
						            
						        </select>        
						    </div>
						    
						    <label for="timezone" class="col-md-3 control-label"></label>
							<div class="col-md-9 ">
						        <?php
						        echo $this->Html->link(__('Click here to learn more about our memberships.'),
						                '#',
						                array(
						                    'data-target' => '#plan-view',
						                    'data-toggle' => 'modal'
						                ));
						        ?>     
					   	 	</div>
						</div>
					<?php endif; ?>
                    <div class="form-group required">
                    <label for="timezone" class="col-md-3 control-label"></label>
                    <div class="col-md-9 ">
                        <?php echo $this->Form->input('tos',array('type' =>'checkbox','hiddenField' => false,'label' => __('I have read and agree to the ').$this->Html->link(__('terms of service.'),array('plugin' => false, 'controller' => 'pages', 'action' => 'terms-of-service'),array('target'=>'_blank')) ) ); ?>
                    </div>
                    </div>
                    <?php echo $this->Form->hidden('name', array('value' => $name)); ?>
                    <?php echo $this->Form->hidden('password', array('value' => $password)); ?>
                    <?php echo $this->Form->hidden('password2', array('value' => $password2)); ?>
                    <?php echo $this->Form->hidden('avatar', array('value' => $avatar)); ?>

                    <div class="form-group regSubmit" id="step1Box">
                        
                        <div id="step2Box" class="regSubmit">
                            <input type="button" value="<?php echo __('Sign Up')?>" id="step2Submit" class="btn btn-success">
                        </div>
                    </div>
                </form>
                <div id="regError"></div>
            </div>
        </div>
    </div>
</div>
</div>

<?php if ($isGatewayEnabled): ?>
	<div id="content_package" style="display:none;">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="title-modal">
					<?php echo __('Subscription Plans')?>
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">x</span></button>
				</div>
				<?php 
					$element = (Configure::read('Subscription.select_theme_subscription_packages') ? 'Subscription.theme_compare' : 'Subscription.theme_default');
				?>
				<?php echo $this->element($element,array('compares'=>$compare,'columns'=>$packages,'type'=>'register'));?>
			</div>
		</div>
	</div>
<?php endif;?>

