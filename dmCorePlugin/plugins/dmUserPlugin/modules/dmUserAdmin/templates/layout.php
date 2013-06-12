<?php

$helper = $sf_context->get('layout_helper');

echo 
$helper->renderDoctype(),
$helper->renderHtmlTag(),

  "\n<head>\n",
    $helper->renderHead(),
  "\n</head>\n",
  
  $helper->renderBodyTag('.dm.bg'),

    $sf_content,

    $helper->renderJavascriptConfig(),
      
    $helper->renderJavascripts(),
      
  '</body>',
'</html>';