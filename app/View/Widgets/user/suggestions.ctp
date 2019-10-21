<?php
if (!( empty($uid) && Configure::read('core.force_login') )):
    if (empty($num_item_show)){
        $num_item_show = 10;
    }
    if(isset($title_enable)&&($title_enable)=== ""){
        $title_enable = false; 
    }else {
        $title_enable = true;
    }

    if (!empty($friend_suggestions)) :
    ?>
    <div class="box2 suggestion_block">
        <?php if($title_enable): ?>
        <h3><?php echo  $title ?></h3>
        <?php endif; ?>
        <div class="box_content">
            <ul class="list6">
            <?php foreach ($friend_suggestions as $friend): ?>
                <li><?php echo  $this->Moo->getItemPhoto(array('User' => $friend['User']), array( 'prefix' => '100_square')) ?>
                    <div class="people_info">
                        <div>
                            <?php echo  $this->Moo->getName($friend['User']) ?>
                        </div>
                        <div><?php echo  __n('%s mutual friend', '%s mutual friends', $friend[0]['count'], $friend[0]['count']) ?></div>
                        <div class="request_add_friend">
                            <i class="material-icons">person_add</i>
                            <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "friends",
                                            "action" => "ajax_add",
                                            "plugin" => false,
                                            $friend['User']['id']
                                            
                                        )),
             'title' => sprintf(__('Send %s a friend request'), h($friend['User']['name'])),
             'innerHtml'=> __('Add as friend'),
          'id' => 'addFriend_' .  $friend['User']['id']
     ));
 ?>
                            
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            
        </div>
    </div>
    <?php
endif;
endif;
?>