
    
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooTopic'), 'object' => array('$', 'mooTopic'))); ?>
mooTopic.initOnCreate();
<?php $this->Html->scriptEnd(); ?>

<?php $this->setCurrentStyle(4) ?>
<?php
$topicHelper = MooCore::getInstance()->getHelper('Topic_Topic');
$tags_value = '';
if (!empty($tags)){
    $tags_value = implode(', ', $tags);
}
?>

<style>
.attach_remove {display:none;}
#attachments_list li:hover .attach_remove {display:inline-block;}
</style>




<div class="create_form">
<div class="bar-content">
<div class="content_center">
<div class="box3">
    <form id="createForm">
	<?php
	echo $this->Form->hidden( 'attachments', array( 'value' => $attachments_list ) );
        echo $this->Form->hidden('thumbnail', array('value' => $topic['Topic']['thumbnail']));
        echo $this->Form->hidden('plugin_topic_id', array('value' => PLUGIN_TOPIC_ID));
        echo $this->Form->hidden('topic_photo_ids');
	if (!empty($topic['Topic']['id']))
		echo $this->Form->hidden('id', array('value' => $topic['Topic']['id']));
	?>
        <div class="mo_breadcrumb">
            <h1><?php if (empty($topic['Topic']['id'])) echo __( 'Create New Topic'); else echo __( 'Edit Topic');?></h1>	
        </div>
        <div class="full_content p_m_10">
                <div class="form_content">
                    <ul>
                        <li>
                            <div class="col-md-2">
                                <label><?php echo __( 'Topic Title')?></label>
                            </div>
                            <div class="col-md-10">
                                <?php echo $this->Form->text( 'title', array( 'value' => html_entity_decode($topic['Topic']['title']) ) ); ?>
                            </div>
                            <div class="clear"></div>
                        </li>
                   
                        <li>
                            <div class="col-md-2">
                            <label><?php echo __( 'Category')?></label>
                            </div>
                            <div class="col-md-10">
                            <?php echo $this->Form->select( 'category_id', $cats, array( 'value' => $topic['Topic']['category_id'] ) ); ?>
                            </div>
                            <div class="clear"></div>
                        </li>
                        <li>
                            <div class="col-md-2">
                            <label><?php echo __( 'Topic')?></label>
                            </div>
                            <div class="col-md-10 tiny_desc">
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
                                <label><?php echo __( 'Thumbnail')?>(<a original-title="<?php echo __( 'Thumbnail only display on topic listing and share topic to facebook')?>" class="tip" href="javascript:void(0);">?</a>)</label>
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
                        <li>
                            <div class="col-md-2">
                            <label><?php echo __( 'Tags')?></label>
                            </div>
                            <div class="col-md-10">
                                <?php echo $this->Form->text( 'tags', array( 'value' => $tags_value ) ); ?> <a href="javascript:void(0)" class="tip profile-tip" title="<?php echo __( 'Separated by commas or space')?>">(?)</a>
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
                    <div class="col-md-2">&nbsp;</div>
                    <div class="col-md-10">
                        <div style="margin:20px 0">           
                            <button type='button' class='btn btn-action' id="saveBtn"><?php echo __( 'Save')?></button>
                            
                            <?php if ( !empty( $topic['Topic']['id'] ) ): ?>
                            <a href="<?php echo $this->request->base?>/topics/view/<?php echo $topic['Topic']['id']?>" class="button"><?php echo __( 'Cancel')?></a>
                            <?php endif; ?>
                            <?php if ( ($topic['Topic']['user_id'] == $uid ) || ( !empty( $topic['Topic']['id'] ) && $cuser['Role']['is_admin'] ) ): ?>
                            <a href="javascript:void(0)" data-id="<?php echo $topic['Topic']['id']?>" class="button deleteTopic"><?php echo __( 'Delete')?></a>
                            <?php endif; ?> 
                        </div>
                        <div class="error-message" id="errorMessage" style="display:none"></div>
                    </div>
                <div class="clear"></div>
            </div>
        </div>
            
    </form>
    
</div>
    
</div>
</div>
</div>