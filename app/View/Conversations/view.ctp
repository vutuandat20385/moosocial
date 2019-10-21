<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGlobal"], function($,mooGlobal) {
        mooGlobal.initLeaveConversation();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooGlobal'), 'object' => array('$', 'mooGlobal'))); ?>
mooGlobal.initLeaveConversation();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
<div class="bar-content">
	<div class="box2">
		<h3><?php echo __('Participants')?> (<?php echo count($convo_users)?>)</b></h3>
		<div class="box_content">
			<ul class="list6 list6sm">
				<?php
				foreach ($convo_users as $convo_user):
				?>
				<li><?php echo $this->element( 'misc/user_mini', array( 'user' => $convo_user['User'], 'areFriends' => in_array( $convo_user['User']['id'], $friends) ) ); ?></li>
				<?php
				endforeach;
				?>
			</ul>
		</div>
	</div>
	
	<div class="box2">
            <div class="box_content">
		<ul class="list6 list6sm">
                        <?php if(!count($pair_blocker)): ?>
			<li>
                            <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "conversations",
                                            "action" => "ajax_add",
                                            "plugin" => false,
                                            $conversation['Conversation']['id']
                                            
                                        )),
             'title' =>  __('Add People To This Conversation'),
             'innerHtml'=> __('Add People'),
     ));
 ?>
                            </li>
                            <?php endif; ?>
			<li><a href="javascript:void(0)" class="leaveConversation" data-msg="<?php echo addslashes(__('Are you sure you want to leave this conversation'))?>" data-url="<?php echo $this->request->base?>/conversations/do_leave/<?php echo $conversation['Conversation']['id']?>"><?php echo __('Leave Conversation')?></a></li>
		</ul>
            </div>
	</div>
</div>
<?php $this->end(); ?>
<div class="bar-content">
    <div class="content_center">
        <div class="mo_breadcrumb">
            <h1><?php echo h($conversation['Conversation']['subject'])?></h1>
            <a href="<?php echo $this->request->base?>/home/index/tab:messages" class="button topButton button-mobi-top"><?php echo __('Back to Messages')?></a>
        </div>
        <div class="full_content p_m_10">
	
	<div class="convo_msg comment_wrapper" style="margin-bottom:10px">
		<?php echo $this->Moo->getItemPhoto(array('User' => $conversation['User']),array( 'prefix' => '100_square'), array('class' => 'img_wrapper2 user_avatar_large'))?>
		<div class="comment">
			<?php echo $this->Moo->getName($conversation['User'])?>
			<div class="comment_message"><?php echo $this->Moo->formatText( $conversation['Conversation']['message'], false, true , array('no_replace_ssl' => 1))?></div>
			<span class="date"><?php echo $this->Moo->getTime($conversation['Conversation']['created'], Configure::read('core.date_format'), $utz)?></span>
		</div>
	</div>
        </div>
         </div>
</div>
<div class="bar-content full_content p_m_10 conversations-comment">
    <div class="content_center">
        <?php if($pair_blocker && count($pair_blocker)): ?>
            <?php echo __('%1$s blocked %2$s so that this conversation can\'t continue!',$pair_blocker['block_user']['name'],$pair_blocker['blocked_user']['name']) ?>
        <?php endif; ?>
        	<?php if (!count($pair_blocker)): ?>
            <h2><?php echo __('Messages')?> (<span id="comment_count"><?php echo $conversation['Conversation']['message_count']?></span>)</h2>
            <?php endif;?>
            <?php if (Configure::read('core.comment_sort_style') == COMMENT_RECENT): ?>
            <?php
            if (!empty($uid) && !count($pair_blocker)) 
                    echo $this->element('comment_form', array('target_id' => $conversation['Conversation']['id'], 'type' => APP_CONVERSATION));
            ?>
            <ul class="list6 comment_wrapper" id="comments">
            <?php echo $this->element('comments');?>
            </ul>
            <?php elseif(Configure::read('core.comment_sort_style') == COMMENT_CHRONOLOGICAL): ?>
            <ul class="list6 comment_wrapper" id="comments">
            <?php echo $this->element('comments_chrono');?>
            </ul>
            <?php
            if (!empty($uid) && !count($pair_blocker)) 
                    echo $this->element('comment_form', array('target_id' => $conversation['Conversation']['id'], 'type' => APP_CONVERSATION));
            ?>
            <?php endif; ?>
    </div>
</div>