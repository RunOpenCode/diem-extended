<?php

if (!function_exists('str_get_html')) {
    require_once dirname(__FILE__) . '/../../vendor/simplehtmldom/simple_html_dom.php';
}

/**
 * Class dmRichEditorContentParser
 *
 * Goes trough HTML content and looks for data attributes:
 *  - data-dme-media-id
 *      - data-dme-media-resize-method
 *  - data-dme-page-id
 * Updates their URLs to valid URI resources due to change in page/media physical structure
 *
 * Logs missing files/pages to event log.
 *
 * @author TheCelavi
 * @package Diem Extended
 * @version 1.0
 */

class dmRichEditorContentParser {

    protected $log;
    protected $helper;

    public function __construct(dmEventLog $log, $helper)
    {
        $this->log = $log;
        $this->helper = $helper;
    }

    /**
     * @param string $content                   A HTML content to parse
     * @param bool $removeAttributes            Flag should custom data attributes be removed with parsing, default false
     * @param bool $removeMissingElements       Flag should missing elements be removed, default false
     * @return string                           Parsed HTML string with updated links
     */
    public function parse($content, $removeAttributes = false, $removeMissingElements = false)
    {
        $html = str_get_html($content);

        foreach ($html->find('img') as $image) {
            if ($id = intval($image->getAttribute('data-dme-media-id'))) {
                $media = dmDb::table('dmMedia')->findOneByIdWithFolder($id);
                if ($media) {
                    $src = '/'.$media->getWebPath();

                    $width = ($image->width) ? $image->width : null;
                    $height = ($image->height) ? $image->height : null;
                    if ($width || $height) {
                        $src = $this->getHelper()->media($media)
                            ->size($width, $height)
                            ->method(($image->getAttribute('data-dme-media-resize-method')) ? $image->getAttribute('data-dme-media-resize-method') : dmConfig::get('image_resize_method'))
                            ->getSrc();
                    }
                    $image->src = $src;
                    if ($removeAttributes) {
                        $image->removeAttribute('data-dme-media-id');
                        $image->removeAttribute('data-dme-media-resize-method');
                    }
                } else {
                    $this->log($id, $image->src, 'image');
                    if ($removeMissingElements) {
                        $image->outertext = '';
                    }
                }
            }
        }

        foreach ($html->find('a') as $link) {
            if ($id = intval($link->getAttribute('data-dme-media-id'))) {
                $media = dmDb::table('dmMedia')->findOneByIdWithFolder($id);
                if ($media) {
                    $link->href = '/'.$media->getWebPath();
                    if ($removeAttributes) {
                        $image->removeAttribute('data-dme-media-id');
                    }
                } else {
                    $this->log($id, $link->href, 'media link');
                    if ($removeMissingElements) {
                        $link->innertext =  '';
                        $link->outertext = '';
                    }
                }
            } elseif ($id = intval($link->getAttribute('data-dme-page-id'))) {
                $page = dmDb::table('DmPage')->createQuery('P')->withI18n()->where('P.id = ?', $id)->fetchOne();
                if ($page) {
                    $link->href = '/'.$page->getSlug();
                    if ($removeAttributes) {
                        $image->removeAttribute('data-dme-page-id');
                    }
                } else {
                    $this->log($id, $link->href, 'page link');
                    if ($removeMissingElements) {
                        $link->innertext =  '';
                        $link->outertext = '';
                    }
                }
            }
        }

        $content = $html->save();

        $html->clear();
        unset($html);

        return $content;
    }

    protected function log($id, $uri, $type = 'image')
    {
        $this->log->log(array(
            'server'  => $_SERVER,
            'action'  => 'error',
            'type'    => 'warning', // TODO create icon in sprite 24
            'subject' => sprintf('Missing %s on path %s with ID %s', $type, $uri, $id)
        ));
    }

    protected function getHelper()
    {
        return $this->helper;
    }
}