<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooEmoji"], function($,mooEmoji) {
        mooEmoji.init('message');
    });
</script>
<?php endif; ?>

<div class="bar-content full_content ">
    <div class="content_center">
        <div class="post_body">
        <div class="mo_breadcrumb bookmark-button">
            <h1 class="visible-xs visible-sm"><?php echo $group['Group']['name']?></h1>
            <?php if ((empty($uid) && !empty($invited_user)) ||
                    (!empty($uid) && (($group['Group']['type'] != PRIVACY_PRIVATE && empty($my_status['GroupUser']['status'])) || ($group['Group']['type'] == PRIVACY_PRIVATE && !empty($my_status) && $my_status['GroupUser']['status'] == 0 ) ) ) ): ?>

                <a href="<?php echo  $this->request->base ?>/groups/do_request/<?php echo  $group['Group']['id'] ?>" class="button button-action topButton button-mobi-top join-btn"><?php echo  __('Join') ?></a>

            <?php endif; ?>
            
            <!-- New hook -->
            <?php $this->getEventManager()->dispatch(new CakeEvent('groups.view.afterRenderJoinButton', $this,array('group'=>$group))); ?>
            <!-- New hook -->

            <?php if (!empty($request_count)): ?>
                <div class="button button-action topButton button-mobi-top">
                    <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "groups",
                                            "action" => "ajax_requests",
                                            "plugin" => 'group',
                                            $group['Group']['id'],

                                        )),
             'title' => __('Join Requests'),
          'class' => 'join-btn',
          'id' => 'join-request',
          'data-request' => $request_count,
             'innerHtml'=> $request_count . " " .  __n('join request', 'join requests', $request_count),
     ));
 ?>

                </div>
            <?php endif; ?>
        </div>


        <div class="p_m_10">
            <h2 class="header_h2" style="margin-top: 0px"><?php echo  __('Information') ?></h2>
        <div class="">
            <?php if ($uid): ?>

            <div class="list_option">
                <div class="dropdown">
                    <button id="dropdown-edit" data-target="#" data-toggle="dropdown">
                        <i class="material-icons">more_vert</i>
                    </button>

                    <ul role="menu" class="dropdown-menu" aria-labelledby="dropdown-edit">

                        <?php if ( ( !empty($my_status) && $my_status['GroupUser']['status'] == GROUP_USER_MEMBER  && $group['Group']['type'] != PRIVACY_PRIVATE) ||
                                !empty($cuser['Role']['is_admin'] ) ||
                                ( !empty($my_status) && $my_status['GroupUser']['status'] == GROUP_USER_ADMIN)
                                ): ?>
                        <li>
                            <?php
                                $this->MooPopup->tag(array(
                                       'href'=>$this->Html->url(array("controller" => "groups",
                                                                      "action" => "ajax_invite",
                                                                      "plugin" => 'group',
                                                                      $group['Group']['id'],

                                                                  )),
                                       'title' => __( 'Invite Friends'),
                                       'innerHtml'=> __( 'Invite Friends'),
                               ));
                            ?>
                        </li>
                        <?php endif; ?>

                        <?php if ( ( !empty($my_status) && $my_status['GroupUser']['status'] == GROUP_USER_ADMIN && $group['Group']['user_id'] == $uid ) || !empty($cuser['Role']['is_admin'] ) ): ?>
                        <li><a href="<?php echo $this->request->base?>/groups/create/<?php echo $group['Group']['id']?>"><?php echo __( 'Edit Group')?></a></li>
                        <li><a href="javascript:void(0)" data-id="<?php echo  $group['Group']['id'] ?>" class="deleteGroup"><?php echo __( 'Delete Group')?></a></li>
                        <?php endif; ?>

                        <li>
                            <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "reports",
                                            "action" => "ajax_create",
                                            "plugin" => false,
                                            'group_group',
                                            $group['Group']['id'],
                                        )),
             'title' => __( 'Report Group'),
          'data-dismiss' => 'modal',
             'innerHtml'=> __( 'Report Group'),
     ));
 ?>
                           </li>
                        <li class="seperate"></li>
                        <?php if ( !empty($my_status) && ( $my_status['GroupUser']['status'] == GROUP_USER_MEMBER || $my_status['GroupUser']['status'] == GROUP_USER_ADMIN ) && ( $uid != $group['Group']['user_id'] ) ): ?>
			<li><a href="javascript:void(0)" class="leaveGroup" data-id="<?php echo $group['Group']['id']?>"><?php echo __('Leave Group')?></a></li>
			<?php endif; ?>
                        <?php if (isset($my_status['GroupUser']['status'])):?>
                            <?php
                                $settingModel = MooCore::getInstance()->getModel("Group.GroupNotificationSetting");
                                $checkStatus = $settingModel->getStatus($group['Group']['id'],$uid);
                            ?>
                            <li><a href="<?php echo $this->request->base?>/groups/stop_notification/<?php echo $group['Group']['id']?>"><?php if ($checkStatus) echo __( 'Turn Off Notification'); else echo __('Turn On Notification');?></a></li>
                        <?php endif;?>
                        <?php // do not add "Do Feature" for private group ?>
                        <?php if ( ( !empty($cuser) && $cuser['Role']['is_admin'] && $group['Group']['type'] != PRIVACY_PRIVATE ) ): ?>
                        <?php if ( !$group['Group']['featured'] ): ?>
                        <li><a href="<?php echo $this->request->base?>/groups/do_feature/<?php echo $group['Group']['id']?>"><?php echo __( 'Feature Group')?></a></li>
                        <?php else: ?>
                        <li><a href="<?php echo $this->request->base?>/groups/do_unfeature/<?php echo $group['Group']['id']?>"><?php echo __( 'Unfeature Group')?></a></li>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($group['Group']['type'] != PRIVACY_PRIVATE && $group['Group']['type'] != PRIVACY_RESTRICTED): ?>
                        <li>
                            <a href="javascript:void(0);" share-url="<?php echo $this->Html->url(array(
                                    'plugin' => false,
                                    'controller' => 'share',
                                    'action' => 'ajax_share',
                                    'Group_Group',
                                    'id' => $group['Group']['id'],
                                    'type' => 'group_item_detail'
                                ), true); ?>" class="shareFeedBtn"><?php echo __('Share'); ?></a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <ul class="group-info info">
            <li><label><?php echo  __('Category') ?>:</label>
                <div>
                <a href="<?php echo  $this->request->base ?>/groups/index/<?php echo  $group['Group']['category_id'] ?>/<?php echo  seoUrl($group['Category']['name']) ?>">
                    <?php echo  $group['Category']['name'] ?></a>
                </div>
            </li>
            <li><label><?php echo  __('Type') ?>:</label>
                <div>
                <?php
                switch ($group['Group']['type']) {
                    case PRIVACY_PUBLIC:
                        echo __('Public (anyone can view and join)');
                        break;

                    case PRIVACY_PRIVATE:
                        echo __('Private (only group members can view details)');
                        break;

                    case PRIVACY_RESTRICTED:
                        echo __('Restricted (anyone can join upon approval)');
                        break;
                }
                ?>
                </div>
            </li>
            <?php
            if ($group['Group']['type'] != PRIVACY_PRIVATE || (!empty($cuser) && $cuser['Role']['is_admin'] ) ||
                    (!empty($my_status) && ( $my_status['GroupUser']['status'] == GROUP_USER_MEMBER || $my_status['GroupUser']['status'] == GROUP_USER_ADMIN ) )
            ):
                ?>
                <li><label><?php echo  __('Description') ?>:</label>
                    <div>
                        <div class="video-description truncate" data-more-text="<?php echo __( 'Show More')?>" data-less-text="<?php echo __( 'Show Less')?>">
                            <?php echo $this->Moo->cleanHtml($this->Text->convert_clickable_links_for_hashtags( $group['Group']['description'] , Configure::read('Group.group_hashtag_enabled')))?>
                        </div>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
            <?php $this->Html->rating($group['Group']['id'],'groups', 'Group'); ?>
        </div>
    </div>
    </div>
