<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
<div class="bar-content">
    <div class="profile-info-menu">
        <?php echo $this->element('profilenav', array("cmenu" => "email_settings"));?>
    </div>
</div>
<?php $this->end(); ?>
<div class="bar-content ">
    <div class="content_center profile-info-edit">
        <form method="post">
        <div id="center" class="post_body">
            <div class="mo_breadcrumb">
                 <h1><?php echo __('Email notification settings')?></h1>
            </div>
            <div class="full_content">
                <div class="content_center">
                    <div class="edit-profile-section">
                        <ul class="profile-checkbox">
                        	<li class="checkbox" >
                                <label>
                                    <?php echo $this->Form->checkbox('unsubcribe_email', array('checked' => ($unsub) ? 1 : 0)); ?>
                                    <?php echo __('Unsubcribe all emails (if this option is enable, the below setttings will be ignored by system')?>
                                </label>
                            </li>
                            <li class="checkbox" >
                                <label>
                                    <?php echo $this->Form->checkbox('notification_email', array('checked' => $user['User']['notification_email'])); ?>
                                    <?php echo __('Daily notification summary email')?>
                                </label>
                            </li>
                            <li class="checkbox" >
                                <label>
                                    <?php echo $this->Form->checkbox('request_friend_email', array('checked' => $user['User']['request_friend_email'])); ?>
                                    <?php echo __('Friend Request email')?>
                                </label>
                            </li>
                            <?php
                            $disableMessageAction = false;
                            if (Configure::read('Chat.chat_turn_on_notification') !== NULL){
                                if(Configure::read('Chat.chat_turn_on_notification')==1){
                                    $disableMessageAction = true;
                                }
                            }
                            
                            
                            if (!$disableMessageAction)
                            {
                            	if (!Configure::read('core.time_notify_message_unread'))
                            	{
                            		$disableMessageAction = true;
                            	}
                            }
                            ?>

                            <li class="checkbox"  <?php if ($disableMessageAction): ?> style="display: none;" <?php endif;?>>
                                <label>
                                    <?php echo $this->Form->checkbox('send_email_when_send_message', array('checked' => $user['User']['send_email_when_send_message'])); ?>
                                    <?php echo __('Member will get email if he does not read message at a specific time (%s)',Configure::read('core.time_notify_message_unread').' '.__('minutes'))?>
                                </label>
                            </li>

                            <?php
                                $this->getEventManager()->dispatch(new CakeEvent('User.EmailSetting.View', $this));
                            ?>
                        </ul>
                        <div class="col-md-9">
                            <div style="margin-top:10px"><input type="submit" value="<?php echo __('Save Changes'); ?>" class="btn btn-action"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>