<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <?php echo $this->Html->charset(); ?>
        <title>
        <?php echo __('Offline Mode')?>
        </title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
        <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <?php
    echo $this->Html->meta('icon');
    echo $this->Html->css( array('main', 'all') );
    //echo $this->Html->script( array('scripts', 'global','moocore/ServerJS.js') );
    $this->loadLibarary('mooCore');
    echo $this->fetch('config');
    echo $this->fetch('mooPhrase');
    echo $this->fetch('mooScript');
    echo $this->fetch('script');
    echo $this->Minify->render();
    ?>
    </head>
    <body class="maintenance-page">
        <script type="text/javascript">
            require(["jquery"], function ($) {
                $(function () {
                    $('#loginButton').on('click', function () {
                        $('#loginForm').toggle();
                    });
                });
            });
        </script>
        <div class="sl-navbar" id="header" style="min-height: 50px;">
            <div class="container full_header">

        <?php echo $this->element('misc/logo'); ?>
        <?php echo $this->element('userbox'); ?>
                <!--Login form-->
                <a href="#" class="button button-flat-primary button-flat" id="loginButton"> <?php echo __('Login')?> <i class="material-icons">expand_more</i></a>
                <div id="loginForm" class="moo-dropdown">
                    <div class="dropdown-caret right">
                        <span class="caret-outer"></span>
                        <span class="caret-inner"></span>
                    </div>
                    <form action="<?php echo $this->request->base?>/users/login" method="post">
            <?php echo $this->Form->email( 'email', array( 'placeholder' => __('Email'), 'id' => 'login_email', 'name' => 'data[User][email]' ) )?>
            <?php echo $this->Form->password( 'password', array( 'placeholder' => __('Password'), 'id' => 'login_password', 'name' => 'data[User][password]') )?>
                        <input type="submit" value="<?php echo __('Login')?>" class="button button-action">
                            <div class="login-box">
                <?php echo $this->Form->checkbox( 'remember', array( 'checked' => true ) )?> <?php echo __('Remember me')?>
                            </div>
                            <p><a href="<?php echo $this->request->base?>/users/recover"><?php echo __('Forgot password?')?></a></p>
            <?php
            if ( !empty( $return_url ) )
                echo $this->Form->hidden( 'return_url', array( 'value' => $return_url ) );
            ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="container">
            <div id="content">
        <?php echo $this->Session->flash(); ?>
                <h1><?php echo __('Sorry, our site is temporarily down for maintenance. Please check back again soon.')?></h1>
        <?php echo nl2br($offline_message)?>
                
                <div id="loginForm" class="login_offline" >
                <form  action="<?php echo $this->request->base?>/users/login" method="post">
            <?php echo $this->Form->email( 'email', array( 'placeholder' => __('Email'), 'id' => 'login_email', 'name' => 'data[User][email]' ) )?>
            <?php echo $this->Form->password( 'password', array( 'placeholder' => __('Password'), 'id' => 'login_password', 'name' => 'data[User][password]') )?>
                        <input type="submit" value="<?php echo __('Login')?>" class="button button-action">
                            <div class="login-box">
                <?php echo $this->Form->checkbox( 'remember', array( 'checked' => true ) )?> <?php echo __('Remember me')?>
                            </div>
                            <p><a href="<?php echo $this->request->base?>/users/recover"><?php echo __('Forgot password?')?></a></p>
            <?php
            if ( !empty( $return_url ) )
                echo $this->Form->hidden( 'return_url', array( 'value' => $return_url ) );
            ?>
                    </form>
                </div>

            </div>
        </div><?php die(); ?>
    </body>
</html> 