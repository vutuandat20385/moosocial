
<?php if( !$this->isEmpty('north') ): ?>
<?php echo $north; ?>
<?php endif; ?>

    <?php if (!empty($is_profile_page)): ?>
        <?php echo $this->element('user/header_profile'); ?>
   <?php endif; ?>
        <div id="leftnav" <?php if( !$this->isEmpty('west') ) echo 'class="sl-rsp-modal col-md-3"' ?>>
            <?php if( !$this->isEmpty('west') ): ?>

            <div class="visible-xs visible-sm closeButton">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            </div>
            <?php endif; ?>
            <?php echo $west; ?>
        </div>
        <?php if( !$this->isEmpty('east') ): ?>
            <div id="right"  class="sl-rsp-modal col-md-3 pull-right">

                <div class="visible-xs visible-sm closeButton">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span> <span class="sr-only">Close</span></button>
                </div>

                 <?php echo $east; ?>
            </div>
         <?php endif; ?>
        <div id="center" <?php if( !$this->isEmpty('east') &&  !$this->isEmpty('west') ): echo 'class="col-md-6"';
                               elseif (($this->isEmpty('east') && !$this->isEmpty('west')) || ($this->isEmpty('west') && !$this->isEmpty('east'))): echo 'class="col-md-9"';
                               
                               endif; ?>>
        <?php echo $center; ?>
        </div>
    

<div class='clear'></div>
<?php if( !$this->isEmpty('south') ): ?>
<?php echo $south; ?>
 <?php endif; ?>