</div>
<?php
$photoHelper = MooCore::getInstance()->getHelper('Photo_Photo');
if ($group['Group']['type'] != PRIVACY_PRIVATE || (!empty($cuser) && $cuser['Role']['is_admin'] ) ||
        (!empty($my_status) && ($my_status['GroupUser']['status'] == GROUP_USER_MEMBER || $my_status['GroupUser']['status'] == GROUP_USER_ADMIN ) )
):
    ?>
    <?php if (!empty($photos)): ?>
        <div class="bar-content full_content p_m_10">
            <div class="content_center">
                <h2 class="header_h2"><?php echo  __('Photos') ?></h2>
                <ul class="photo-list">
                    <?php foreach ($photos as $photo): ?>
                        <li class="photoItem" >
                            <div class="p_2">
                                <a href="<?php echo $photo['Photo']['moo_href']?>" class="layer_square photoModal" style="background-image:url(<?php echo $photoHelper->getImage($photo, array('prefix' => '150_square'));?>)" href="<?php echo  $this->request->base ?>/photos/view/<?php echo  $photo['Photo']['id'] ?>#content"></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="clear"></div>
                <?php
                    if ($photo_count > Configure::read('Photo.photo_item_per_pages')):
                ?>
                        <a href="<?php echo $this->request->base; ?>/groups/view/<?php echo $group['Group']['id']; ?>/tab:photos"><?php echo __('View More'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <div class="p_7">
        <h2 class="header_title"><?php echo  __('Recent Activities') ?></h2>
        <?php $this->MooActivity->wall($groupActivities)?>
    </div>
<?php endif; ?>

