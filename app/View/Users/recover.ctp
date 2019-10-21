<?php //$this->setCurrentStyle(4);?>
<div class="bar-content full_content p_m_10">
    <div class="content_center">
        <h1><?php echo __('Forgot Password')?></h1>

        <?php
        if ($state == 'sent'):
            echo __('An email has been sent to the email address that you entered<br />Please follow the instructions to reset your password<br />Check your spam folder if you don\'t see it');
        else:
        ?>
        <p><?php echo __('Please enter your email to reset your password')?></p>
        <form action="<?php echo $this->request->base?>/users/recover" method="post" class="forgot-form">
            <?php echo $this->Form->text('email'); ?>
            <input type="submit" value="<?php echo __('Submit')?>" class="btn btn-action">
            </form>
        <?php
        endif;
        ?>
    </div>
</div>