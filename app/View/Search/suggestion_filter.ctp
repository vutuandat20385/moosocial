<?php if($this->request->is('ajax')): ?>
<script type="text/javascript">
    require(["jquery","mooSearch"], function($,mooSearch) {
        mooSearch.init();
    });
</script>
<?php else: ?>
<?php $this->Html->scriptStart(array('inline' => false, 'domReady' => true, 'requires'=>array('jquery','mooSearch'), 'object' => array('$', 'mooSearch'))); ?>
mooSearch.init();
<?php $this->Html->scriptEnd(); ?> 
<?php endif; ?>

<?php if($this->request->is('ajax')) $this->setCurrentStyle(2); ?>
<?php if(!$this->request->is('ajax')):?>
<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>
    
    <div class='bar-content'>
        <div class="box2">
            <h3><?php echo __('Search Filters')?></h3>
            <div class='box_content'>
                <ul class="list2" id="global-search-filters">
                    <li><a href="<?php echo $this->request->base?>/search/index/<?php echo $keyword?>" class="no-ajax"><i class="material-icons">list</i> <?php echo __('All Results')?></a></li>
                    <li <?php echo  ($type == 'user')? 'class="current"':'' ?>><a data-url="<?php echo $this->request->base?>/search/suggestion/user/<?php echo $keyword?>" id="filter-users" href="#"><i class="material-icons">person</i> <?php echo __('People')?></a></li>
                    <?php if ( !empty( $other_header ) ): ?>
                        <?php foreach($other_header as $k => $value):?>
                            <li <?php echo  ($type == $k)? 'class="current"':'' ?>>
                                <a data-url="<?php echo $this->request->base?>/search/suggestion/<?php echo  lcfirst($k);?>/<?php echo $keyword;?>" id="filter-<?php echo strtolower($k);?>s" href="#">
                                    <i class="material-icons"><?php echo $value['icon_class']?></i> <?php echo $value['header']?>
                                </a>
                            </li>
                        <?php endforeach;?>
                    <?php endif;?>
                </ul>
            </div>
        </div>
    </div>

<?php $this->end(); ?>
<?php endif; ?>
<?php if (!empty($is_activity)):?>

<?php if(isset($page) && $page != 1): ?>
	<?php echo $this->element('activities');?>
	<?php return;?>
<?php endif;?>
<div class="bar-content">

    <div class='content_center'>
        <div class='mo_breadcrumb'>
            <h1><?php echo __('Search Results')?> "<?php echo h($keyword)?>"</h1>
        </div>
        <div id="search-content" class='bar-content'>
            <?php if ( !empty( $activities ) ): ?>
                    <?php if(isset($page) && $page == 1): ?>
                          <ul id="list-content" class="list6 comment_wrapper">
                    <?php endif ?>
                        <?php echo $this->element('activities');?>
                    <?php if(isset($page) && $page == 1): ?>
                         </ul>
                    <?php endif ?>
            <?php else:?>
                <p align="center"><?php echo __('Nothing found')?></p>
            <?php endif; ?>
        </div>
        <div class="clear"></div>
    </div>
</div>

<?php return; endif;?>
<?php if(empty($more_link)):  ?>
<div class="bar-content">

    <div class='content_center'>
        <div class='mo_breadcrumb'>
            <h1><?php echo __('Search Results')?> "<?php echo h($keyword)?>"</h1>
        </div>
        <div id="search-content" class='bar-content'>
            <?php if ( !empty( $result ) ): ?>
                <?php if(!empty($users)): ?>
                    <?php if(isset($page) && $page == 1): ?>
                        <ul id="list-content" class="users_list">
                    <?php endif ?>
                        <?php echo $this->element($element_list_path);?>
                    <?php if(isset($page) && $page == 1): ?>
                         </ul>
                    <?php endif ?>
                <?php else: ?>
                    <ul id="list-content" class="search-list-filter ">
                        <?php echo $this->element($element_list_path);?>
                    </ul>
                <?php endif; ?>
            <?php else:?>
                <p align="center"><?php echo __('Nothing found')?></p>
            <?php endif; ?>
        </div>
        <div class="clear"></div>
    </div>
</div>
<?php else: ?>
    <?php if ( !empty( $result ) ): ?>
        <?php if(isset($page) && $page == 1): ?>
            <ul id="list-content" class="list6 comment_wrapper">
        <?php endif; ?>
            <?php echo $this->element($element_list_path);?>
        <?php if(isset($page) && $page == 1): ?>
            </ul>
        <?php endif; ?>
    <?php else:?>
        <p align="center"><?php echo __('Nothing found')?></p>
    <?php endif; ?>
<?php endif; ?>