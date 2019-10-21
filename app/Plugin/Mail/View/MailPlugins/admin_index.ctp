<?php
echo $this->Html->css(array('jquery-ui', 'footable.core.min'), null, array('inline' => false));
echo $this->Html->script(array('jquery-ui', 'footable'), array('inline' => false));
echo $this->Html->script(array('tinymce/tinymce.min'), array('inline' => false));

$this->Html->addCrumb(__('System Admin'),'/admin/home');
$this->Html->addCrumb(__('Mails'), array('plugin' => 'mail', 'controller' => 'mail_plugins', 'action' => 'admin_index'));

$this->startIfEmpty('sidebar-menu');
echo $this->element('admin/adminnav', array("cmenu" => "mail"));
$this->end();

?>

<div class="portlet-body form">
    <div class=" portlet-tabs">
        <div class="tabbable tabbable-custom boxless tabbable-reversed">
            <?php echo $this->Moo->renderMenu('Mail', __('Manage Mail Template'));?>
            <form class="form-horizontal" method="post" enctype="multipart/form-data" action="">
			    <div class="form-body">
			    	<div class="form-group">
			            <label class="col-md-3 control-label">
			            	<?php echo __('Language Pack');?>			               
			            </label>
			            <div class="col-md-7">
			                <select class="form-control" name="language" id="language">
							    <?php foreach ($languages as $language_array):?>
							    	<?php $language_array = $language_array['Language']?>
							    	<option <?php if ($language == $language_array['key']) echo "selected='selected'";?> value="<?php echo $language_array['key']?>"><?php echo $language_array['name']?></option>
							    <?php endforeach;?>
							</select>
			            </div>
			         </div>		
			         <div class="form-group">
			            <label class="col-md-3 control-label">
			            	<?php echo __('Choose Email');?>
			            </label>
			            <div class="col-md-7">
			                <select class="form-control" name="template_id" id="template_id">
			                	<option value=""></option>
							    <?php foreach ($templetes as $tmp):?>
							    	<option value="<?php echo $tmp['Mailtemplate']['id']?>" <?php if ($templete && $templete['Mailtemplate']['id'] == $tmp['Mailtemplate']['id']) echo "selected='selected'" ?>>
							    		<?php 
							    			if (__d(Inflector::underscore($tmp['Mailtemplate']['plugin']),'_EMAIL_'.strtoupper($tmp['Mailtemplate']['type']).'_TITLE_') != '_EMAIL_'.strtoupper($tmp['Mailtemplate']['type']).'_TITLE_')
							    				echo __d(Inflector::underscore($tmp['Mailtemplate']['plugin']),'_EMAIL_'.strtoupper($tmp['Mailtemplate']['type']).'_TITLE_');
							    			else
							    				echo __d('mail','_EMAIL_'.strtoupper($tmp['Mailtemplate']['type']).'_TITLE_');
							    		?>
							    	</option>
							    <?php endforeach;?>
							</select>
							<?php if ($templete):?>
							<br/>
							<div>
								<div>									
									<?php 
										if (__d(Inflector::underscore($templete['Mailtemplate']['plugin']),'_EMAIL_'.strtoupper($templete['Mailtemplate']['type']).'_DESCRIPTION_') != '_EMAIL_'.strtoupper($templete['Mailtemplate']['type']).'_DESCRIPTION_')
											echo __d(Inflector::underscore($templete['Mailtemplate']['plugin']),'_EMAIL_'.strtoupper($templete['Mailtemplate']['type']).'_DESCRIPTION_');
										else
											echo __d('mail','_EMAIL_'.strtoupper($templete['Mailtemplate']['type']).'_DESCRIPTION_');
									?>
								</div>
								<div>
									<?php echo __('Available Placeholders');?>: <?php echo $templete['Mailtemplate']['vars'];?>
								</div>
								<br>
							</div>
							<?php endif;?>
			            </div>
			         </div>	
			         <div class="form-group">
			            <label class="col-md-3 control-label">
			            	<?php echo __('Subject');?>
			            </label>
			            <div class="col-md-7">
			                <input type="text" value="<?php if ($templete) echo $templete['Mailtemplate']['subject'];?>" name="subject" class="form-control" />
			            </div>
			         </div>	
			         <div class="form-group">
			            <label class="col-md-3 control-label">
			            	<?php echo __('Message Body');?>
			            </label>
			            <div class="col-md-7">
			                <textarea name="content" id="editor"><?php if ($templete) echo $templete['Mailtemplate']['content'];?></textarea>
			            </div>
			         </div>		            			           
			    </div>
			    <div class="form-actions">
			        <div class="row">
			            <div class="col-md-offset-3 col-md-9">
			                <input type="submit" class="btn btn-circle btn-action" value="<?php echo __('Save Changes');?>">
			            </div>
			        </div>
			    </div>
			</form>
        </div>
    </div>
</div>
<script>
<?php $this->Html->scriptStart(array('inline' => false)); ?>
$(document).ready(function() {
  	$('#language').change(function(e){
  		window.location.href = "<?php echo $this->request->base;?>/admin/mail/mail_plugins/index/" +$('#language').val();
  	});
    $('#template_id').change(function(e){
      	window.location.href = "<?php echo $this->request->base;?>/admin/mail/mail_plugins/index/" +$('#language').val() + "/" + $(this).val();
    });

    tinymce.init({
        selector: "textarea",
        language : mooConfig.tinyMCE_language,
        theme: "modern",
        skin: 'light',
        plugins: [
            "advlist autolink lists link image charmap print preview hr anchor pagebreak",
            "searchreplace wordcount visualblocks visualchars code",
            "insertdatetime nonbreaking save table contextmenu directionality",
            "emoticons template textcolor"
        ],
        toolbar1: "fontselect | fontsizeselect | styleselect | bold italic | bullist numlist outdent indent | forecolor backcolor emoticons | image link unlink anchor | preview code",
        image_advtab: true,
        theme_advanced_font_sizes: "10px,12px,13px,14px,16px,18px,20px",
        height: 300,
        relative_urls : false,
        remove_script_host : false,
        document_base_url : '<?php echo FULL_BASE_URL . $this->request->root?>',
        convert_urls: false,
    });
});
<?php $this->Html->scriptEnd(); ?>
</script>