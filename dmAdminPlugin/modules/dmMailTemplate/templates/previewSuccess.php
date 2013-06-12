<?php
use_helper('Date');
use_stylesheet('admin.filter');
use_stylesheet('admin.form');
use_javascript('admin.form');
?>
<div id="sf_admin_container">
    <div id="sf_admin_header"></div>
    <div id="sf_admin_content">
        <div id="sf_admin_form">
            <div class="dm_form_action_bar dm_form_action_bar_top clearfix">
                <div>
                    <?php echo $helper->linkToList(array(  'label' => 'Back to list',  'params' =>   array(  ),  'class_suffix' => 'list',)) ?>
                    <?php echo $helper->linkToDelete($record, array(  'label' => 'Delete',  'title' => 'Delete this %1%',  'params' =>   array(  ),  'confirm' => 'Are you sure?',  'class_suffix' => 'delete',)); ?>
                    <?php echo $helper->linkToAdd(array(  'label' => 'Add',  'title' => 'Add a %1%',  'params' =>   array(  ),  'class_suffix' => 'add',)); ?>
                </div>
            </div>
            <div class="ui-corner-all ui-widget-content" style="padding: 10px">
                <?php
                    echo $content;
                ?>
            </div>
            <div class="dm_form_action_bar dm_form_action_bar_bottom clearfix">
                <div>
                    <?php echo $helper->linkToList(array(  'label' => 'Back to list',  'params' =>   array(  ),  'class_suffix' => 'list',)) ?>
                    <?php echo $helper->linkToDelete($record, array(  'label' => 'Delete',  'title' => 'Delete this %1%',  'params' =>   array(  ),  'confirm' => 'Are you sure?',  'class_suffix' => 'delete',)); ?>
                    <?php echo $helper->linkToAdd(array(  'label' => 'Add',  'title' => 'Add a %1%',  'params' =>   array(  ),  'class_suffix' => 'add',)); ?>
                </div>
            </div>
        </div>
    </div>
    <div id="sf_admin_footer"></div>
</div>