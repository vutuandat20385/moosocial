<div class="content_center_home">
    <div class="mo_breadcrumb">
        <h1><?php echo __('My Photos') ?></h1>
        <?php
        $this->MooPopup->tag(array(
            'href' => $this->Html->url(array("controller" => "albums",
                "action" => "create",
                "plugin" => 'photo',
            )),
            'title' => __('Create New Album'),
            'innerHtml' => __('Create New Album'),
            'class' => 'topButton button button-action button-mobi-top'
        ));
        ?>
    </div>
    <ul class="albums photo-albums" id="album-list-content">
        <?php echo $this->element('lists/albums_list'); ?>
    </ul>
    <div class="clear"></div>
</div>