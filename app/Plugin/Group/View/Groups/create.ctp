<?php $this->setCurrentStyle(4) ?>
<?php
$groupHelper = MooCore::getInstance()->getHelper('Group_Group');
?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooGroup'), 'object' => array('$', 'mooGroup'))); ?>
mooGroup.initOnCreate();
<?php $this->Html->scriptEnd(); ?>

<div class="create_form">
    <div class="bar-content">
        <div class="content_center">
            <div class="box3">
                <form id="createForm">
                    <?php
                    if (!empty($group['Group']['id'])){
                        echo $this->Form->hidden('id', array('value' => $group['Group']['id']));
                        echo $this->Form->hidden('photo', array('value' => $group['Group']['photo']));
                    }else{
                        echo $this->Form->hidden('photo', array('value' => ''));
                    }
                    ?>
                    <div class="mo_breadcrumb">
                        <h1><?php if (empty($group['Group']['id'])) echo __( 'Add New Group');
                    else echo __( 'Edit Group'); ?></h1>
                    </div>
                    <div class="full_content p_m_10">
                        <div class="form_content">
                            <ul>
                                <li>
                                    <div class="col-md-2">
                                        <label><?php echo  __( 'Group Name') ?></label>
                                    </div>
                                    <div class="col-md-10">
                                    <?php echo $this->Form->text('name', array('value' => html_entity_decode($group['Group']['name']))); ?>
                                    </div>
                                    <div class="clear"></div>
                                </li>
                                <li>
                                    <div class="col-md-2">
                                        <label><?php echo  __( 'Category') ?></label>
                                    </div>
                                    <div class="col-md-10">
                                    <?php echo $this->Form->select('category_id', $categories, array('value' => $group['Group']['category_id'])); ?>
                                    </div>
                                    <div class="clear"></div>
                                </li>
                                <li>
                                    <div class="col-md-2">
                                        <label><?php echo  __( 'Description') ?></label>
                                    </div>
                                    <div class="col-md-10">
                                        <?php echo $this->Form->tinyMCE('description', array('id' => 'editor','escape'=>false,'value' => $group['Group']['description'])); ?>
                                    </div>
                                    <div class="clear"></div>
                                </li>
                                <li>
                                    <div class="col-md-2">
                                        <label><?php echo  __( 'Group Type') ?> <a href="javascript:void(0)" class="tip" title="<?php echo  __( "<p style='display:inline-block; width:150px;'>Public: anyone can view and join<br />Private: only members can view group's details<br />Restricted: anyone can view but join request has to be accepted by group admins</p>") ?>">(?)</a></label>
                                    </div>
                                    <div class="col-md-10">
                                        <?php
                                        echo $this->Form->select('type', array(PRIVACY_PUBLIC => __( 'Public'),
                                            PRIVACY_PRIVATE => __( 'Private'),
                                            PRIVACY_RESTRICTED => __( 'Restricted')
                                                ), array('value' => $group['Group']['type'], 'empty' => false)
                                        );
                                        ?>
                                        
                                    </div>
                                    <div class="clear"></div>
                                </li>
                                <li>
                                    <div class="col-md-2">
                                        <label><?php echo  __( 'Photo') ?></label>
                                    </div>
                                    <div class="col-md-10">
                                        <div id="select-0" style="margin: 10px 0 0 0px;"></div>
                                        <?php if (!empty($group['Group']['photo'])): ?>
                                        <img width="150" src="<?php echo $groupHelper->getImage($group, array('prefix' => '150_square'))?>" id="item-avatar" class="img_wrapper">
                                        <?php else: ?>
                                        <img width="150" src="" id="item-avatar" class="img_wrapper" style="display: none;">
                                        <?php endif; ?>
                                        
                                    </div>
                                    <div class="clear"></div>
                                </li>
                                <li>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                    </div>
                                    <div class="col-md-10">
                                        <button type='button' id='saveBtn' class='btn btn-action'><?php echo __( 'Save'); ?></button>
                                        
                                        <?php if (!empty($group['Group']['id'])): ?>

                                            <a href="<?php echo  $this->request->base ?>/groups/view/<?php echo  $group['Group']['id'] ?>" class="button"><?php echo  __( 'Cancel') ?></a>

                                        <?php endif; ?>

                                    </div>
                                    <div class="clear"></div>
                                </li>
                            </ul>
                            <div class="error-message" style="display:none;"></div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>