<?php
echo $this->Html->css(array('codemirror','jquery.miniColors'), null, array('inline' => false));
echo $this->Html->script(array('scripts.js',
    'codemirror/codemirror',
    'codemirror/javascript',
    'codemirror/css',
    'codemirror/clike',
    'codemirror/xml',
    'codemirror/php',
    'codemirror/htmlmixed',
    'codemirror/htmlembedded',
    'jquery.miniColors.min'), array('inline' => false));

$this->Html->addCrumb(__('Site Manager'));
$this->Html->addCrumb(__('Themes Manager'), array('controller' => 'themes', 'action' => 'admin_index'));
$this->Html->addCrumb(__('Setting'), array('controller' => 'themes', 'action' => 'admin_setting',$theme['Theme']['id']));

$this->startIfEmpty('sidebar-menu');
echo $this->element('admin/adminnav', array("cmenu" => "themes"));
$this->end();
$theme_setting = '';
?>
<?php echo $this->element('admin/themenav', array("cmenu" => "setting", "theme_id" => $theme['Theme']['id'])); ?>
 <h3><?php echo $theme['Theme']['name']; ?></h3>
<div>   
    <div class="portlet-body">
        <div class=" portlet-tabs">
            <div class="tabbable tabbable-custom boxless tabbable-reversed">
                <div class="row" style="padding-top: 10px;">
                    <div class="col-md-12">
                        <div class="tab-content">
                            <div class="tab-pane active" id="portlet_tab1">
                                <?php
                                echo $this->Form->create('Theme', array(
                                    'id' => 'theme_setting_form',
                                    'class' => 'form-horizontal',
                                    'url' => 'save_custom_css/',
                                    'enctype' => 'multipart/form-data'
                                ));
                                ?>
                                <?php echo $this->Form->hidden('theme_id', array('id' => 'theme_id', 'value' => $theme['Theme']['id'])); ?>
                                <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Enable'); ?>  (<a data-html="true" href="javascript:void(0)" class="tooltips" data-original-title="<?php echo __("Uncheck if you don't want to apply the custom colors and background image setup here. Use default color and background of the theme.") ?>" data-placement="bottom">?</a>)</label>
                                        <div class="col-md-7" style="margin-top: 10px;">
                                            <?php 
                                                echo $this->Form->checkbox('custom_css_enable', array(
                                                    'value' => 1, 
                                                    'checked' => $theme['Theme']['custom_css_enable'],
                                                ));
                                            ?>	
                                        </div>
                                </div>
                                 <div class="form-group">
                                        <label class="col-md-3 control-label"></label>
                                        <div class="col-md-7" style="margin-top: 10px;">
                                            <a href="javascript:void(0)" id="reset_settings"><?php echo __('Reset Settings'); ?></a>
                                        </div>
                                </div>
                                <h4 style="text-transform: uppercase;padding-left: 10px"><?php echo __('Desktop'); ?></h4>
                                <div class="form-body">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Page Background'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[page_background]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['page_background']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Header Background'); ?></label>
                                        <div class="col-md-7">
                                                <input class="color-picker form-control" name="data[header_background]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['header_background']:''; ?>" class="form-control" type="text">	                                            
                                                <br> Or Use Image    
                                                <?php if(isset($custom_css_arr['header_background_image']) && !empty($custom_css_arr['header_background_image'])): ?>
                                                <div id="div_header_background_image">
                                                    <img src="<?php echo $this->request->webroot . $custom_css_arr['header_background_image'] ?>" height="100px"> <br>
                                                    <a href="javascript:void(0)" id="reset_header_image"><?php echo __('Reset'); ?></a>
                                                    <input type="text" name="data[header_background_image]" value="<?php echo $custom_css_arr['header_background_image'] ?>" style="display: none">
                                                </div>    
                                                 <?php endif; ?>    
                                                  <div class="clear"></div>
                                                <input type="file" name="Filedata">	
                                        </div>
                                    </div>                              
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Header Icons Color'); ?></label>
                                        <div class="col-md-7">
                                                <input class="color-picker form-control" name="data[header_icons_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['header_icons_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Search Bar Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[search_bar_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['search_bar_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                   <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Search Bar Icon & Text Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[search_bar_icon_text_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['search_bar_icon_text_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Navigation Background Color'); ?></label>
                                        <div class="col-md-7">
                                           <input class="color-picker form-control" name="data[navigation_background_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['navigation_background_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Navigation Text Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[navigation_text_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['navigation_text_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Navigation Active Background Color'); ?></label>
                                        <div class="col-md-7">
                                           <input class="color-picker form-control" name="data[navigation_active_background_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['navigation_active_background_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Navigation Active Text Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[navigation_active_text_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['navigation_active_text_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Dropdown Background Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[dropdown_background_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['dropdown_background_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Dropdown Hover Background Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[dropdown_hover_background_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['dropdown_hover_background_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Dropdown Text Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[dropdown_text_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['dropdown_text_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Block Header Background'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[block_header_background]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['block_header_background']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Footer Text Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[footer_text_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['footer_text_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Footer Background'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[footer_background]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['footer_background']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Footer Text Link Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[footer_text_link_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['footer_text_link_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Apply to Landing Page'); ?></label>
                                        <div class="col-md-7" style="margin-top: 10px;">
                                            <?php 
                                                echo $this->Form->checkbox('apply_to_landing_page', array(
                                                    'value' => 1, 
                                                    'checked' => isset($custom_css_arr)?$custom_css_arr['apply_to_landing_page']:false
                                                ));
                                            ?>	
                                        </div>
                                    </div>
                                    <h4 style="text-transform: uppercase;padding-left: 10px"><?php echo __('Mobile'); ?></h4>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Bottom Bar Background'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[bottom_bar_background]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['bottom_bar_background']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label"><?php echo __('Bottom Bar Icons Color'); ?></label>
                                        <div class="col-md-7">
                                            <input class="color-picker form-control" name="data[bottom_bar_icons_color]" value="<?php echo isset($custom_css_arr)?$custom_css_arr['bottom_bar_icons_color']:''; ?>" class="form-control" type="text">	                                            
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-9">
                                            <button id="createButton" class="btn btn-circle btn-action"><i class="icon-save"></i> <?php echo __('Save'); ?></button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-6">
                                            <div class="alert alert-danger error-message" style="display:none;margin-top:10px"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php echo $this->Form->end(); ?>
                                <!-- END FORM-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if($this->request->is('ajax')): ?>
<script>
<?php else: ?>
<?php  $this->Html->scriptStart(array('inline' => false));   ?>
<?php endif; ?>

jQuery('.color-picker').each(function(){
   var int_val = $(this).val();
   $(this).miniColors({});
   if(int_val == '' || int_val == '#'){
    $(this).miniColors('value','');
   }
});

$('#reset_header_image').click(function(){
        disableButton('reset_header_image');
        $.post("<?php echo $this->request->base?>/admin/themes/ajax_reset_header_image", {theme_id: $('#theme_id').val()}, function(data){
            enableButton('reset_header_image');
            var json = $.parseJSON(data);
           
            if ( json.result == 1 )
                $('#div_header_background_image').remove();
            else
            {
                $(".error-message").show();
                $(".error-message").html('<strong>Error!</strong>'+json.message);
            }
	});
	return false;
});

$('#reset_settings').click(function(){
    $('#theme_setting_form').find("input[type=text]").val("");
    $('#theme_setting_form').submit();
});
<?php if($this->request->is('ajax')): ?>
</script>
<?php else: ?>
<?php $this->Html->scriptEnd();  ?>
<?php endif; ?>