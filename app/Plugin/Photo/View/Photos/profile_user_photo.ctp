<?php if($this->request->is('ajax')): ?>
<script>
    require(["jquery","mooUser"], function($,mooUser) {
        mooUser.initShowAlbums();
    });
</script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false,'requires'=>array('jquery','mooUser'),'object'=>array('$','mooUser'))); ?>
    mooUser.initShowAlbums();
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<div class="bar-content photo_profile">
    <div class="content_center">

        <div class="title_center p_m_10">
            <?php if ($tag_uid == $uid): ?>
                <div class="bar-content profile-sub-menu pull-right">
                    <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "albums",
                                            "action" => "create",
                                            "plugin" => 'photo',
                                           
                                        )),
             'title' => __( 'Create New Album'),
             'innerHtml'=> __( 'Create New Album'),
          'class' => 'topButton button button-action'
     ));
 ?>
                  
                </div>
            <?php endif; ?>
            <h2 style="margin-top: 0px;"><?php echo  __( 'Photo Albums') ?></h2>
        </div>

        <ul class="albums photo-albums" id="album-list-content">
            <?php echo $this->element('lists/albums_list'); ?>
        </ul>
        <div class="view-all-bottom"><a href="javascript:void(0)" data-user-id="<?php echo $tag_uid;?>" class="showAlbums"><?php echo  __( 'View all') ?></a></div>
        <div class="clear"></div>

    </div>
</div>
<div class="bar-content photo_profile">
    <div class="content_center box-tagged-photo">
        <div class="title_center p_m_10">
            <h2><?php echo  __( 'Tagged Photos') ?></h2>
        </div>
        <div class="full_content p_m_10">
            <?php echo $this->element('lists/photos_list', array('type' => APP_USER, 'param' => $tag_uid)); ?> 
        </div>
        <div class="clear"></div>
    </div>
</div>