<?php
    $countryModel = MooCore::getInstance()->getModel("Country");
    $countries = $countryModel->find('all');
    $options = array();
    foreach ($countries as $country)
    {
        $options[$country['Country']['id']] = $country['Country']['name'];
    }

    $user_country = null;
    $userCountryModel = MooCore::getInstance()->getModel("UserCountry");
    $country_id = 0;
    $state_id = 0;
    $option_state = array();
    $address = '';
    $zip = '';
    $is_search = isset($is_search) ? $is_search : null;
    if ($is_search)
    {
        if (isset($this->request->params['named']['country_id']))
            $country_id = $this->request->params['named']['country_id'];

        if (isset($this->request->params['named']['state_id']))
            $state_id = $this->request->params['named']['state_id'];
    }

    if (isset($user_edit_id) && $user_edit_id)
    {
        $user_country = $userCountryModel->find('first',array(
            'conditions'=>array('user_id' => $user_edit_id)
        ));
        if ($user_country) {
            $country_id = $user_country['UserCountry']['country_id'];
            $state_id = $user_country['UserCountry']['state_id'];
            $address = $user_country['UserCountry']['address'];
            $zip = $user_country['UserCountry']['zip'];
        }
    }

    if ($country_id)
    {
        $stateModel = MooCore::getInstance()->getModel("State");
        $states = $stateModel->find('all',array(
            'conditions'=>array('country_id'=>$country_id)            
        ));

        foreach ($states as $state)
        {
            $option_state[$state['State']['id']] = $state['State']['name'];
        }
    }
?>
<?php if($this->request->is('ajax')): ?>
    <script type="text/javascript">
        require(["jquery","mooUser"], function($,mooUser) {
            mooUser.initOnSignupStep1FieldCountry();
        });
    </script>
<?php else: ?>
    <?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooUser'), 'object' => array('$', 'mooUser'))); ?>
    mooUser.initOnSignupStep1FieldCountry();
    <?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<div class="form-group">
    <label class="col-sm-3 control-label">
        <?php echo __('Country'); ?>
    </label>
    <div class="col-sm-9">
        <?php echo $this->Form->select( 'country_id', $options, array( 'value'=>$country_id,'class' => 'form-control') ); ?>
        <?php if ( !empty( $show_require ) && $field['ProfileField']['required'] )
            echo '<span class="profile-tip"> *</span>';
        ?>
    </div>
    <div class="clear"></div>
</div>

<div class="form-group country_state" style="<?php if (!$country_id || !count($option_state)) echo 'display:none;' ?>">
    <label class="col-sm-3 control-label">
        <?php echo __('State/Province'); ?>
    </label>
    <div class="col-sm-9">
        <?php echo $this->Form->select( 'state_id', $option_state, array( 'value'=>$state_id,'class' => 'form-control') ); ?>
    </div>
    <div class="clear"></div>
</div>

<div class="form-group">
	<label class="col-sm-3 control-label">
    	<?php echo __('City/Address'); ?>
	</label>
    <div class="col-sm-9">
		<?php echo $this->Form->text( 'address', array( 'value'=>$address,'class' => 'form-control') ); ?>
	</div>
    <div class="clear"></div>
</div>
<div class="form-group">
    <label class="col-sm-3 control-label">
        <?php echo __('Zip/Postal Code'); ?>
    </label>
    <div class="col-sm-9">
		<?php echo $this->Form->text( 'zip', array( 'value'=>$zip,'class' => 'form-control') ); ?>
	</div>
	<div class="clear"></div>
</div>