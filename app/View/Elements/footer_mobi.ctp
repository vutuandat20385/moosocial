<?php if(!empty($uid) || (!$this->isEmpty('east') && $this->isActive('east')) || (!$this->isEmpty('west') && $this->isActive('west')) ):  ?>
<div class="visible-xs visible-sm">
    <div class="mobile-footer">


        <?php if( !$this->isEmpty('west') && $this->isActive('west') ): ?>
        <a class="pull-left" href="#" data-target="#leftnav"><i class="material-icons">format_indent_increase</i></a>
        <?php endif; ?>
        <?php if( !$this->isEmpty('east') && $this->isActive('east') ): ?>
        <a href="#" data-target="#right" class="pull-right"><i class="material-icons">format_indent_decrease</i></a>
        <?php endif; ?>
   

 </div>
</div>
<?php endif; ?>
