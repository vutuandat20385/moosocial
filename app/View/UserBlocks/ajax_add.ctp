<?php if($this->request->is('ajax')) $this->setCurrentStyle(4);?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooUser"], function($,mooUser) {
        mooUser.initBlockUser();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooUser'), 'object' => array('$', 'mooUser'))); ?>
mooUser.initBlockUser();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<div class="title-modal">
    <?php echo  __('Confirm Block')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <div class><?php echo  __('Are you sure you want to block %s?',$user['User']['name'])?></div>
    <div class="mtl mbs"><?php echo  __('%s will no longer be able to:', $user['User']['name']) ?></div>
    <ul class="uiList">
        <li><div class="fcb"><?php echo  __('See things you post on your timeline') ?></div></li>
        <li><div class="fcb"><?php echo  __("If you're friends, blocking %s will also unfriend this user", $user['User']['name']) ?></div></li>
    </ul>
</div>
<div class="modal-footer">
    <form id="blockUserForm">
        <input type="hidden" name="user_id" value="<?php echo $user['User']['id']?>">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <a style="float:left" href="javascript:void(0)" data-uid="<?php echo $user['User']['id']?>" id="blockUserButton" class="button button-caution"><?php echo __('Ok')?></a>
                    <a style="float:left; margin-left:3px" href="javascript:void(0)" data-dismiss="modal" class="button button-action"><?php echo __('Cancel')?></a>
                </td>
            </tr>
        </table>
    </form>
</div>