<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooVideo"], function($, mooVideo) {
        mooVideo.initAfterFetch();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooVideo'), 'object' => array('$', 'mooVideo'))); ?>
mooVideo.initAfterFetch();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php
$tags_value = '';
if (!empty($tags)) $tags_value = implode(', ', $tags);
?>
<?php if ( !empty( $video['Video']['id'] ) ): ?>

<?php if($this->request->is('ajax')): ?>
<div class="title-modal">
    <?php echo __( 'Edit Video')?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<?php endif; ?>

<div class="modal-body">

    <div class="bar-content full_content p_m_10">
        <div class="content_center">
            <div class="create_form">
                <form id="createForm">
                    <?php endif; ?>
                    <div class="create_form">
                    <ul class="list6 list6sm2">
                        <?php echo $this->Form->hidden('id', array('value' => $video['Video']['id'])); ?>
                        <?php echo $this->Form->hidden('source_id', array('value' => $video['Video']['source_id'])); ?>
                        <?php echo $this->Form->hidden('thumb', array('value' => $video['Video']['thumb'])); ?>

                        <li>
                                <div class="col-md-2">
                                    <label><?php echo __( 'Video Title')?></label>
                                </div>
                                <div class="col-md-10">
                                    <?php echo $this->Form->text('title', array('value' => html_entity_decode($video['Video']['title']))); ?>
                                </div>
                                <div class="clear"></div>
                        </li>

                        <?php if(empty($isGroup)): ?>
                        <li>
                                <div class="col-md-2">
                                    <label><?php echo __( 'Category')?></label>
                                </div>
                                <div class="col-md-10">
                                    <?php echo $this->Form->select( 'category_id', $categories, array( 'value' => $video['Video']['category_id'] ) ); ?>
                                </div>
                                <div class="clear"></div>


                        </li>
                        <?php endif; ?>

                        <li>
                                <div class="col-md-2">
                                    <label><?php echo __( 'Description')?></label>
                                </div>
                                <div class="col-md-10">
                                    <?php echo $this->Form->textarea('description', array('value' => $video['Video']['description'])); ?>
                                </div>
                                <div class="clear"></div>


                        </li>

                        <?php if(empty($isGroup)): ?>
                        <li>
                                <div class="col-md-2">
                                    <label><?php echo __( 'Tags')?></label>
                                </div>
                                <div class="col-md-10">
                                    <?php echo $this->Form->text('tags', array('value' => $tags_value)); ?> <a href="javascript:void(0)" class="tip profile-tip" title="<?php echo __( 'Separated by commas or space')?>">(?)</a>
                                </div>
                                <div class="clear"></div>
                        </li>
                        <li>
                                <div class="col-md-2">
                                    <label><?php echo __( 'Privacy')?></label>
                                </div>
                                <div class="col-md-10">



                            <?php
                            echo $this->Form->select( 'privacy',
                                                      array( PRIVACY_EVERYONE => __( 'Everyone'),
                                                             PRIVACY_FRIENDS  => __( 'Friends Only'),
                                                             PRIVACY_ME 	  => __( 'Only Me')
                                                            ),
                                                      array( 'value' => $video['Video']['privacy'],
                                                             'empty' => false
                                                            )
                                                    );
                            ?>
                                    </div>
                                <div class="clear"></div>
                        </li>
                        <?php endif; ?>

                        <li>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                </div>
                                <div class="col-md-10">


                                <button type='button' class='btn btn-action' id="saveBtn"><?php echo __( 'Save Video')?></button>
                            
                            <?php if ( !empty( $video['Video']['id'] ) ): ?>
                            <a href="javascript:void(0)" data-id="<?php echo $video['Video']['id'] ?>" class="btn btn-default deleteVideo"><?php echo __( 'Delete Video')?></a>
                            <?php endif; ?>
                             </div>
                                <div class="clear"></div>
                        </li>
                    </ul>
                    </div>
                    <?php if ( !empty( $video['Video']['id'] ) ): ?>
                    </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="error-message" style="display:none;margin-top:10px;"></div>