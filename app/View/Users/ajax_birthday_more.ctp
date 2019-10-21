<?php if($this->request->is('ajax')) $this->setCurrentStyle(4);?>

<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooUser"], function($,mooUser) {
        mooUser.initBirthdayPopup();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooUser'), 'object' => array('$', 'mooUser'))); ?>
mooUser.initBirthdayPopup();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<div class="title-modal">
    <?php echo __("Today's Birthdays")?>
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <ul class="list_block">
        <?php foreach ($birthday as $u): ?>
            <li class="today-birthday">
                <?php echo $this->Moo->getItemPhoto(array('User' => $u['User']),array( 'prefix' => '100_square'), array('class' => 'img_wrapper2 user_avatar_large tip'))?>
                <?php
                if ( !empty(  $u['User']['username'] ) )
                    $url = $this->request->base . '/-' .  $u['User']['username'];
                else
                    $url = $this->request->base . '/users/view/'. $u['User']['id'];
                ?>
                <?php
                $tz  = new DateTimeZone($utz);

                ?>
                <div class="more p_l_60">
                    <a href="<?php echo  $url; ?>"><?php echo  $u['User']['name']; ?></a>
                    <div>
                        <form id="wallForm_<?php echo  $u['User']['id']; ?>">
                            <?php
                            echo $this->Form->hidden('target_id', array('value' => $u['User']['id']));
                            echo $this->Form->hidden('type', array('value' => APP_USER));
                            echo $this->Form->hidden('action', array('value' => 'wall_post'));
                            echo $this->Form->hidden('wall_photo_id');
                            echo $this->Form->hidden('privacy',array('value'=>1));
                            echo $this->Form->hidden('params',array('value'=>'birthday_wish'));

                            $text = __("Write on %s's timeline",$u['User']['name']);

                            ?>
                                <div class="birthday-wish">
                                    <?php if(empty($users_sent) || !in_array($u['User']['id'],$users_sent)): ?>
                                    <?php  echo $this->Form->text('message_'.$u['User']['id'], array('placeholder' => $text,'name'=>"data[message]")); ?>
                                        <a href="javascript:void(0)" data-id="<?php echo  $u['User']['id'] ?>" class="btn btn-action postFriendWall birthday-post" id="status_btn_<?php echo  $u['User']['id'] ?>"><?php echo __('Send')?></a>
                                    <?php else: ?>
                                    <?php echo __("Birthday wish is sent"); ?>
                                    <?php endif;?>
                                    
                                </div>

                        </form>
                    </div>
                    <div><i class="fa fa-envelope"></i><?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "conversations",
                                            "action" => "ajax_send",
                                            "plugin" => false,
                                            $u['User']['id']
                                            
                                        )),
             'title' => __('Send a private message'),
             'innerHtml'=> __('Send a private message'),
          'class' => 'more-birthday-email p_l_5',
     ));
 ?>
                        </div>
                </div>
            </li>
            <hr/>
        <?php endforeach; ?>
    </ul>
</div>