<?php

echo _tag('div.help_box', __('Variables you can use here:').' '.$dmMailTemplate->showVars() . '. '. __('To output variable in content, surround it with curly brackets, example: {{%variable_name%}}.'));