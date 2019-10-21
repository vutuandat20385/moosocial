<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGlobal"], function($,mooGlobal) {
        mooGlobal.initConversationSendBtn();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooGlobal'), 'object' => array('$', 'mooGlobal'))); ?>
mooGlobal.initConversationSendBtn();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php if(empty($notAllow)): ?>
    <div class="title-modal">
        <?php echo __('Send New Message')?>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
    </div>
    <div class="modal-body">
    <div class="create_form">
    <form id="sendMessage">
    <ul class="list6 list6sm2" style="position:relative">
        <?php if (!empty($to)): ?>
        <input type="hidden" name="data[friends]" value="<?php echo $to['User']['id']?>">
        <li>
                <div class="col-sm-2">
                    <label><?php echo __('To')?></label>
                </div>
                <div class="col-sm-10">
                    <?php echo $to['User']['name']?>
                </div>
                <div class="clear"></div>
            </li>
        <?php else: ?>
        <li>
                <div class="col-sm-2">
                    <label><?php echo __('To')?></label>
                </div>
                <div class="col-sm-10">
                    <?php echo $this->Form->text('friends'); ?>
                </div>
                 <div class="clear"></div>
            </li>
        <?php endif; ?>
        <li>
                <div class="col-sm-2">
                <label><?php echo __('Subject')?></label>
                </div>
                <div class="col-sm-10">
                    <?php echo $this->Form->text('subject'); ?>
                </div>
                 <div class="clear"></div>
            </li>
        <li>
                <div class="col-sm-2">
                    <label><?php echo __('Message')?></label>
                </div>
                <div class="col-sm-10">
                    <?php echo $this->Form->textarea('message', array('style' => 'height:120px')); ?>
                </div>
                 <div class="clear"></div>
            </li>
        <li>
                <div class="col-sm-2">
                    <label>&nbsp;</label>
                </div>
                <div class="col-sm-10">
                    <a href="javascript:void(0);" class="button button-action" id="sendButton"><?php echo __('Send Message')?>
                    </a>
                </div>
                 <div class="clear"></div>
            </li>
    </ul>
    </form>
    </div>
    <div class="error-message" style="display:none;"></div>
    </div>
<?php else: ?>
    <div class="title-modal">
        <?php echo __('Warning')?>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
    </div>
    <div class="modal-body">
        <span><?php echo __('This person is receiving messages from Friends only') ?></span>
    </div>
    <div class="modal-footer">
        <button class="btn btn-action" data-dismiss="modal"><?php echo __('Close'); ?></button>
    </div>
<?php endif; ?>