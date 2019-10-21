<?php
$ids = explode(',',$activity['Activity']['items']);
$groupModel = MooCore::getInstance()->getModel('Group_Group');
$groups_count = $groupModel->find( 'count', array( 'conditions' => array( 'Group.id' => $ids )));
$groups = $groupModel->find( 'all', array( 'conditions' => array( 'Group.id' => $ids ), 'limit' => 3));
$groupModel->cacheQueries = false;
$groupHelper = MooCore::getInstance()->getHelper('Group_Group');
?>
<ul class="activity_content">
<?php foreach ( $groups as $group ): ?>
    <li>
        <div class="activity_item">
            <div class="activity_left">
                <a href="<?php echo $group['Group']['moo_href']?>">
                    <img src="<?php echo $groupHelper->getImage($group, array('prefix' => '150_square'))?>" class="img_wrapper2" />
                </a>
            </div>
            <div class="activity_right ">
                <a class="feed_title" href="<?php echo $group['Group']['moo_href']?>"><?php echo $group['Group']['moo_title']?></a>
                <div class="date comment_message feed_detail_text">
                    <?php echo  $this->Text->convert_clickable_links_for_hashtags($this->Text->truncate(strip_tags(str_replace(array('<br>', '&nbsp;'), array(' ', ''), $group['Group']['description'])), 125, array('exact' => false)), Configure::read('Group.group_hashtag_enabled')) ?>
                </div>
            </div>
        </div>
    </li>
<?php endforeach; ?>
    <?php if ($groups_count > 3): ?>
    <div><?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "groups",
                                            "action" => "ajax_group_joined",
                                            "plugin" => 'group',
                                            'activity_id:' . $activity['Activity']['id'],

                                        )),
             'title' => __('View more groups'),
             'innerHtml'=> __('View more groups'),
     ));
 ?> </div>
    <?php endif; ?>
</ul>