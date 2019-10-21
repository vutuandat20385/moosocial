<?php
    $this->loadLibrary(array('tagCloud'));
?>
<?php if (!empty($formatted_hashtag)): ?>
    <?php $this->Html->scriptStart(array('inline' => false)); ?>
        var oWords = <?php echo json_encode($formatted_hashtag); ?>;

        var word_array = [];
        var link = mooConfig.url.base+'/search/hashtags/';
        $.each(oWords,function(key,val){
            word_array.push({text: key, weight: val, link: link+key+'/tabs:<?php echo $type; ?>'});
        });
        
        

        $(function() {
            // When DOM is ready, select the container element and call the jQCloud method, passing the array of words as the first argument.
            $(".tag-cloud").jQCloud(word_array);
        });
    <?php $this->Html->scriptEnd(); ?>
    <div class="box2">
        <?php if($title_enable): ?>
            <h3><?php echo $title;?></h3>
        <?php endif; ?>
        <div class="box_content">
            <div class="tag-cloud" style="height:350px"></div>
        </div>
    </div>
<?php endif; ?>