<div class="bar-content">
    <div class="content_center">
        <?php if ($user_id == $uid): ?>
            <div class="bar-content profile-sub-menu">
                <?php
                $this->MooPopup->tag(array(
                    'href' => $this->Html->url(array("controller" => "videos",
                        "action" => "create",
                        "plugin" => 'video',
                    )),
                    'title' => __('Share New Video'),
                    'innerHtml' => __('Share New Video'),
                    'class' => 'topButton button button-action'
                ));
                ?>

                <!-- Hook for video upload -->
                <?php $this->getEventManager()->dispatch(new CakeEvent('Video.View.Elements.uploadVideo', $this)); ?>
                <!-- Hook for video upload -->
            </div>
        <?php endif; ?>
        <ul class="albums" id="list-content">
        <?php echo $this->element('lists/videos_list'); ?>
        </ul>
    </div>
</div>