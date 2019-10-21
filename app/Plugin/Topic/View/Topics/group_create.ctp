<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooGroup","mooTopic"], function($, mooGroup, mooTopic) {
        mooTopic.initOnCreate();
        mooGroup.initOnCreateGroupTopic();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooTopic', 'mooGroup'), 'object' => array('$', 'mooTopic', 'mooGroup'))); ?>
mooTopic.initOnCreate();
mooGroup.initOnCreateGroupTopic();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>

<?php
$topicHelper = MooCore::getInstance()->getHelper('Topic_Topic');
?>

<style>
.list6 .mce-tinymce { margin-left: 0; }
.attach_remove {display:none;}
#attachments_list li:hover .attach_remove {display:inline-block;}
</style>
<div class="create_form_ajax">
<div class="bar-content full_content p_m_10">
    <div class="content_center">
<form id="createForm">
<?php
$topicHelper = MooCore::getInstance()->getHelper('Topic_Topic');
echo $this->Form->hidden( 'attachments', array( 'value' => $attachments_list ) );
echo $this->Form->hidden('thumbnail', array('value' => $topic['Topic']['thumbnail']));
echo $this->Form->hidden( 'tags' );

if (!empty($topic['Topic']['id']))
	echo $this->Form->hidden('id', array('value' => $topic['Topic']['id']));

echo $this->Form->hidden('topic_photo_ids');
echo $this->Form->hidden('group_id', array('value' => $group_id));
echo $this->Form->hidden('category_id', array('value' => 0));
?>	<div class="groupId" data-id="<?php echo $group_id; ?>"></div>
    <div class="form_content">
        <ul>
                <li>
                    <div class="col-md-2">
                        <label><?php echo __( 'Topic Title')?></label>
                    </div>
                    <div class="col-md-10">
                        <?php echo $this->Form->text( 'title', array( 'value' => $topic['Topic']['title'] ) ); ?>
                    </div>
                    <div class="clear"></div>
                </li>
                <li>
                    <div class="col-md-2">
                        <label> <?php echo __( 'Topic')?></label>
                    </div>
                    <div class="col-md-10">
                        <?php echo $this->Form->tinyMCE( 'body', array( 'value' => $topic['Topic']['body'], 'id' => 'editor' ) ); ?>
                        <div class="toggle_image_wrap">
                                    <div id="images-uploader" style="display:none;margin:10px 0;">
                                        <div id="attachments_upload"></div>
                                        <a href="javascript:void(0)" class="button button-primary" id="triggerUpload"><?php echo __( 'Upload Queued Files')?></a>
                                    </div>
                                    <?php //if(empty($isMobile)): ?>
                                        <a id="toggleUploader" href="javascript:void(0)"><?php echo __( 'Upload Photos or Attachments into editor')?></a>
                                    <?php //endif; ?>
                                </div>
                    </div>
                    <div class="clear"></div>
                </li>
                <li>
                    <div class="col-md-2">
                        <label><?php echo __( 'Thumbnail')?>(<a original-title="Thumbnail only display on topic listing and share topic to facebook" class="tip" href="javascript:void(0);">?</a>)</label>
                    </div>
                    <div class="col-md-10">
                        <div id="topic_thumnail"></div>
                        <div id="topic_thumnail_preview">
                            <?php if (!empty($topic['Topic']['thumbnail'])): ?>
                            <img width="150" src="<?php echo $topicHelper->getImage($topic, array('prefix' => '150_square'))?>" />
                            <?php else: ?>
                                <img width="150" src="" style="display: none;" />
                            <?php endif; ?>
                        </div>

                    </div>
                    <div class="clear"></div>
                </li>
                <?php if (!empty($attachments)): ?>
                <li>
                    <div class="col-md-2">
                        <label><?php echo __( 'Attachments')?></label>
                    </div>
                    <div class="col-md-10">
                        <ul class="list6 list6sm" id="attachments_list" style="overflow: hidden;">
                            <?php foreach ($attachments as $attachment): ?>
                            <li><i class="material-icons">attach_file</i><a href="<?php echo $this->request->base?>/attachments/download/<?php echo $attachment['Attachment']['id']?>" target="_blank"><?php echo $attachment['Attachment']['original_filename']?></a>
                                &nbsp;<a href="#" data-id="<?php echo $attachment['Attachment']['id']?>" class="attach_remove tip" title="<?php echo __( 'Delete')?>"><i class="material-icons icon-small">delete</i></a>              
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="clear"></div>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</form>
        <div class="col-md-2">&nbsp;</div>
        <div class="col-md-10">
            
             <div style="margin:20px 0">           
                <a href="javascript:void(0)" class="btn btn-action" id="ajaxCreateButton"><?php echo __( 'Save')?></a>

                <?php if ( !empty( $topic['Topic']['id'] ) ): ?>
                <a href="javascript:void(0)" class="button cancelTopic1" data-url="<?php echo $this->request->base?>/topics/ajax_view/<?php echo $topic['Topic']['id']?>"><?php echo __( 'Cancel')?></a>

                <?php if ( ($topic['Topic']['user_id'] == $uid ) || ( !empty($my_status) && $my_status['GroupUser']['status'] == GROUP_USER_ADMIN ) || ( !empty($cuser) && $cuser['Role']['is_admin'] ) ): ?>
                <a href="javascript:void(0)" data-id="<?php echo $topic['Topic']['id']; ?>" data-group="<?php echo $topic['Topic']['group_id']; ?>" class="deleteTopic button button-caution"><?php echo __( 'Delete')?></a>
                <?php endif; ?> 

                <?php else: ?>
                <a href="javascript:void(0)" data-url="<?php echo $this->request->base?>/topics/browse/group/<?php echo $this->request->data['group_id']?>" class="button cancelTopic"><?php echo __( 'Cancel')?></a>
                <?php endif; ?>     
            </div>
            <div class="error-message" id="errorMessage" style="display:none"></div>
         </div>
        <div class="clear"></div>
</div>
</div>

</div>