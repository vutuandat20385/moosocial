<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('My Videos') ?></h1>
        <?php
        $this->MooPopup->tag(array(
            'href' => $this->Html->url(array("controller" => "videos",
                "action" => "create",
                "plugin" => 'video',
            )),
            'title' => __('Share New Video'),
            'innerHtml' => __('Share New Video'),
            'class' => 'topButton button button-action button-mobi-top'
        ));
        ?>


    </div>
    <ul class="list4 albums" id="list-content">
        <?php echo $this->element('lists/videos_list'); ?>
    </ul>
</div>