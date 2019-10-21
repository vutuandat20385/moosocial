<?php if (!Configure::read('core.guest_search') && empty($uid)): ?>
<?php else: ?>
    <div class="box2 search-friend">
        <h3><?php echo __('Search') ?></h3>
        <div class="box_content">
            <form id="filters" method="">
                <ul class="list6">
                    <li><label><?php echo __('Name') ?></label><?php echo $this->Form->text('name'); ?></li>
                    <li><label><?php echo __('Email') ?></label><?php echo $this->Form->text('email'); ?></li>
                    <li><label><?php echo __('Gender') ?></label>
                    <?php echo $this->Form->select('gender', $this->Moo->getGenderList(), array('multiple' => 'multiple', 'class' => 'multi')); ?>
                    </li>
                    <?php if (Configure::read('core.show_about_search')):?>
                    <li><label><?php echo __('About') ?></label><?php echo $this->Form->textarea('about',array('value'=>$about)); ?></li>
                    <?php endif;?>
                        <?php echo $this->element('custom_fields',array('is_search'=>true)); ?>
                    <li><label for="picture"><?php echo __('Profile Picture') ?></label> <?php echo $this->Form->checkbox('picture'); ?> </li>
                    <li><label for="online"><?php echo __('Online Users') ?></label>
                        <?php
                        if (!empty($online_filter))
                            echo $this->Form->checkbox('online', array('checked' => true));
                        else
                            echo $this->Form->checkbox('online');
                        ?>
                    </li>
                    <?php $this->getEventManager()->dispatch(new CakeEvent('View.Elements.User.searchForm.afterRender', $this)); ?>
                    <li style="margin-top:20px"><input type="button" value="<?php echo __('Search') ?>" id="searchPeople" class="btn btn-action"></li>
                </ul>
            </form>
        </div>
    </div>
<?php endif; ?>