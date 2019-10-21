<script type="text/javascript">
    $('#friendTaggedBtn').click(function(){
       $.post("<?php echo $this->request->base?>/friends/tagged_save", jQuery("#friendTagged").serialize(), function(data){
            window.location.reload();
        }); 
    });
</script>

<div class="title-modal">
    <?php echo __('Tag Friends') ?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<form id="friendTagged">
    <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>" />
    <div class="modal-body">
        <ul class="tagged_user">
            <?php foreach ($friendList as $user_id): ?>
                <li>
                    <input type="checkbox" <?php if(in_array($user_id, $userTagged)) echo 'checked'; ?> name="friends[]" value="<?php echo $user_id ?>" />
                    
                        <?php $user = $this->MooPeople->get($user_id);?>
                    <div><?php echo $this->Moo->getItemPhoto(array('User' => $user['User']), array('prefix' => '50_square')) ?></div>
                    <div><?php echo $this->Moo->getName($user['User']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="clear"></div>
    </div>
    <div class="modal-footer">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <a style="float:left" href="javascript:void(0)" id="friendTaggedBtn" class="button button-caution"><?php echo __('Submit') ?></a>
                    <a style="float:left; margin-left:3px" href="javascript:void(0)" data-dismiss="modal" class="button button-action"><?php echo __('Cancel') ?></a>
                </td>
            </tr>
        </table>
    </div>
</form>