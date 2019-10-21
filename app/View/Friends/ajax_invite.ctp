<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGlobal"], function($,mooGlobal) {
        mooGlobal.initInviteFriendBtn('<?php echo $mode?>');
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooGlobal'), 'object' => array('$', 'mooGlobal'))); ?>
mooGlobal.initInviteFriendBtn('<?php echo $mode?>');
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>
<?php if ($mode):?>
<div class="title-modal">
    <?php echo __( 'Invite Your Friends')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<?php endif?>
<div class="content_center_home">
    <div class="post_body">
    	<?php if (!$mode):?>
        <div class="mo_breadcrumb">
        <h1><?php echo __('Invite Your Friends')?></h1>
        </div>
        <?php endif?>
        <div class="full_content p_m_10">
            <?php echo __("Enter your friends' emails below (separated by commas). Limit 10 email addresses per request")?><br /><br />
            <div class="create_form">
                <form id="inviteForm">
                <ul class="list6 list6sm2">
                    <li>
                        <div class="col-md-2">
                            <label><?php echo __('To')?></label>
                        </div>
                        <div class="col-md-10">
                            <?php echo $this->Form->textarea('to'); ?>
                        </div>
                        <div class="clear"></div>
                    </li>
                    <li>
                        <div class="col-md-2">
                            <label><?php echo __('Message')?></label>
                        </div>
                        <div class="col-md-10">
                            <?php echo $this->Form->textarea('message'); ?>
                        </div>
                        <div class="clear"></div>
                    </li>
                    <?php 
                    if ($isMobile && $mode):
                    else:
	                    if ($this->Moo->isRecaptchaEnabled()): ?>
	            		<li>            			
	                        <div class="col-md-2">
	                            <label></label>
	                        </div>
	                        <div class="col-md-10">
	                           <script src='<?php echo $this->Moo->getRecaptchaJavascript();?>'></script>
					           <div class="g-recaptcha" data-sitekey="<?php echo $this->Moo->getRecaptchaPublickey()?>"></div>
	                        </div>
	                        <div class="clear"></div>
					    </li>
					    <?php endif; ?>
				    <?php endif;?>
                    <li>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                        </div>
                        <div class="col-md-10">
                            <a href="javascript:void(0);" class="btn btn-action" id="inviteButton"><?php echo __('Send Invitation')?></a>
                        </div>
                        <div class="clear"></div>
                    </li>
                </ul>
                </form>
            </div>
            <div class="error-message" style="display:none;"></div>
        </div>
    </div>
</div>