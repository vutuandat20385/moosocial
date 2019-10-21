<?php echo  $this->Session->flash(); ?>
<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
<div class="bar-content">
    <div class="profile-info-menu">
        <?php echo $this->element('profilenav', array("cmenu" => "password"));?>
    </div>
</div>
<?php $this->end(); ?>
<div class="bar-content ">
    <div class="content_center profile-info-edit">
        <form action="<?php echo $this->request->base?>/users/password" method="post">
        <div id="center" class="post_body">
            <div class="mo_breadcrumb">
            <h1><?php echo __('Change Password')?></h1>
            </div>
             <div class="full_content">
                <div class="content_center">
                    <div class='edit-profile-section'>
                        <p><?php echo __('To change your password, please enter your current password to for verification')?></p>

                        <ul>
                            <li>
                                <div class='col-md-3'>
                                    <label><?php echo __('Current Password')?></label>
                                </div>
                                <div class='col-md-9'>
                                    <?php echo $this->Form->password('old_password'); ?>
                                </div>
                                <div class='clear'></div>
                            </li>     
                            <li>
                                <div class='col-md-3'>
                                    <label><?php echo __('New Password')?></label>
                                </div>
                                <div class='col-md-9'>
                                    <?php echo $this->Form->password('password'); ?>
                                </div>
                                <div class='clear'></div>
                            </li>         
                            <li>
                                <div class='col-md-3'>
                                    <label><?php echo __('Verify Password')?></label>
                                </div>
                                <div class='col-md-9'>
                                    <?php echo $this->Form->password('password2'); ?>
                                </div>
                                <div class='clear'></div>
                            </li>
                        </ul>
                        <div class='col-md-3'>&nbsp;</div>
                        <div class='col-md-9'>
                            <div style="margin-top:10px"><input type="submit" value="<?php echo __('Change Password')?>" class="btn btn-action"></div>
                        </div>
                        <div class='clear'></div>
                    </div>
                </div>
             </div>
        </div>
        </form>
    </div>
</div>