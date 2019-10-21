<?php
if(Configure::read('Group.group_enabled') == 1 && $uid):
$groupHelper = MooCore::getInstance()->getHelper('Group_Group');
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;
if ( !( empty($uid) && Configure::read('core.force_login') ) ):
    $aMyJoinedGroup = $myJoinedGroupWidget;
if (count($aMyJoinedGroup) > 0):
?>
<div class="box2">
    <?php if($title_enable): ?>
    <h3>
        <?php if(empty($title)) $title = "My Joined Groups";?>
        <?php echo $title ; ?>
    </h3>
    <?php endif; ?>
    <div class="box_content">
<?php 

    $i = 1;
    foreach ($aMyJoinedGroup as $group):
?>
        <div class="group_item list-item-inline myJoin-group">
            <a href="<?php echo $this->request->base?>/groups/view/<?php echo $group['Group']['id']?>/<?php echo seoUrl($group['Group']['name'])?>">
                <img width="70" alt="<?php echo $group['Group']['name']?>" src="<?php echo $groupHelper->getImage($group, array('prefix' => '75_square'))?>" class="img_wrapper2" >
            </a>
            <div class="group_detail">
                <div class="title-list">
                    <a href="<?php echo $this->request->base?>/groups/view/<?php echo $group['Group']['id']?>/<?php echo seoUrl($group['Group']['name'])?>"><?php echo $this->Text->truncate($group['Group']['name'], 35 )?></a>
                </div>
                <div>
                    <?php echo __n('%s member', '%s members', $group['Group']['group_user_count'], $group['Group']['group_user_count'] )?>
                </div>
            </div>
            <div class='clear'></div>
        </div>
<?php 
    $i++;
    endforeach;

?>
    </div>
</div>
<?php
endif;
endif;
endif;
?>
