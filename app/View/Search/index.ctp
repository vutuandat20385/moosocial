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

<?php $this->setNotEmpty('west');?>
<?php $this->start('west'); ?>

<div class='bar-content'>
    <div class="box2">
        <h3><?php echo __('Search Filters')?></h3>
        <div class='box_content'>
            <ul class="list2" id="global-search-filters">
                <li class="current"><a href="<?php echo $this->request->base?>/search/index/<?php echo $keyword?>" class="no-ajax"><i class="material-icons">list</i> <?php echo __('All Results')?></a></li>
                <li><a data-url="<?php echo $this->request->base?>/search/suggestion/user/<?php echo $keyword?>" id="filter-users" href="#"><i class="material-icons">person</i> <?php echo __('People')?></a></li>
                <li><a data-url="<?php echo $this->request->base?>/search/suggestion/activity/<?php echo $keyword?>" id="filter-activities" href="#"><i class="material-icons">library_books</i> <?php echo __('Activities')?></a></li>
                <?php if ( !empty( $searches ) ): ?>
                    <?php foreach($searches as $k => $search):?>
                        <li>
                            <a data-url="<?php echo $this->request->base?>/search/suggestion/<?php echo  lcfirst($k);?>/<?php echo $keyword;?>" id="filter-<?php echo strtolower($k);?>s" href="#">
                                <i class="material-icons"><?php echo $search['icon_class']?></i> <?php echo $search['header']?>
                            </a>
                        </li>
                    <?php endforeach;?>
                    
                <?php endif;?>
            </ul>
        </div>
    </div>
</div>

<?php $this->end(); ?>
<div class="bar-content">

    <div class='content_center'>
        <div class='mo_breadcrumb'>
             <h1><?php echo __('Search Results')?> "<?php echo h($keyword)?>"</h1>
        </div>
        
        <div id="search-content">
            <?php if ( !empty( $users ) ): ?>
            <h2><?php echo __('People')?></h2>
            <div class="search-more">
                <a href="javascript:void(0)" data-query="users" class="globalSearchMore button"><?php echo __('View More Results')?></a>
            </div>  
            <div class="clear"></div>
            <ul class="users_list">
                <?php echo $this->element( 'lists/users_list' ); ?>
            </ul>
            <?php endif; ?>
            
            <?php if ( !empty( $activities) ): ?>
            <h2><?php echo __('Activities')?></h2>
            <div class="search-more">
                <a href="javascript:void(0)" data-query="activities" class="globalSearchMore button"><?php echo __('View More Results')?></a>
            </div>  
            <div class="clear"></div>
            <ul id="list-content" class="list6 comment_wrapper">
                <?php echo $this->element( 'activities' ); ?>
            </ul>
            <?php endif; ?>
            
            <?php $emptyResult = true; ?>
            <?php if ( !empty( $searches ) ): ?>
                <?php foreach($searches as $k => $search):?>
                    <?php if(!empty($search['notEmpty'])): ?>
                        <h2><?php echo $search['header']?></h2>
                        <div class="search-more">
                            <a href="javascript:void(0)" data-query="<?php echo strtolower($k);?>s" class="globalSearchMore button"><?php echo __('View More Results')?></a>
                        </div>
                         <div class="clear"></div>
                         <ul class="list6">
                        <?php echo $this->element($search['view'], array(), array('plugin' => $k));?>
                         </ul>
                         <?php $emptyResult = false; ?>
                    <?php endif; ?>
                <?php endforeach;?>
            <?php endif; ?>
                         
            <?php if($emptyResult): ?>
            <div align="center"><?php echo __('No result found')?></div>
            <?php endif; ?>
            
        </div>
        <div class="clear"></div>
    </div>
</div>
