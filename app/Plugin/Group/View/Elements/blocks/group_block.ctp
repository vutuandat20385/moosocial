<?php if (Configure::read('Group.group_enabled') == 1): ?>
    <?php if (!empty($groups)): ?>
        <div class="box2">
            <h3><?php echo  __('Groups') ?></h3>
            <div class="box_content">
                <?php
                $groupHelper = MooCore::getInstance()->getHelper('Group_Group');
                ?>
                <?php if (!empty($groups)): ?>
                    <ul class="list6 list6sm popular_group_block">
                        <?php foreach ($groups as $group): ?>
                            <li class="list-item-inline">
                                <a href="<?php echo  $this->request->base ?>/groups/view/<?php echo  $group['Group']['id'] ?>/<?php echo  seoUrl($group['Group']['name']) ?>">
                                    <img width="70" src="<?php echo  $groupHelper->getImage($group, array('prefix' => '75_square')); ?>" class="img_wrapper2">
                                </a>
                                <div class="group_detail">
                                    <div class="title-list">
                                        <a href="<?php echo  $this->request->base ?>/groups/view/<?php echo  $group['Group']['id'] ?>/<?php echo  seoUrl($group['Group']['name']) ?>"><?php echo  $this->Text->truncate($group['Group']['name'], 50, array('exact' => false)) ?></a><br />
                                    </div>
                                    <div class="like_count">
                                        <?php echo  __n('%s member', '%s members', $group['Group']['group_user_count'], $group['Group']['group_user_count']) ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>