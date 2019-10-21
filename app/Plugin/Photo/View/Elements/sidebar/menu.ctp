<ul class="list2 menu-list" id="browse">
    <li <?php if (empty($this->request->named['category_id'])): ?>class="current"<?php endif; ?> id="browse_all"><a rel="album-list-content" data-url="<?php echo $this->request->base ?>/albums/browse/all" href="<?php echo $this->request->base ?>/albums"><?php echo __('All Photos') ?></a></li>
    <?php if (!empty($uid)): ?>
        <li><a  rel="album-list-content" data-url="<?php echo $this->request->base ?>/albums/browse/my" href="<?php echo $this->request->base ?>/albums"><?php echo __('My Photos') ?></a></li>
        <li><a rel="album-list-content" data-url="<?php echo $this->request->base ?>/albums/browse/friends" href="<?php echo $this->request->base ?>/albums"><?php echo __("Friends' Photos") ?></a></li>
    <?php endif; ?>
    <li class="separate"></li>
</ul>