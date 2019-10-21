<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGroup"], function($, mooGroup) {
        mooGroup.initTabVideo();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery', 'mooGroup'), 'object' => array('$', 'mooGroup'))); ?>
mooGroup.initTabVideo();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php if ( !empty( $video['Video']['id'] ) ): ?>
<div class='bar-content'>
<?php endif; ?>
    <div class='content_center'>
        <div class='mo_breadcrumb'>
            <h1><?php echo __( 'Video Details')?></h1>
        </div>
        <div class="error-message" style="display:none"></div>
        <div class='create_form full_content p_m_10'>
        <?php if ( !empty( $video['Video']['id'] ) ): ?>
        <form id="createForm">
        <?php endif; ?>

        <ul class="list6 list6sm2">
                <?php if (!empty($video['Video']['id'])): ?>
                <?php echo $this->Form->hidden('id', array('value' => $video['Video']['id'])); ?>
                <?php endif; ?>
                <?php echo $this->Form->hidden('source_id', array('value' => $video['Video']['source_id'])); ?>
                <?php echo $this->Form->hidden('thumb', array('value' => $video['Video']['thumb'])); ?>
                <?php echo $this->Form->hidden('privacy', array('value' => PRIVACY_EVERYONE)); ?>

                <li>
                    <div class='col-md-2'>
                    <label><?php echo __( 'Video Title')?></label>
                    </div>
                    <div class='col-md-10'>
                        <?php echo $this->Form->text('title', array('value' => $video['Video']['title'])); ?>
                    </div>
                    <div class='clear'></div>  
                </li>
                <li>
                    <div class='col-md-2'>
                        <label><?php echo __( 'Description')?></label>
                    </div>
                    <div class='col-md-10'>
                        <?php echo $this->Form->textarea('description', array('value' => $video['Video']['description'])); ?>
                    </div>
                    <div class='clear'></div>
                </li>
                <li>
                    <div class='col-md-2'>
                    <label>&nbsp;</label>
                    </div>
                    <div class='col-md-10'>
                        <a href="javascript:void(0)" class="btn btn-action saveVideo"><?php echo __( 'Save')?></a>
                        <?php if ( !empty($video['Video']['id']) ): ?>
                        <a href="javascript:void(0)" class="button cancelVideo" data-group-id="<?php echo $video['Video']['group_id']?>" data-id="<?php echo $video['Video']['id']?>"> <?php echo __( 'Cancel')?></a>
                        <a href="javascript:void(0)" class="button" class="deleteVideo" data-group-id="<?php echo $video['Video']['group_id']?>" data-id="<?php echo $video['Video']['id']?>"> <?php echo __( 'Delete')?></a>
                        <?php else: ?>
                        <a href="javascript:void(0)" class="button" onclick="$('.modal').modal('hide');"> <?php echo __( 'Cancel')?></a>
                        <?php endif; ?>
                    </div>
                    <div class='clear'></div>  
                </li>
        </ul>

        <?php if ( !empty( $video['Video']['id'] ) ): ?>
        </form>
        <?php endif; ?>
        </div>
    </div>
<?php if ( !empty( $video['Video']['id'] ) ): ?>
</div>
<?php endif; ?>