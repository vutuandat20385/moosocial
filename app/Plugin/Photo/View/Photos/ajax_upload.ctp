<?php if($this->request->is('ajax')) $this->setCurrentStyle(4);?>

<?php
if ($target_id):
?>


<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGroup"], function($, mooGroup) {
        mooGroup.initTabPhoto2();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery', 'mooGroup'), 'object' => array('$', 'mooGroup'))); ?>
mooGroup.initTabPhoto2();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>


<div class="share-video-section ">
    <div class="title-modal">
        <?php echo __( 'Upload Photos')?>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
    </div>
    <div class="modal-body">
        <div class="bar-content full_content p_m_10">
            <div class="content_center">
                <form id="uploadPhotoForm" action="<?php echo $this->request->base?>/photos/do_activity/<?php echo $type?>" method="post">
                    <div id="photos_upload"></div>
                    <div id="photo_review"></div>
                    
                    <a href="#" class="btn btn-action" id="triggerUpload"><?php echo __( 'Upload Queued Files')?></a>
                    <input type="hidden" name="new_photos" id="new_photos">
                    <input type="hidden" name="target_id" value="<?php echo $target_id?>">
                    <input type="hidden" name="type" value="<?php echo $type?>">
                    <input type="button" class="btn btn-action" id="nextStep" value="<?php echo __( 'Save Photos')?>" style="display:none">
                    <div id="loadingSpin" style="display: inline-block; padding: 0 10px;"></div>
                </form>
                <?php
                endif;
                ?>
            </div>
        </div>
    </div>