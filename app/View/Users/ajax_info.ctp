<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>
<div class="bar-content full_content p_m_10">
    <div class="content_center">
        <div class="post_body">
            <h2 class="header_h2" style="margin-top: 0px;"><?php echo __('Basic Information')?></h2>
            <ul class="list6 info info2">
                <?php if ( !empty( $user['User']['username'] ) ): ?>
                <li><label><?php echo __('Profile URL')?>:</label> <?php echo $this->Text->autoLink(FULL_BASE_URL . $this->request->base . '/-' . $user['User']['username'])?></li>
                <?php endif; ?>	
                <li><label><?php echo __('Gender')?>:</label> <?php $this->Moo->getGenderTxt($user['User']['gender']); ?></li>
                <?php if ( !empty($user['User']['birthday']) && $user['User']['birthday'] != '0000-00-00' ): ?>
                <li><label><?php echo __('Birthday')?>:</label> <?php echo $this->Time->event_format($user['User']['birthday'], '%B %d', false, $utz)?></li>
                <?php endif; ?>
                <li><label><?php echo __('Registered Date')?></label><?php echo $this->Moo->getTime($user['User']['created'], Configure::read('core.date_format'), $utz)?></li>
                <li><label><?php echo __('Last Online')?></label><?php echo $this->Moo->getTime($user['User']['last_login'], Configure::read('core.date_format'), $utz)?></li>
                <?php if ( !empty( $user['User']['about'] ) ): ?>
                <li><label><?php echo __('About')?>:</label>
                	<div>
                		<?php if (Configure::read('core.show_about_search')):?>
                			<a href="<?php echo $this->request->base?>/users/index/about:<?php echo $this->Moo->formatText( $user['User']['about'] , false, true, array('no_replace_ssl' => 1))?>">
                			<?php echo $this->Moo->formatText( $user['User']['about'] , false, true, array('no_replace_ssl' => 1))?>
                			</a>
                		<?php else:?>
                			<?php echo $this->Moo->formatText( $user['User']['about'] , false, true, array('no_replace_ssl' => 1))?>
                		<?php endif;?>
                	</div>
                </li>
                <?php endif; ?>	                
                <?php if ($user['ProfileType']['id']):?>
                	<?php if (Configure::read('core.enable_show_profile_type')):?>
	                	<li>
	                		<label><?php echo __('Profile type');?>: </label>
	                		<div><a href="<?php echo $this->request->base;?>/users/index/profile_type:<?php echo $user['ProfileType']['id'];?>"><?php echo $user['ProfileType']['name'];?></a></div>
	                	</li>
                	<?php endif;?>
	                <?php
	                $helper = MooCore::getInstance()->getHelper("Core_Moo");
	                foreach ($fields as $field):
	                    if (!in_array($field['ProfileField']['type'],$helper->profile_fields_default))
	                    {
	                        $options = array();
	                        if ($field['ProfileField']['plugin'])
	                        {
	                            $options = array('plugin' => $field['ProfileField']['plugin']);
	                        }
	
	                        echo $this->element('profile_field/' . $field['ProfileField']['type'].'_info', array('field' => $field,'user'=>$user),$options);
	                        continue;
	                    }
	                    if ( $field['ProfileField']['type'] == 'heading' ):
	                ?>
	                <li class="fields_heading"><h2><?php echo $field['ProfileField']['name']?></h2></li>
	                <?php
	                    elseif ( !empty( $field['ProfileFieldValue']['value'] ) ) :
	                ?>
	                <li><label><?php echo $field['ProfileField']['name']?>:</label>
	                    <div><?php echo $this->element( 'misc/custom_field_value', array( 'field' => $field ) ); ?></div>
	                </li>
	                <?php
	                    endif;
	                endforeach;
	                ?>
                <?php endif;?>
            </ul>
        </div>
    </div>
</div>