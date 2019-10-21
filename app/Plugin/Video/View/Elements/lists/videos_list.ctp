<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooVideo"], function($, mooVideo) {
        mooVideo.initOnListing();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery', 'mooVideo'), 'object' => array('$', 'mooVideo'))); ?>
mooVideo.initOnListing();
<?php $this->Html->scriptEnd(); ?>
<?php endif; ?>

<?php
$videoHelper = MooCore::getInstance()->getHelper('Video_Video');
?>
    <?php
    if (!empty($videos) && count($videos) > 0)
    {
        foreach ($videos as $video):
    ?>

            <li class="video-list-index full_content ">

            <div class="item-content">
                <?php if(!empty( $ajax_view )): ?>
                <a href="javascript:void(0)" data-url="<?php echo $this->request->base?>/videos/ajax_view/<?php echo $video['Video']['id']?>" class="ajaxLoadPage video_cover">
                   <div>
                   <img src='<?php echo $videoHelper->getImage($video, array('prefix' => '450'))?>' />
                    </div>
                </a>
                <?php else: ?>
                <a href="<?php echo $this->request->base?>/videos/view/<?php echo $video['Video']['id']?>/<?php echo seoUrl($video['Video']['title'])?>" class="video_cover">
                   <div>
                   <img src='<?php echo $videoHelper->getImage($video, array('prefix' => '450'))?>' />
                    </div>
                </a>
                <?php endif; ?>
                
            <?php if (($video['User']['id'] == $uid) || (!empty($cuser) && $cuser['Role']['is_admin'] ) || (isset($video['Video']['admins']) && in_array($uid, $video['Video']['admins'])) || (!empty($admins) && in_array($uid, $admins) )): ?>
                <div class="list_option" style="top:2px;right:2px;">
                    <div class="dropdown">
                        <button id="dropdown-edit" data-target="#" data-toggle="dropdown" >
                            <i class="material-icons">more_vert</i>
                        </button>
                        <ul role="menu" class="dropdown-menu" aria-labelledby="dropdown-edit" style="float: right;">
                            <li>
                                <?php
                                    $this->MooPopup->tag(array(
                                           'href'=>$this->Html->url(array("controller" => "videos",
                                                                          "action" => "create",
                                                                          "plugin" => 'video',
                                                                          $video['Video']['id']

                                                                      )),
                                           'title' => __('Edit Video'),
                                           'innerHtml'=> __( 'Edit Video'),
                                   ));
                               ?>
                                 </li>
                            <li><a href="javascript:void(0)" class="deleteVideo" data-id="<?php echo $video['Video']['id'] ?>"> <?php echo __( 'Delete Video')?></a></li>
                            <li class="seperate"></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
                <div class="video_info">
                    <?php if ( !empty( $ajax_view ) ): ?>
                    <a href="javascript:void(0)" class="ajaxLoadPage" data-url="<?php echo $this->request->base?>/videos/ajax_view/<?php echo $video['Video']['id']?>"><?php echo $this->Text->truncate( $video['Video']['title'], 60 )?></a>
                    <?php else: ?>
                    <a href="<?php echo $this->request->base?>/videos/view/<?php echo $video['Video']['id']?>/<?php echo seoUrl($video['Video']['title'])?>"><?php echo $this->Text->truncate( $video['Video']['title'], 60 )?></a>
                    <?php endif; ?>
                    
                    <div class="extra_info"><?php echo __( 'Posted by')?> <?php echo $this->Moo->getName($video['User'], false)?>  <?php echo $this->Moo->getTime($video['Video']['created'], Configure::read('core.date_format'), $utz)?> 
                        <?php if (empty($type)): ?>
                        &middot; 
                            <?php if(empty($video['Video']['group_id'])): ?>
                                <?php if ($video['Video']['privacy'] == PRIVACY_PUBLIC): ?>
                                <a class="tip" href="javascript:void(0);" original-title="Shared with: Everyone"> <i class="material-icons">public</i></a>
                                <?php elseif ($video['Video']['privacy'] == PRIVACY_ME): ?>
                                <a class="tip" href="javascript:void(0);" original-title="Shared with: Only Me"> <i class="material-icons">lock</i></a>
                                <?php elseif ($video['Video']['privacy'] == PRIVACY_FRIENDS): ?>
                                <a class="tip" href="javascript:void(0);" original-title="Shared with: Friends Only"> <i class="material-icons">people</i></a>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($video['Video']['privacy'] == PRIVACY_PUBLIC): ?>
                                    <a class="tip" href="javascript:void(0);" original-title="Shared with: Everyone"> <i class="material-icons">public</i></a>
                                <?php else: ?>
                                    <a class="tip" href="javascript:void(0);" original-title="<?php echo __('Shared with: member of their group');?>"> <i class="material-icons">people</i></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php $this->Html->rating($video['Video']['id'],'videos', 'Video'); ?>
                </div>
            </div>
        </li>
    <?php 
        endforeach;
    } 

    else
        echo '<li class="clear text-center no-result-found" style="width:100%;overflow:hidden">' . __( 'No more results found') . '</li>';
    ?>
    <?php if (!empty($more_result)): ?>
        <?php $this->Html->viewMore($more_url) ?>
    <?php endif; ?>
