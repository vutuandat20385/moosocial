<?php $this->setCurrentStyle(4);?>
<div class="bar-content">
    <?php $this->setCurrentStyle(4);?>
    <div class="inner404">
        <h2><?php echo __('<span>OOPS!</span> This page cannot be found')?></h2>
        <div>Get back on track</div>
        <a class="btn btn-action" href="<?php echo  $this->request->base; ?>/"><?php echo __('Go back home page')?></a>
        <div class="page-not-found-img">
            <img src="<?php echo $this->request->webroot?>img/page_not_found_1.png" />
        </div>
    </div>
</div>