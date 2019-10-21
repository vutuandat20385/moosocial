<?php
if(!empty($site_rtl)){
	echo $this->Html->css('rtl');
}
if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery", "mooShare"], function ($, mooShare) {
        mooShare.initOnAjaxShare({
            'social_link_share': '<?php echo $social_link_share; ?>',
            'do_share_url': '<?php
                echo $this->Html->url(array(
                    'plugin' => false,
                    'controller' => 'share',
                    'action' => 'do_share'
                ));
                ?>'
        });
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooShare'), 'object' => array('$', 'mooShare'))); ?>
mooShare.initOnAjaxShare({
'social_link_share' : '<?php echo $social_link_share; ?>',
'do_share_url' : '<?php
                echo $this->Html->url(array(
                    'plugin' => false,
                    'controller' => 'share',
                    'action' => 'do_share'
                ));
                ?>'
});
$('.share-content a').attr('target','_blank');
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php
$action = empty($plugin) ? $activity['Activity']['action'] : $type;
$object_id = empty($object_id) ? $activity['Activity']['id'] : $object_id;
?>

<div>
    <!-- Nav tabs -->
    <div class="left"><?php echo __('Share on') ?></div>
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="list_tabshare">
            <div  id="myTabDrop1" ><?php echo __('My Wall'); ?><span class="caret"></span></div>
            <ul id="myTabDrop1-contents">
                <li class="active">
                    <a href="#me" role="tab" id="dropdown1-tab" data-toggle="tab" aria-controls="dropdown1" aria-expanded="false" onclick="$('#myTabDrop1-contents').hide()"><?php echo __('My Wall'); ?><span class="caret"></span></a>
                </li>
                <li class="">
                    <a href="#friend" role="tab" id="dropdown2-tab" data-toggle="tab" aria-controls="dropdown2" aria-expanded="true" onclick="$('#myTabDrop1-contents').hide()"><?php echo __('Friend Wall'); ?><span class="caret"></span></a>
                </li>
                <li class="">
                    <a href="#group" role="tab" id="dropdown3-tab" data-toggle="tab" aria-controls="dropdown3" aria-expanded="true" onclick="$('#myTabDrop1-contents').hide()"><?php echo __('Group Wall'); ?><span class="caret"></span></a>
                </li>
                <?php
                    $disableMessageAction = false;
                    if (Configure::read('Chat.chat_turn_on_notification') !== NULL){
                        if(Configure::read('Chat.chat_turn_on_notification')==1){
                            $disableMessageAction = true;
                        }
                    }
                ?>
                <li class="" <?php if ($disableMessageAction): ?> style="display: none;" <?php endif;?>>
                    <a href="#msg" role="tab" id="dropdown4-tab" data-toggle="tab" aria-controls="dropdown4" aria-expanded="true" onclick="$('#myTabDrop1-contents').hide()"><?php echo __('Messages'); ?><span class="caret"></span></a>
                </li>

                <li class="">
                    <a href="#email" role="tab" id="dropdown5-tab" data-toggle="tab" aria-controls="dropdown5" aria-expanded="true" onclick="$('#myTabDrop1-contents').hide()"><?php echo __('Email'); ?><span class="caret"></span></a>
                </li>
                <?php if (!empty($plugin)): ?>
                <li class="sharethis">
                    <a href="#socialshare"><?php echo __('Share This'); ?><span class="caret"></span></a>
                </li>
                <?php endif; ?>
            </ul>
        </li>
    </ul>
    <!-- Tab panes -->

    <form method="post" class="activity_share_form" name="activity_share_form" id="activity_share_form">

        <div class="tab-content share_content">


            <div role="tabpanel" class="tab-pane fade in active" id="me">

            </div>

            <div role="tabpanel" class="tab-pane fade" id="friend">
                <div class="left"><?php echo __('Select Friend') ?></div>
                <div class="suggestion userTagging">
                    <?php echo $this->Form->friendSuggestion(); ?>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane fade" id="group">
                <div class="left"><?php echo __('Select Group') ?></div>
                <div class="suggestion groupSuggestion" style="display:block;">
                    <?php echo $this->Form->groupSuggestion(); ?>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane fade" id="msg">

            </div>

            <div role="tabpanel" class="tab-pane fade" id="email">
                <div class="left"><?php echo __('Email Address') ?></div>
                <div class="suggestion shareViaEmail" style="display:block;">
                    <?php echo $this->Form->textarea('email', array('placeholder' => __('Enter their emails (separated by commas). Limit 10 email addresses per request')), false, false); ?>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane fade " id="socialshare">

            </div>
            <div class="left"><?php echo __('Message') ?></div>
            <div class="params">
                <?php
                echo $this->Form->hidden('share_type', array('value' => '#me'));
                echo $this->Form->hidden('action', array('value' => $action));
                echo $this->Form->hidden('param', array('value' => $param));

                echo $this->Form->hidden('object_id', array('value' => $object_id));
                echo $this->Form->textarea('message', array('name' => 'messageText', 'placeholder' => __('Say something about this...'), 'onfocus' => ''), true);
                echo $this->Form->textarea('messageHidden', array('style' => 'display:none', 'name' => 'messageTextHidden', 'placeholder' => __('Say something about this...'), 'onfocus' => ''));
                ?>               
            </div>
			<div class="clear"></div>
			<?php if ($this->Moo->isRecaptchaEnabled() && !$isMobile): ?>        			
				<div style="display:none;" id="recaptcha_content">
				   <script src='<?php echo $this->Moo->getRecaptchaJavascript();?>'></script>
				   <div class="g-recaptcha" data-sitekey="<?php echo $this->Moo->getRecaptchaPublickey()?>"></div>
				</div>
			<?php endif; ?>
            <div id="shareWarning"></div>
            <div class="share-content">
                <?php if (empty($plugin)): ?>

                    <?php
                    $item_type = $activity['Activity']['item_type'];
                    if ($activity['Activity']['plugin']) {
                        $options = array('plugin' => $activity['Activity']['plugin']);
                    } else {
                        $options = array();
                    }

                    if ($item_type) {
                        list($plugin, $name) = mooPluginSplit($item_type);
                        $object = MooCore::getInstance()->getItemByType($item_type, $activity['Activity']['item_id']);
                    } else {
                        $plugin = '';
                        $name = '';
                        $object = null;
                    }
                    ?>

                    <?php echo $this->element('activity/share/' . $activity['Activity']['action'], array('activity' => $activity, 'object' => $object), $options); ?>
                <?php else: ?>
                    <?php echo $this->element('activity/share/' . $type, array('object' => $object), array('plugin' => $plugin)); ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($activity) || !empty($object)): ?>
            <?php
            $cond1 = isset($object) && isset($object[key($object)]['moo_privacy']) && $object[key($object)]['moo_privacy'] == PRIVACY_FRIENDS;
            $cond2 = isset($activity['Activity']['privacy']) && $activity['Activity']['privacy'] == PRIVACY_FRIENDS;
            
            $username = isset($object['User']['name']) ? $object['User']['name'] : $activity['User']['name'];
            ?>
            <?php if ($cond1 || $cond2): ?>
            <div class="privacy-notice">
                <div class="title"><span>?</span><?php echo __('Who can see this?'); ?></div>
                <div class="message"><?php echo __('%s chose a specific audience for this post. Only people in that audience will be able to see this when you share it', $username); ?></div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="userTagged" style="display:block">
                <div>
                    <?php echo $this->Form->userTagging('', 'userTaggingOnShareForm', true); ?>  
                </div>
                <div class="stt-action share-action">

                    <div class="user-tagging-container">
                        <i class="" data-toggle="tooltip" title="<?php echo __('Tag people in your post'); ?>" onclick="$('.userTagging-userTaggingOnShareForm').toggleClass('hidden');$('.userTagging-userTagging input').focus()"><em class="material-icons">person_add</em></i>
                    </div>
                    <input class="btn btn-action" type="button" id="shareBtn" name="share" value="<?php echo __('Share'); ?>" />
                </div>
            </div>

        </div>
    </form>
</div>