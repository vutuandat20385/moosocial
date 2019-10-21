
<?php if($this->request->is('ajax')) $this->setCurrentStyle(4);?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooUser"], function($,mooUser) {
        mooUser.initUnBlockUser();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooUser'), 'object' => array('$', 'mooUser'))); ?>
mooUser.initUnBlockUser();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<div class="title-modal">
    <?php echo  __('Unblock %s', $user['User']['name'])?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <div class><?php echo  __('Are you sure you want to unblock %s?',$user['User']['name'])?></div>
    <ul class="uiList">
        <li><div class="fcb"><?php echo  __('%s may be able to see your timeline or contact you', $user['User']['name']) ?></div></li>
        <li><div class="fcb"><?php echo  __('%s may be able add you as a friend and contact with you', $user['User']['name']) ?></div></li>
    </ul>
</div>
<div class="modal-footer">
    <form id="unBlockUserForm">
        <input type="hidden" name="user_id" value="<?php echo $user['User']['id']?>">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <a style="float:left" href="javascript:void(0)" data-uid="<?php echo $user['User']['id']?>" id="unBlockUserButton" class="button button-caution"><?php echo __('Confirm')?></a>
                    <a style="float:left; margin-left:3px" href="javascript:void(0)" data-dismiss="modal" class="button button-action"><?php echo __('Cancel')?></a>
                </td>
            </tr>
        </table>
    </form>
</div>