<?php
    if(empty($settings) || $settings['disable_registration'] != 1): ?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooUser'), 'object' => array('$', 'mooUser'))); ?>
mooUser.initOnRegistration();
<?php $this->Html->scriptEnd(); ?>


<style>
input.button:disabled {color: transparent;}
</style>
<?php
    if ( ( empty($uid)) ):
?>
<div class="bar-content">
     <?php $this->getEventManager()->dispatch(new CakeEvent('View.SocialEnable', $this)); ?>
        <div id="signUpForm" class="row <?php if(!$this->Moo->socialIntegrationEnable('facebook') && !$this->Moo->socialIntegrationEnable('google') && !Configure::read('social.social_enable')): ?>no-social<?php endif; ?>">
            <div class="<?php if($this->Moo->socialIntegrationEnable('facebook') || $this->Moo->socialIntegrationEnable('google') || Configure::read('social.social_enable')): ?>col-md-sl7<?php endif; ?>">
                <div class="register_main_form">
                    <div class="box51">

                        <form id="regForm" class="form-horizontal" >
                            <h1 class="page-header"><?php echo __('Join')?> <?php echo Configure::read('core.site_name')?></h1>
                            <div class="list1" id="regFields">
                                <?php if ( Configure::read('core.enable_registration_code') ): ?>
                                    <div class="form-group required">
                                        <label class="col-md-3 control-label" for="name">
                                            <?php echo __('Registration Code')?> (<a href="javascript:void(0)" class="tip" title="<?php echo __('A registration code is required in order to register on this site')?>">?</a>)
                                        </label>
                                        <div class="col-md-9">
                                            <?php echo $this->Form->text('registration_code',array('class'=>'form-control')) ?>

                                        </div>
                                    </div>

                                <?php endif; ?>
                                <div class="form-group required">
                                    <label class="col-md-3 control-label" for="full-name">
                                        <?php echo __('Full Name')?>
                                    </label>
                                    <div class="col-md-9">
                                        <?php echo $this->Form->text('name',array('class'=>'form-control')) ?>
                                    </div>
                                </div>

                                <div class="form-group required">
                                    <label class="col-md-3 control-label" for="email">
                                        <?php echo __('Email Address')?>
                                    </label>
                                    <div class="col-md-9">
                                        <?php echo $this->Form->text('email',array('class'=>'form-control')) ?>
                                    </div>
                                </div>
                                <div class="form-group required">
                                    <label class="col-md-3 control-label" for="password">
                                        <?php echo __('Password')?>
                                    </label>
                                    <div class="col-md-9">
                                        <?php echo $this->Form->password('password',array('class'=>'form-control')) ?>
                                    </div>
                                </div>
                                <div class="form-group required">
                                    <label class="col-md-3 control-label" for="verify-password">
                                        <?php echo __('Verify Password')?>
                                    </label>
                                    <div class="col-md-9">
                                        <?php echo $this->Form->password('password2',array('class'=>'form-control')) ?>
                                    </div>
                                </div>
								<?php if (Configure::read('core.show_username_signup')): ?>
									<div class="form-group">
	                                    <label class="col-md-3 control-label" for="verify-password">
	                                        <?php echo __('Username')?>
	                                    </label>
	                                    <div class="col-md-9">
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

                            </div>
                            
                            <div id="captcha" style="display:none">
                                <div class="col-md-3">&nbsp;</div>
                                <div class="col-md-9">
                                <?php if ( !empty( $challenge ) ): ?>
                                    <div>
                                        <p><?php echo __('To avoid spam, please answer the following question')?>:</p><?php echo $challenge['SpamChallenge']['question']?><br /><br />
                                        <?php echo $this->Form->text('spam_challenge');?>
                                    </div>
                                <?php endif; ?>

                                <?php $recaptcha_publickey = Configure::read('core.recaptcha_publickey');
                                      
                                    if ( $this->Moo->isRecaptchaEnabled()): ?>

                                    <div class="captcha_box">
                                        <script src='<?php echo $this->Moo->getRecaptchaJavascript();?>'></script>
                                        <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_publickey?>"></div>
                                    </div>
                                <?php endif; ?>
                                </div>
                                <div class="clear"></div>
                                <div class="regSubmit" id="step2Box" class="regSubmit">
                                    <input type="button" value="<?php echo __('Sign Up')?>" id="step2Submit" class="btn btn-success">
                                </div>
                            </div>
                            <div class="form-group regSubmit" id="step1Box">
                                <label class="col-md-3 control-label">&nbsp;</label>
                                <div class="col-md-9">
                                    <input type="button" value="<?php echo __('Continue')?>" id="submitFormsignup" class="btn btn-success">

                                </div>

                            </div>

                        </form>
                        <div id="regError"></div>
                    </div>
                </div>
            </div>                     
            <?php if($this->Moo->socialIntegrationEnable('facebook') || $this->Moo->socialIntegrationEnable('google') || Configure::read('social.social_enable')): ?>
            <div class="col-md-sl3">
                
                <div class="register_social_form">
                    <div class="center-login-text text-center">
                        <span><?php echo  __('Or Register using')?></span>
                    </div>
                    <div style="float:right" class="hide">
                        <a href="<?php echo $this->request->base?>/users/fb_register" ><img src="<?php echo $this->request->webroot?>img/fb_register_button.png"></a>
                    </div>
                    <?php if ($this->Moo->socialIntegrationEnable('facebook')): ?>
                    <div class="fSignInWrapper">
                        <div class="fb-login-button"> </div>
                        <a href="<?php echo  $this->Html->url(array('plugin' => 'social_integration', 'controller' => 'auths', 'action' => 'login', 'provider' => 'facebook')) ?>" style="color:white">
                        <div class="overlay-button">
                            <span class="icon"></span>
                            <span class="buttonText"><?php echo  __('Facebook') ?></span>
                        </div>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if ($this->Moo->socialIntegrationEnable('google')): ?>
                    <div id="gSignInWrapper">
                        <a href="<?php echo  $this->Html->url(array('plugin' => 'social_integration', 'controller' => 'auths', 'action' => 'login', 'provider' => 'google')) ?>" style="color:white">
                        <div id="customBtn" class="customGPlusSignIn">
                            <span class="icon"></span>
                            <span class="buttonText"><?php echo  __('Google') ?></span>
                        </div>
                        </a>
                    </div>                    
                    <?php endif; ?>
                    <?php 
                        if(Configure::read('social.social_enable')){
                            $this->getEventManager()->dispatch(new CakeEvent('View.SocialLogin.Elements', $this)); 
                        }
                    ?>
                </div>
                
            </div>
            <?php endif;?>
        </div>
</div>
    <?php endif; ?>
<?php endif; ?>