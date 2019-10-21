<?php if($this->request->is('ajax')) $this->setCurrentStyle(4) ?>
<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('Conversations')?></h1>
          <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "conversations",
                                            "action" => "ajax_send",
                                            "plugin" => false,
                                            
                                        )),
             'title' => __('Send New Message'),
             'innerHtml'=> __('Send New Message'),
          'class' => 'topButton button button-action button-mobi-top'
     ));
 ?>
        <?php
        echo $this->Html->link(__('Mark All As Read'),
            array("controller" => "conversations",
                "action" => "mark_all_read",
                "plugin" => false,
            ),
            array('class' => 'topButton button button-action button-mobi-top')
        );
 ?>
        
       

    </div>
    <ul class="list6 comment_wrapper conversation_list" id="list-content">
    <?php echo $this->element( 'lists/messages_list', array( 'more_url' => '/conversations/ajax_browse/page:2' ) ); ?>
    </ul>
</div>