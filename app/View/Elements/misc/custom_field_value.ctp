<?php if ( !$field['ProfileField']['searchable'] ): ?>
	<?php 
		if ($field['ProfileField']['type'] == 'multilist' )
		{
			$values = explode(', ', $field['ProfileFieldValue']['value']);
			foreach ( $values as $key => $val ) {
				$option = $this->Moo->getNameFieldOption($field['ProfileFieldValue']['profile_field_id'], $val);
				echo h($option)?><?php if ( $key != (count($values) - 1) ) echo ', ';	
			}
		}
		elseif ($field['ProfileField']['type'] == 'list')
		{
			$option = $this->Moo->getNameFieldOption($field['ProfileFieldValue']['profile_field_id'], $field['ProfileFieldValue']['value']);	
			echo h($option);
		}
		else
		{
			echo h($field['ProfileFieldValue']['value']);
		}
	?>
<?php else: ?>
	<?php if ($field['ProfileField']['type'] == 'multilist' ):
		$values = explode(', ', $field['ProfileFieldValue']['value']);
		$options = $this->Moo->getOrderOptions($field['ProfileFieldValue']['profile_field_id'], $values);
		$key = 0;
		foreach ( $options as $id => $val ):
	?>
		<a href="<?php echo $this->request->base?>/users/index/profile_type:<?php echo $field['ProfileField']['profile_type_id']?>/field_<?php echo $field['ProfileField']['id']?>:<?php echo urlencode(trim($id))?>"><?php echo h($val)?></a><?php if ( $key != (count($values) - 1) ) echo ', ';
		$key++;
		endforeach;
	elseif ($field['ProfileField']['type'] == 'list'):
		$option = $this->Moo->getNameFieldOption($field['ProfileFieldValue']['profile_field_id'], $field['ProfileFieldValue']['value']);	
	?>
		<a href="<?php echo $this->request->base?>/users/index/profile_type:<?php echo $field['ProfileField']['profile_type_id']?>/field_<?php echo $field['ProfileField']['id']?>:<?php echo urlencode(trim($field['ProfileFieldValue']['value']))?>"><?php echo h($option)?></a>
	<?php else: ?>
		<a href="<?php echo $this->request->base?>/users/index/profile_type:<?php echo $field['ProfileField']['profile_type_id']?>/field_<?php echo $field['ProfileField']['id']?>:<?php echo urlencode(trim($field['ProfileFieldValue']['value']))?>"><?php echo h(trim($field['ProfileFieldValue']['value']))?></a>
	<?php endif; ?>
<?php endif; ?>