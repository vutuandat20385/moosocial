
<div id="footer">
    <div class="bar-content">
        <?php $this->doLoadingFooter();?>

    </div>
    <?php echo html_entity_decode( Configure::read('core.footer_code') )?><br />
    <?php if (Configure::read('core.show_credit')): ?>
    <span class="date"><?php echo __('Powered by')?> 
        <a href="http://www.moosocial.com" target="_blank">mooSocial <?php echo Configure::read('core.version')?></a>
    </span>
    <?php endif; ?>
    <?php if (Configure::read('core.select_language') || Configure::read('core.select_theme')): ?>
        <?php if (Configure::read('core.select_language')): ?>
                           
        <?php if (Configure::read('core.show_credit')): ?>&nbsp;.&nbsp;<?php endif; ?>
        <a href="<?php echo  $this->request->base ?>/home/ajax_lang"
        data-target="#langModal" data-toggle="modal"
        title="<?php echo  __('Language') ?>">
                <?php echo  (!empty($site_langs[Configure::read('Config.language')])) ? $site_langs[Configure::read('Config.language')] : __('Change') ?>
        </a>
                            
        <?php endif; ?>
                <?php if(empty($isMobile)): ?>

        <?php if (Configure::read('core.select_theme')): ?>
        <?php if (Configure::read('core.select_language')): ?>&nbsp;.&nbsp;<?php endif; ?>
        <?php
      $this->MooPopup->tag(array(
             'href'=>$this->Html->url(array("controller" => "home",
                                            "action" => "ajax_theme",
                                            "plugin" => false,
                                            
                                        )),
             'title' => __('Theme'),
             'innerHtml'=> (!empty($site_themes[$this->theme])) ? $site_themes[$this->theme] : __('Change'),
     ));
 ?>

        <?php endif; ?>
                <?php endif; ?>
                
        <?php endif; ?>
</div>