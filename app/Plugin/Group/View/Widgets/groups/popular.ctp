<?php
if(Configure::read('Group.group_enabled') == 1):
$groupHelper = MooCore::getInstance()->getHelper('Group_Group');
if(empty($title)) $title = "Popular Groups";
if(empty($num_item_show)) $num_item_show = 10;
if(isset($title_enable)&&($title_enable)=== "") $title_enable = false; else $title_enable = true;

$popular_groups = $popularGroupWidget;
?>
<?php if (!empty($popular_groups)): ?>
<div class="box2">
    <?php if($title_enable): ?>
    <h3><?php echo $title; ?></h3>
    <?php endif; ?>
    <div class="box_content">
        <?php
        if (!empty($popular_groups)):
            ?>
            <ul class="group-block">
                <?php foreach ($popular_groups as $group): ?>
                    <li>
                        <a href="<?php echo $this->request->base?>/groups/view/<?php echo $group['Group']['id']?>/<?php echo seoUrl($group['Group']['name'])?>">
                            <img width="70" alt="<?php echo $group['Group']['name']?>" src="<?php echo $groupHelper->getImage($group, array('prefix' => '75_square'))?>" class="group-thumb" >
                        </a>
                        <div class="group_detail">
                            <div class="title-list">
                                <a href="<?php echo $this->request->base?>/groups/view/<?php echo $group['Group']['id']?>/<?php echo seoUrl($group['Group']['name'])?>"><?php echo $group['Group']['name'] ?></a>

                            </div>
                            <div class="like_count">
                                <?php echo __n('%s member', '%s members', $group['Group']['group_user_count'], $group['Group']['group_user_count'] )?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php
        else:
            echo __('Nothing found');
        endif;
        ?>
    </div>
</div>
<?php endif;
endif; ?>