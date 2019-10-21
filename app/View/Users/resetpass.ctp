<div class="bar-content resetpass">
    <div class="content_center">
    <h1><?php echo __('Reset Password')?></h1>
    <div class="create_form ">
        <form action="<?php echo $this->request->base?>/users/resetpass" method="post">
            <div class="full_content p_m_10">
                <div class="form_content">
                    <?php echo $this->Form->hidden('code', array('value' => $code)); ?>
                    <ul class="">
                        <li>
                            <div class="col-md-4">
                                <label><?php echo __('New Password')?></label>
                            </div>
                            <div class="col-md-8">
                                <?php echo $this->Form->password('password'); ?>
                            </div>
                            <div class="clear"></div>
                        </li>			
                        <li>
                            <div class="col-md-4">
                                <label><?php echo __('Verify Password')?></label>
                            </div>
                            <div class="col-md-8">
                                <?php echo $this->Form->password('password2'); ?>
                            </div>
                            <div class="clear"></div>
                        </li>
                        <li>
                            <div class="col-md-4">
                            <label>&nbsp;</label>
                            </div>
                            <div class="col-md-8">
                            <?php echo $this->Form->submit(__('Submit'), array('class' => 'btn btn-action')); ?>
                            </div>
                            <div class="clear"></div>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
    </div>
    </div>
</div>