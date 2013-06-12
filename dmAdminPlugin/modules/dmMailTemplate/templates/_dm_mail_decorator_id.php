<?php

if ($dm_mail_template->get('dm_mail_decorator_id')) {

    $text = $dm_mail_template->get('Decorator')->__toString();
    if (!$dm_mail_template->get('Decorator')->get('is_active')) {
        $text = sprintf('<span style="text-decoration: line-through">%s</span> (%s)', $text, __('decorator is inactive'));
    }

    if ($sf_user->canAccessToModule('dmMailDecorator')) {
        echo _link($dm_mail_template->get('Decorator'))
                ->text($text)
                ->set('.associated_record.s16right.s16_arrow_up_right_medium');
    } else {
        echo $text;
    }


}  else {
   echo '-';
}