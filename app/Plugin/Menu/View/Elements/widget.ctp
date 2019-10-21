<?php
$class_menu = 'menu_' . $menu_id;
echo $this->Menu->generate(null, $menu_id, array('class' => "$class_menu nav navbar-nav menu_top_list"));
?>