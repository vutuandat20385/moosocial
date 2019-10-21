<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooEvent"], function($,mooEvent) {
        mooEvent.initOnInvitePopup();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooEvent'), 'object' => array('$', 'mooEvent'))); ?>
mooEvent.initOnInvitePopup();
<?php $this->Html->scriptEnd();  ?>
<?php endif; ?>

<div class="title-modal">
    <?php echo __( 'Invite Friends')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body" id="simple-modal-body">
<div class="message" style="display:none;"></div>
<div class='create_form'>
<form id="sendInvite">
<?php echo $this->Form->hidden('event_id', array('value' => $event_id)); ?>
<ul class="list6" style="position:relative">
	<li>
            <div class='col-md-2'>
                <?php echo __( 'Type')?> 
            </div>
            <div class='col-md-10'>
                <div class="m_suggest">
                	<?php echo $this->Form->select('invite_type_event',array('1'=>__('Friends'),'2' => __('Emails')),array('empty' => false)); ?>
                </div>
            </div>
            <div class='clear'></div>
        </li>	
	<li>
	<li id="invite_friend">
            <div class='col-md-2'>
                <?php echo __( 'Friend')?>
            </div>
            <div class='col-md-10'>
            	<div class="m_suggest">
                	<?php echo $this->Form->text('friends'); ?>
                </div>
            </div>
            <div class='clear'></div>
        </li>	
	<li id="invite_email" style="display:none;">
            <div class='col-md-2'>
                 <?php echo __( 'Emails')?>
            </div>
            <div class='col-md-10'>
                <?php echo $this->Form->textarea('emails'); ?>
                 <div class='text-description'>
                    <?php echo __( 'Not on your friends list? Enter their emails below (separated by commas)<br />Limit 10 email addresses per request')?>
                </div>
                <?php if ($this->Moo->isRecaptchaEnabled() && !$isMobile): ?>        			
                <div id="recaptcha_content">
	                    <script src='<?php echo $this->Moo->getRecaptchaJavascript();?>'></script>
			           <div class="g-recaptcha" data-sitekey="<?php echo $this->Moo->getRecaptchaPublickey()?>"></div>
	                    </div>
			    <?php endif; ?>
            </div>
            
            <div class='clear'></div>
	</li>
	<li>
            <div class='col-md-2'>&nbsp;</div>
            <div class='col-md-10'>
                <a href="#" class="button button-action" class="sendButton" id="sendButton"><?php echo __( 'Send Invitations')?></a>
            </div>
        <div class='clear'></div> 
        </li>
</ul>
</form>
</div>
    <div class="error-message" style="display:none;"></div>
</div>