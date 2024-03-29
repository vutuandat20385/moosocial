<?php
if(!isset($custom_fields)){
    $custom_fields = Cache::read('custom_fields');
    if(!$custom_fields){
        $custom_fields = $this->requestAction('/users/getCustomField');
        Cache::write('custom_fields',$custom_fields);
    }
}

?>
<?php if (!isset($show_profile_type) || $show_profile_type):?>
<?php if($this->request->is('ajax')): ?>
    <script type="text/javascript">
        require(["jquery","mooUser"], function($,mooUser) {
            mooUser.loadProfileType('<?php if (isset($user_edit_id)) echo 'profile'; elseif (isset($is_browser)) echo 'search'; else echo 'signup'?>');
        });
    </script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooUser'), 'object' => array('$', 'mooUser'))); ?>
   		mooUser.loadProfileType('<?php if (isset($user_edit_id)) echo 'profile'; elseif (isset($is_browser)) echo 'search'; else echo 'signup'?>');
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<!-- custom profle type -->
<div  class="form-group" <?php if(count($profile_type) <= 1 || (isset($is_edit) && !$is_edit && $cuser['ProfileType']['id'])):?> style="display: none;" <?php endif;?>>
    <div class="col-sm-3  control-label">
        <label><?php echo __('Profile type');?></label>
    </div>
    <div class="col-sm-9">
        <?php 
        	$params = array(
            	'class' => 'form-control',
        		'empty' => isset($is_search) && count($profile_type) > 1? true : false,
        	);
        	if (isset($profile_type_id))
        		$params['value'] = $profile_type_id;
        	
        	echo $this->Form->select('profile_type_id', $profile_type, $params); 
        	?>
    </div>
    <div class="clear"></div>
</div>
<!-- end -->
<?php endif;?>

<?php if (!isset($show_profile_type) || $show_profile_type):?><div class="custom-field"><?php endif;?>
<?php

if ( !empty( $custom_fields ) )
{
	$helper = MooCore::getInstance()->getHelper("Core_Moo");
	foreach ( $custom_fields as $field )
	{
		$val = ( isset( $values[$field['ProfileField']['id']]['value'] ) ) ? $values[$field['ProfileField']['id']]['value'] : '';
		if (!in_array($field['ProfileField']['type'],$helper->profile_fields_default))
		{
			$options = array();
			if ($field['ProfileField']['plugin'])
			{
				$options = array('plugin' => $field['ProfileField']['plugin']);
			}

			echo $this->element('profile_field/' . $field['ProfileField']['type'], array('field' => $field,'is_search'=>isset($is_search) ? $is_search : null,'show_require'=>isset($show_require) ? $show_require : null,'val'=>$val),$options);
			continue;
		}

		if ( $field['ProfileField']['type'] == 'heading' && !empty( $show_heading ) )
        {
            echo '<h2 class="page-header">' . $field['ProfileField']['name'] . '</h2>';
            continue;
        }
		
		echo '<div class="form-group"><div class="col-sm-3"><label>' .$field['ProfileField']['name'];
		
		if ( !empty( $field['ProfileField']['description'] ) )
			echo ' <a href="javascript:void(0)" class="tip" title="' . $field['ProfileField']['description'] . '">(?)</a>';
		echo '</label></div><div class="col-sm-9">';
		
		switch ( $field['ProfileField']['type'] )
		{                
			case 'textfield':
				echo $this->Form->text( 'field_' .$field['ProfileField']['id'], array( 'class' => 'form-control','value' => $val , 'name' => 'field_' .$field['ProfileField']['id']) );
				break;
			
			case 'textarea':
				echo $this->Form->textarea( 'field_' .$field['ProfileField']['id'], array( 'class' => 'form-control no-grow','value' => $val , 'name' => 'field_' .$field['ProfileField']['id']) );
				break;
				
			case 'list':
				$field_values = $helper->getProfileFieldOption($field['ProfileField']['id']);

				echo $this->Form->select( 'field_' .$field['ProfileField']['id'], $field_values, array( 'class' => 'form-control','value' => $val, 'name'=>'field_' .$field['ProfileField']['id'] ) );
				break;
				
			case 'multilist':
				$field_values = $helper->getProfileFieldOption($field['ProfileField']['id']);

				echo $this->Form->select( 'field_' .$field['ProfileField']['id'], $field_values, array( 'class' => 'multi form-control','value' => explode(', ', $val), 'multiple' => 'multiple',  'name' =>'field_' .$field['ProfileField']['id'] ) );
				break;
		}
		
		if ( !empty( $show_require ) && $field['ProfileField']['required'] )
			echo '<span class="profile-tip"> *</span>';
		
		echo '</div><div class="clear"></div></div>';
	}
}
?>
<?php if (!isset($show_profile_type) || $show_profile_type):?></div><?php endif;?>