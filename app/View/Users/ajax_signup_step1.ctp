<?php $this->setCurrentStyle(4); ?>

<?php
$allow_upload_avatar_signup = Configure::read('core.allow_upload_avatar_signup');
if ( !empty( $allow_upload_avatar_signup ) ): ?>

<script type="text/javascript">
    require(["jquery","mooUser"], function($,mooUser) {
        mooUser.initOnSignupStep1();
    });
</script>

    <div class="form-group required">
        <label class="col-md-3 control-label" for="avatar">
            <?php echo __('Profile Picture')?>
        </label>
        <div class="col-md-9 form-inline">
            <div id="profile_picture"></div>
            <div id="profile_picture_preview">
                <img style="display: none;" src="" />
                <input type="hidden" id="avatar" name="avatar" />
            </div>
        </div>
    </div>

<?php endif; ?>

<?php $show_birthday_signup = Configure::read('core.show_birthday_signup');
    if ( !empty($show_birthday_signup) ): ?>
    <div class="form-group required">
        <label class="control-label col-md-3" for="birthday">
            <?php echo __('Birthday')?><a href="javascript:void(0)" class="tip" title="<?php echo __('Only month and date will be shown on your profile')?>">(?)</a>
        </label>
        <div class="col-md-9 form-inline">
            <div class="col-xs-4">
                <?php echo $this->Form->month('birthday',array('class'=>'form-control'))?>
            </div>
            <div class="col-xs-4">
                <div class="p_l_2">
                    <?php echo $this->Form->day('birthday',array('class'=>'form-control'))?>
                </div>

            </div>
            <div class="col-xs-4">
                <?php echo $this->Form->year('birthday', 1930, date('Y'),array('class'=>'form-control'))?>
            </div>
            <div class="clear"></div>
        </div>
    </div>

<?php endif; ?>
<?php $enable_timezone_selection = Configure::read('core.enable_timezone_selection');
    if ( !empty($enable_timezone_selection) ): ?>
    <div class="form-group required">
        <label class="control-label col-md-3" for="timezone">
            <?php echo __('Timezone')?>
        </label>
        <div class="col-md-9 ">

            <?php echo $this->Form->select('timezone', $this->Moo->getTimeZones(), array('value' => Configure::read('core.timezone')), array('class'=>'form-control')); ?>
        </div>
    </div>
<?php endif; ?>
<?php $show_gender_signup = Configure::read('core.show_gender_signup');
if ( !empty($show_gender_signup) ): ?>
<div class="form-group required">
    <label class="col-md-3 control-label" for="gender">
        <?php echo __('Gender')?>
    </label>
    <div class="col-md-9 ">
        <?php echo $this->Form->select('gender', $this->Moo->getGenderList(), array('value' => 'Male'),array('class'=>'form-control')); ?>
    </div>
</div>
<?php endif; ?>
<?php $show_about_signup= Configure::read('core.show_about_signup');
if ( !empty($show_about_signup) ): ?>
<div class="form-group required">
	<label class="col-md-3 control-label" for="gender">
		<?php echo __('About')?>
	</label>
	<div class="col-md-9 ">
		<?php echo $this->Form->textarea('about',array('class'=>'form-control')); ?>
	</div>
</div>
<?php endif;?>
<?php if(isset($cbPackage) && $cbPackage != null):?>
<div class="form-group required">
    <label class="col-md-3 control-label" for="Package">
        <?php echo __('Package')?>
    </label>
    <div class="col-md-9 ">
        <?php echo $this->Form->input('package_id', array(
                'options' => $cbPackage,
                'label' => false,
            ));
        ?>
        <a href="<?php echo $this->request->base;?>/subscription/subscribes/compare" title="Compare Subscription" data-target="#themeModal" data-toggle="modal">Click here to learn more about our membership</a>
    </div>
</div>
<?php endif;?>
<?php
echo $this->element( 'custom_fields', array( 'show_heading' => true ) );
?>
<?php if ($isGatewayEnabled && $packages): ?>
    <?php 	$helper = MooCore::getInstance()->getHelper('Subscription_Subscription');    ?>
<h3 class="page-header"><?php echo __('Membership')?></h3>
<div class="form-group required">
	<div id="content_package" style="display:none;">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="title-modal">
				    <?php echo __('Subscription Plans')?>
				    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">x</span></button>
				</div>
				<?php 
					$element = (Configure::read('Subscription.select_theme_subscription_packages') ? 'Subscription.theme_compare' : 'Subscription.theme_default');
				?>
				<?php echo $this->element($element,array('compares'=>$compare,'columns'=>$packages,'type'=>'register'));?>
			</div>
		</div>
	</div>
	<script>
		$('#plan-view').html($('#content_package').html());
		$('#content_package').remove();

		var first = false;
		$('.button_select').click(function(){
			$('#plan-view').modal('hide');
			package_id = $(this).attr('ref');		
			$('#select-plan').val($('.package_register_'+package_id).val());
		});
		$('#plan-view').on('shown.bs.modal',function (e) {
			if (first)
				return;
			first = true;
			$('.compare-register .content').attr('style','');
			max = 0;
			$('.compare-register .content').each(function(e){
				if ($(this).height() > max)
				 	max = $(this).height();
			});
			$('.compare-register .content').each(function(e){
				if ($(this).css('padding-top'))
				{
					$(this).css('height',parseInt($(this).css('padding-top').replace("px", "")) + parseInt($(this).css('padding-bottom').replace("px", "")) + max + 10);
				}
			});
		});
		
	</script>
    <label for="timezone" class=" col-md-3 control-label"><?php echo __('Membership')?></label>
    <div class="col-md-9 ">
        <select id="select-plan" name="plan_id">
            <?php
            foreach ($packages as $package):
                $plans = $package['SubscriptionPackagePlan'];
                if (!count($plans))
                	continue;
                $package = $package['SubscriptionPackage'];
                $plan = array();
                if(!empty($plans)){
                    $plan = $plans[0];
                }
                ?>
            <optgroup label="<?php echo $package['name'] ?>">
                <?php foreach($plans as $index => $plan): ?>
                	<?php $plan = $plan['SubscriptionPackagePlan']?>
                    <option <?php if($index == 0 && $package['default'] == 1) echo 'selected'; ?>  value="<?php echo $plan['id']?>"><?php echo $package['name']. ' - '. $helper->getPlanDescription($plan,$currency['Currency']['currency_code'])?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php
            endforeach;
            ?>
            
        </select>        
    </div>
    <label for="timezone" class="col-md-3  control-label"></label>
    <div class=" col-md-9 ">
        <?php
        echo $this->Html->link(__('Click here to learn more about our memberships.'),
                '#',
                array(
                    'data-target' => '#plan-view',
                    'data-toggle' => 'modal'
                ));
        ?>     
    </div>

</div>
<?php endif; ?>
<label for="timezone" class="control-label"></label>
<div class=" col-md-9 ">
    <?php echo $this->Form->input('tos',array('type' =>'checkbox','hiddenField' => false,'label' => __('I have read and agree to the ').$this->Html->link(__('terms of service.'),array('controller' => 'pages','terms-of-service'),array('target'=>'_blank')) ) ); ?>
</div>