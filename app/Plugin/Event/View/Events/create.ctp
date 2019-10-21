<?php $this->setCurrentStyle(4);?>
<?php
$eventHelper = MooCore::getInstance()->getHelper('Event_Event');
?>

<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true,'requires'=>array('jquery','mooEvent'), 'object' => array('$', 'mooEvent'))); ?>
mooEvent.initOnCreate();
<?php $this->Html->scriptEnd(); ?>

<div class="create_form">
<div class="bar-content">
    <div class="content_center">
        <form id="createForm">
        <?php
        if (!empty($event['Event']['id'])){
            echo $this->Form->hidden('id', array('value' => $event['Event']['id']));
            echo $this->Form->hidden('photo', array('value' => $event['Event']['photo']));
        }else{
            echo $this->Form->hidden('photo', array('value' => ''));
        }
        ?>	

        <div class="box3">	
            <div class="mo_breadcrumb">
                <h1><?php if (empty($event['Event']['id'])) echo __( 'Add New Event'); else echo __( 'Edit Event');?></h1>
            </div>

            <div class="full_content p_m_10">
                <div class="form_content">
                <ul class="list6 list6sm2">
                        <li>
                            <div class="col-md-2">
                                <label><?php echo __( 'Event Title')?></label>
                            </div>
                            <div class="col-md-10">
                                <?php echo $this->Form->text('title', array('value' => html_entity_decode($event['Event']['title']))); ?>
                            </div>
                        </li>
                        <li>
                            <div class="col-md-2">
                                <label><?php echo __( 'Category')?></label>
                            </div>
                            <div class="col-md-10">
                                <?php echo $this->Form->select( 'category_id', $categories, array( 'value' => $event['Event']['category_id'] ) ); ?>
                            </div>
                        </li>
                        <li>
                            <div class="col-md-2">
                            <label><?php echo __( 'Location')?></label>
                            </div>
                            <div class="col-md-10">
                                <?php echo $this->Form->text('location', array('value' => $event['Event']['location'])); ?>
                                <a href="javascript:void(0)" class="tip profile-tip" title="<?php echo __( 'e.g. Aluminum Hall, Carleton University')?>">(?)</a>
                            </div>

                        </li>
                        <li>
                            <div class='col-md-2'>
                            <label><?php echo __( 'Address')?></label>
                            </div>
                            <div class='col-md-10'>
                                <?php echo $this->Form->text('address', array('value' => $event['Event']['address'])); ?>
                                <a href="javascript:void(0)" class="tip profile-tip" title="<?php echo __( 'Enter the full address (including city, state, country) of the location.<br />This will render a Google map on your event page (optional)')?>">(?)</a>
                            </div>

                        </li>
                        <li>
                            <div class='col-md-2'>
                            <label><?php echo __( 'From Date')?></label>
                            </div>
                            <div class="col-md-10">
                                <div class='col-xs-6'>
                                    <?php
                                    echo $this->Form->text('from', array('class' => 'datepicker', 'value' => $event['Event']['from'] , 'placeholder' => __('Date') )); ?>
                                </div>
                                <div class='col-xs-6'>
                                    <div class="m_l_2">
                                        <?php

                                        echo $this->Form->text('from_time', array('value' => $event['Event']['from_time'], 'class' => 'timepicker' , 'placeholder' => __('Time')));
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="clear"></div>
                        </li>
                        <li>
                            <div class='col-md-2'>
                                <label><?php echo __( 'To Date')?></label>
                            </div>
                            <div class="col-md-10">
                                <div class='col-xs-6'>
                                    <?php
                                    echo $this->Form->text('to', array('class' => 'datepicker', 'value' => $event['Event']['to']  , 'placeholder' => __('Date') )  );	 ?>
                                </div>
                                <div class='col-xs-6'>
                                    <div class="m_l_2">
                                    <?php

                                    echo $this->Form->text('to_time', array('value' => $event['Event']['to_time'], 'class' => 'timepicker' , 'placeholder' => __('Time')));

                                    ?>
                                        </div>
                                </div>
                            </div>
                            <div class="clear"></div>
                        </li>
                        <li>
                            <div class="col-md-2">
                                <label><?php echo __( 'Timezone')?></label>
                            </div>
                            <div class="col-md-10">
                                <?php $currentTimezone = !empty($event['Event']['timezone']) ? $event['Event']['timezone'] : $cuser['timezone']; ?>
                                <?php echo $this->Form->select('timezone', $this->Moo->getTimeZones(), array('empty' => false, 'value' => $currentTimezone)); ?>
                            </div>
                        </li>
                        <li>
                            <div class='col-md-2'>
                                <label><?php echo __( 'Information')?></label>
                            </div>
                            <div class='col-md-10'>
                                <?php echo $this->Form->tinyMCE('description', array('id' => 'editor' ,'escape'=>false,'value' => $event['Event']['description'])); ?>
                            </div>
                        </li>

                        <li>
                            <div class='col-md-2'>
                                <label><?php echo __( 'Event Type')?></label>
                            </div>
                            <div class='col-md-10'>
                                 <?php 
                                echo $this->Form->select('type', array( PRIVACY_PUBLIC  => __( 'Public'), 
                                                                                                                PRIVACY_PRIVATE => __( 'Private')
                                                                                                        ), 
                                                                                                 array( 'value' => $event['Event']['type'], 'empty' => false ) 
                                                                                ); 
                                ?>
                                <a href="javascript:void(0)" class="tip profile-tip" title="<?php echo __( 'Public: anyone can view and RSVP<br />Private: only invited guests can view and RSVP')?>">(?)</a>
                            </div>

                        </li>		
                        <li>
                            <div class="col-md-2">
                                <label><?php echo __( 'Photo')?></label>
                            </div>
                            <div class="col-md-10">
                                <div id="select-0" style="margin: 10px 0 0 0px;"></div>
                                <?php if (!empty($event['Event']['photo'])): ?>
                                <img width="150" id="item-avatar" class="img_wrapper" src="<?php echo  $eventHelper->getImage($event, array('prefix' => '150_square')) ?>" />
                                <?php else: ?>
                                    <img width="150" id="item-avatar" class="img_wrapper" style="display: none;" src="" />
                                <?php endif; ?>
                                
                            </div>
                            <div class="clear"></div>
                        </li>		
                        <li>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                            </div>
                            <div class="col-md-10">
                                <button type='button' class='btn btn-action' id="saveBtn"><?php echo __( 'Save')?></button>
                                
                                <?php if ( !empty( $event['Event']['id'] ) ): ?>
                                    <a href="<?php echo $this->request->base?>/events/view/<?php echo $event['Event']['id']?>" class="button"><?php echo __( 'Cancel')?></a>
                                <?php endif; ?>
                                <?php if ( ($event['Event']['user_id'] == $uid ) || ( !empty( $event['Event']['id'] ) && !empty($cuser['Role']['is_admin']) ) ): ?>
                                    <a href="javascript:void(0)" data-id="<?php echo $event['Event']['id']?>" class="button deleteEvent"><?php echo __( 'Delete')?></a>
                                <?php endif; ?>
                            </div>
                            <div class="clear"></div>
                        </li>
                </ul>
           
                <div class="error-message" style="display:none;"></div>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>
</div>