<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery', 'mooUser'), 'object' => array('$', 'mooUser'))); ?>
    mooUser.initOnProfilePicture();
<?php $this->Html->scriptEnd(); ?>
    
<div class="bar-content full_content p_m_10">
    <div class="content_center">
        <div class="mo_breadcrumb">
            <h1> <?php echo  __('Profile Picture') ?></h1>
        </div>
        <div class="ava_content">
            <div id="avatar_wrapper" style="vertical-align: top;margin: 0 10px 10px 0">
                <img src="<?php echo $this->Moo->getImageUrl(array('User' => $cuser), array('prefix' => '600'))?>"  id="av-img2">
            </div>
            <?php if($this->Storage->isLocalStorage()):?>
            <div class="avatar-rotate" style="<?php echo empty($cuser['avatar']) ? 'display:none' : '';?>">
                <a href="#" id="rotate_right" data-mode="left" aria-haspopup="true" role="button" aria-expanded="false" class="rotate_avatar" title="<?php echo __('Rotate Left');?>">
                    <i class="material-icons notranslate">rotate_left</i>
                </a>
                <a href="#" id="rotate_right" data-mode="right" aria-haspopup="true" role="button" aria-expanded="false" class="rotate_avatar" title="<?php echo __('Rotate Right');?>">
                    <i class="material-icons notranslate">rotate_right</i>
                </a>
            </div>
            <?php endif;?>
            <div class="Metronic-alerts alert alert-warning fade in ava-upload" style="margin-bottom: 20px;"><?php echo __("Optimal size 200x200px"); ?></div>

            <div id="select-0" class="ava-upload"></div>
            <div class="">
                <button id="save-avatar" data-url="<?php echo $this->Moo->getProfileUrl( $cuser )?>" type="button" class="btn btn-action save-avatar"><span aria-hidden="true"><?php echo  __('Save Thumbnail') ?></span>
                </button>
                <a id="submit-avatar" href="<?php echo $this->request->base; ?>/users/view/<?php echo $cuser['id']; ?>"; type="button" class="btn btn-action submit-avatar hide"><span aria-hidden="true"><?php echo  __('Submit') ?></span>
                </a>
            </div>
        </div>
        
    </div>
</div>
