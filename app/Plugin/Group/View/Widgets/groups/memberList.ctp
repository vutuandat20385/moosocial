<?php
if(Configure::read('Group.group_enabled') == 1):
    if (!( empty($uid) && Configure::read('core.force_login') )):
        if (empty($num_item_show))
            $num_item_show = 10;
        if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;
    ?>
        <?php if(!(empty($is_member) && !empty($group) && $group['Group']['type'] == PRIVACY_PRIVATE)): ?>
            <?php if(!empty($data['groupMembers'])): ?>
                <div class="box2 box-member-group">
                <?php if($title_enable): ?>
                    <h3>
                        <?php if (empty($title)) $title = __("Members"); ?>
                        <?php echo  $title ?>(<?php echo  $data['groupMembersCnt']; ?>)
                    </h3>
                <?php endif; ?>
                    <div class="box_content">
                        <?php echo $this->element('blocks/users_block', array('users' => $data['groupMembers'])); ?>
                    </div>
                </div>

<?php
            endif;
        endif;
    endif;
endif;
?>