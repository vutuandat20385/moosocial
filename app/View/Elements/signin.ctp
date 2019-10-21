<?php if($this->Auth->user('id') === null): ?>
    <?php //echo $this->Session->flash(); ?>
<div class="bar-content">
</div>

<div id="fb-root"></div>

<div class="loginPage">
<div id="loginForm">
    <h1 class="text-center"><?php echo __('Member Login')?></h1>
    <div class="main_login_form">
    <?php
    $form = $this->Form->create('User', array(
            'class' => 'form-horizontal',
            'url'=>array('plugin' => false, 'controller'=>'users','action'=>'member_login')
        )
    );
    $this->Form->inputDefaults(array(
            'label' => false,
            //'div' => array("class" => 'form-group'),
            'class' => 'form-control',

        )
    );
    $form .=$this->Form->input('id');
    $form .=$this->Form->input('email', array(
            'id' => 'login_email',
            'placeholder' => __('Email'),
            //'name'=>"data[email]",

        )
    );
    $form .=$this->Form->input('password', array(
            'id' => 'login_password',
            'placeholder' => __('Password'),
            //'name' => 'data[password]',
            'class' => 'form-control',
            'required' => false
        )
    );
    $pararms = array('name'=>'data[redirect_url]');
    if (isset($redirect_url))
        $pararms['value'] = $redirect_url;
    $form .=$this->Form->hidden('redirect_url', $pararms);
    $form .=$this->Form->submit(__('Sign in'), array(
            'class'=>'btn btn-success btn-login',
            'value' => __('Sign in'),
            'div'=>false

        )
    );
    echo $form;
    ?>
    <div class="row p_top_15">
        <div class="col-md-6 text-left"><!--login-box-->
            <input type="hidden" value="0" id="remember_" name="data[remember]">
            <input type="checkbox" id="remember" value="1" checked="checked" name="data[remember]"> <?php echo __('Remember me')?>

        </div>
        <div class="col-md-6 text-right">
            <a href="<?php echo $this->request->base ;?>/users/recover"><?php echo __('Forgot password?')?></a>
        </div>
    </div>

    </div>
    <?php $this->getEventManager()->dispatch(new CakeEvent('View.SocialEnable', $this)); ?>
    <?php if($this->Moo->socialIntegrationEnable('facebook') || $this->Moo->socialIntegrationEnable('google') || Configure::read('social.social_enable')): ?>
    <div class="register_social_form">
        <div class="center-login-text text-center">
            <span><?php echo  __('Or using')?></span>
        </div>
        <?php if ($this->Moo->socialIntegrationEnable('facebook')): ?>
        <div class="fSignInWrapper">
            <!-- <div class="fb-login-button"> </div> -->
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
    <?php endif; ?>
   
	</form>
</div>
</div>
<?php
echo $this->Html->script(

    array(
        'global/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js?'. Configure::read('core.version'),
    ),
    array('inline'=>false)
);
?>
<?php endif; ?>
