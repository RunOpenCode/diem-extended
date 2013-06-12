<?php

if (!function_exists('str_get_html')) {
    require_once dirname(__FILE__) . '/../vendor/simplehtmldom/simple_html_dom.php';
}

class dmMailTemplateRenderer
{

    protected
        $serviceContainer,
        $helper,
        $embeddedMedia = array(),
        $basePath,
        $cacheCleared = false;

    public function __construct(dmBaseServiceContainer $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
        $this->helper = $serviceContainer->getService('helper');
        // TODO FIND A BASE PATH!!!
        $this->basePath = dmConfig::get('mail_template_base_assets_path', ($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'http://www.yourdomain.com');
    }

    public function render(DmMailTemplate $template, Swift_Message $message, array $vars = array())
    {
        $templateFile = $this->loadTemplate($template, $vars);

        ob_start();
        ob_implicit_flush(0);
        extract($this->prepareVars($vars));
        try {
            require($templateFile);
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $content = ob_get_clean();

        if ($template->get('is_html')) {
            $content = $this->updateHTMLPaths($content, $message, $template->get('name'));
        }

        return $content;
    }

    protected function loadTemplate(DmMailTemplate $template, array $vars)
    {
        $culture = $this->serviceContainer->getService('user')->getCulture();

        $fileName = dmOs::join(sfConfig::get('sf_cache_dir'), 'mail_templates', sprintf('%s-%s-%s.php', dmString::slugify($template->get('name')), $culture, dmString::slugify($template->get('updated_at'))));
        if (!file_exists($fileName)) {
            $this->buildTemplate($template, $fileName, $vars);
        }
        return $fileName;
    }

    protected function buildTemplate(DmMailTemplate $template, $fileName, array $vars)
    {
        $body = $template->get('body');
        if ($template->get('is_html')) {
            $decorator = $template->getDecorator();
            if ($decorator && $decorator->get('is_active')) {
                $body = str_replace('{{%body%}}', $body, $decorator->get('template'));
            }
            if (dmConfig::get('mail_template_decorate_HTML_template_with_page_tags', true)) {
                $body = $this->decorateHTMLTemplateWithPageTags($body);
            }
        }
        $body = $this->resolvePHP($body, $vars);
        $fileSystem = $this->serviceContainer->getService('filesystem');
        if (!file_exists(dirname($fileName))) {
            $fileSystem->mkdir(dirname($fileName));
        }
        file_put_contents($fileName, $body);
        chmod($fileName, 0777);
    }

    protected function decorateHTMLTemplateWithPageTags($body)
    {
        return sprintf('<html><head></head><body>%s</body></html>', $body);
    }

    protected function resolvePHP($body, array $vars)
    {
        foreach ($vars as $key => $value) {
            $body = str_replace('{{' . $key . '}}', '<?php if (isset('.$key.')) echo '.$key.'; ?>', $body);
            $body = str_replace($key, '$' . str_replace('%', '', $key), $body);
        }
        $body = str_replace('{{', '<?php', $body);
        $body = str_replace('}}', '?>', $body);
        $body = str_replace('{{=', '<?=', $body);
        return $body;
    }

    protected function prepareVars(array $vars)
    {
        $processed = array();
        foreach ($vars as $key => $value) {
            $processed[str_replace('%', '', $key)] = $value;
        }
        return $processed;
    }

    // PASS
    protected function updateHTMLPaths($content, Swift_Message $message, $templateName)
    {
        $html = str_get_html($content);

        foreach ($html->find('img') as $image) {
            if (isset($this->embeddedMedia[$image->src])) {
                $image->src = $this->embeddedMedia[$image->src];
            } else {
                $key = $image->src;
                $image = $this->updateImagePath($image, $message, $templateName);
                $this->embeddedMedia[$key] = $image->src;
            }
            $image->id = null;
            $image->class = null;
        }
        foreach ($html->find('a') as $page) {
            if (isset($this->embeddedMedia[$page->href])) {
                $page->href = $this->embeddedMedia[$page->href];
            } else {
                $key = $page->href;
                $page = $this->updateLinkPath($page, $message, $templateName);
                $this->embeddedMedia[$key] = $page->href;
            }
            if (dmString::startsWith($page->href, 'cid:')) {
                $page->outertext = '';
            } else {
                $page->id = null;
                $page->class = null;
            }
        }

        $content = $html->save();

        $html->clear();
        unset($html);

        return $content;
    }

    // PASS
    protected function updateImagePath($image, Swift_Message $message, $templateName){
        $id = str_replace('dmMedia-', '', $image->id);
        if (!$id) {
            $id = $image->getAttribute('data-dm-media-id');
        }

        if ($id && is_numeric($id)) {
            $mediaRecord = dmDb::table('dmMedia')->findOneByIdWithFolder($id);
            if ($mediaRecord) {
                $media = $this->helper->media($mediaRecord);
                if (isset($image->width)) {
                    $media->width($image->width);
                }
                if (isset($image->height)) {
                    $media->height($image->height);
                }
                if (dmConfig::get('mail_template_embed_local_images_as_attachments', true)) {
                    try {
                        $image->src = $message->embed(Swift_Image::fromPath(dmOs::join(sfConfig::get('sf_web_dir'), $media->getSrc())));
                    } catch (Exception $e) {
                        $this->logError($templateName, 'local', 'image', $image->src);
                        $image->src = '';
                    }
                } else {
                    if (file_exists(dmOs::join(sfConfig::get('sf_web_dir'), $media->getSrc()))) {
                        $image->src = $this->basePath . $media->getSrc();
                    } else {
                        $this->logError($templateName, 'local', 'image', $image->src);
                    }
                }
                return $image;
            }
        }

        if (dmString::startsWith($image->src, sfConfig::get('sf_root_dir'))) {
            return $this->updateLocalImage($image, $message, $templateName);
        } elseif (dmString::startsWith($image->src, $this->basePath)) {
            $image->src = dmOs::join(sfConfig::get('sf_web_dir'),str_replace($this->basePath, '', $image->src));
            return $this->updateLocalImage($image, $message, $templateName);
        } else {
            return $this->updateRemoteImage($image, $message, $templateName);
        }

    }

    // NOT TESTED
    protected function updateLocalImage($image, Swift_Message $message, $templateName)
    {
        if (file_exists($image->src)) {

            $fileName = dmOs::join(sfConfig::get('sf_web_dir'), 'cache/mail_templates', md5($image->src . $image->width . $image->height) . pathinfo($image->src, PATHINFO_EXTENSION));

            if (!file_exists($fileName)) {
                $sfImage = new sfImage($image->src, $this->serviceContainer->getService('mime_type_resolver')->getByFilename($image->src));

                $width = $sfImage->getWidth();
                $height = $sfImage->getHeight();
                if (isset($image->width) && strpos($image->width, '%') === false) {
                    $width = $image->width;
                }
                if (isset($image->height) && strpos($image->height, '%') === false) {
                    $width = $image->height;
                }
                $sfImage->setQuality(dmConfig::get('image_resize_quality'));
                $sfImage->thumbnail($width, $height, dmConfig::get('image_resize_method'), null);

                $fileSystem = $this->serviceContainer->getService('filesystem');
                if (!file_exists(dirname($fileName))) {
                    $fileSystem->mkdir(dirname($fileName));
                }
                $sfImage->saveAs($fileName);
                chmod($fileName, 0777);
            }

            if (dmConfig::get('mail_template_embed_local_images_as_attachments', true)) {
                $image->src = $message->embed(Swift_Image::fromPath($fileName));
            } else {
                $image->src = $this->basePath . str_replace(sfConfig::get('sf_web_dir'), '', $fileName);
            }
        } else {
            $this->logError($templateName, 'local', 'image', $image->src);
            $image->src = '';
        }
        return $image;
    }

    // NOT TESTED
    protected function updateRemoteImage($image, Swift_Message $message, $templateName)
    {
        $fileName = dmOs::join(sfConfig::get('sf_web_dir'), 'cache/mail_templates', md5($image->src . $image->width . $image->height) . pathinfo($image->src, PATHINFO_EXTENSION));

        if (!file_exists($fileName)) {
            $imageData = file_get_contents($image->src);
            if (!$imageData) {
                $this->logError($templateName, 'remote', 'image', $image->src);
                $image->src = '';
                return$image;
            } else {
                $sfImage = new sfImage();
                $sfImage->loadString($imageData);

                $width = $sfImage->getWidth();
                $height = $sfImage->getHeight();
                if (isset($image->width) && strpos($image->width, '%') === false) {
                    $width = $image->width;
                }
                if (isset($image->height) && strpos($image->height, '%') === false) {
                    $width = $image->height;
                }
                $sfImage->setQuality(dmConfig::get('image_resize_quality'));
                $sfImage->thumbnail($width, $height, dmConfig::get('image_resize_method'), null);

                $fileSystem = $this->serviceContainer->getService('filesystem');
                if (!file_exists(dirname($fileName))) {
                    $fileSystem->mkdir(dirname($fileName));
                }
                $sfImage->saveAs($fileName);
                chmod($fileName, 0777);
            }
        }

        if (dmConfig::get('mail_template_embed_remote_images_as_attachments', true)) {
            $image->src = $message->embed(Swift_Image::fromPath($fileName));
        } else {
            $image->src = $this->basePath . str_replace(sfConfig::get('sf_web_dir'), '', $fileName);
        }

        return $image;
    }

    // PASS
    protected function updateLinkPath($page, Swift_Message $message, $templateName)
    {
        $id = str_replace('dmPage-', '', $page->id);
        if (!$id) {
            $id = $page->getAttribute('data-dm-page-id');
        }

        if (!$id || !is_numeric($id)) {
            return $this->updateFilePath($page, $message, $templateName);
        }
        $pageRecord = dmDb::table('DmPage')->findOneByIdWithI18n($id);

        if (!$pageRecord) {
            $this->logError($templateName, 'local', 'page', $page->href);
            $page->href = '';
            return $page;
        }

        $page->href = $this->basePath . $this->helper->link($pageRecord)->getHref();

        return $page;
    }

    protected function updateFilePath($file, Swift_Message $message, $templateName)
    {
        $id = str_replace('dmFile-', '', $file->id);
        if (!$id) {
            $id = $file->getAttribute('data-dm-file-id');
        }

        if (is_numeric($id)) {
            $mediaRecord = dmDb::table('dmMedia')->findOneByIdWithFolder($id);
            if ($mediaRecord) {
                $embed = false;
                $embedExtensions = array_map('trim', explode(',', dmConfig::get('mail_template_embed_file_types', 'doc,docx,xls,xlsx,ppt,pptx,pdf,mdb')));
                foreach ($embedExtensions as $ext) {
                    if (dmString::endsWith($file->href, '.'.$ext, false)) {
                        $embed = true;
                        break;
                    }
                }
                if (dmConfig::get('mail_template_embed_local_files_as_attachments', true) && $embed) {
                    try {
                        $file->href = $message->embed(Swift_EmbeddedFile::fromPath(dmOs::join(sfConfig::get('sf_web_dir'), $mediaRecord->getWebPath())));
                    } catch (Exception $e) {
                        $this->logError($templateName, 'local', 'file', $file->href);
                        $file->href = '';
                    }
                } else {
                    if (file_exists(dmOs::join(sfConfig::get('sf_web_dir'), $link = $mediaRecord->getWebPath()))) {
                        $file->href = $this->basePath . '/' . $mediaRecord->getWebPath();
                    } else {
                        $this->logError($templateName, 'local', 'file', $file->href);
                        $file->href = '';
                    }
                }

                return $file;
            }
        }

        if (dmString::startsWith($file->href, sfConfig::get('sf_root_dir')) || dmString::startsWith($file->href, $this->basePath)) {
            return $this->updateLocalFilePath($file, $message, $templateName);
        } else {
            return $this->updateRemoteFilePath($file, $message, $templateName);
        }
    }

    protected function updateLocalFilePath($file, Swift_Message $message, $templateName)
    {

         // TODO
        return $file;

    }

    protected function updateRemoteFilePath($file, Swift_Message $message, $templateName)
    {
        // TODO
        return $file;
    }

    protected function logError($template, $source, $type, $url)
    {
        $this->serviceContainer->getService('event_log')->log(array(
            'server'  => $_SERVER,
            'action'  => 'error',
            'type'    => 'exception',
            'subject' => sprintf(
                'Mail template <strong>%s<strong> is using <em>%s %s</em> on path </strong>%s<strong> that does not exist.',
                $template,
                $source,
                $type,
                $url
            )
        ));
    }

    public function clearCache()
    {
        if ($this->cacheCleared) return $this;
        else $this->cacheCleared = true;
        $fileSystem = $this->serviceContainer->getService('filesystem');
        $errors = false;
        try {
            $fileSystem->deleteDirContent(dmOs::join(sfConfig::get('sf_cache_dir'), 'mail_templates'));
        } catch (Exception $e) {
            $errors = true;
            $this->serviceContainer->getService('event_log')->log(array(
                'server'  => $_SERVER,
                'action'  => 'error',
                'type'    => 'exception',
                'subject' => 'Mail template cache could not be cleared on ' . dmOs::join(sfConfig::get('sf_cache_dir'), 'mail_templates')
            ));
        }
        try {
            $fileSystem->deleteDirContent(dmOs::join(sfConfig::get('sf_web_dir'), 'cache/mail_templates'));
        } catch (Exception $e) {
            $errors = true;
            $this->serviceContainer->getService('event_log')->log(array(
                'server'  => $_SERVER,
                'action'  => 'error',
                'type'    => 'exception',
                'subject' => 'Mail template cache could not be cleared on ' . dmOs::join(sfConfig::get('sf_web_dir'), 'cache/mail_templates')
            ));
        }
        if (!$errors) {
            $this->serviceContainer->getService('event_log')->log(array(
                'server'  => $_SERVER,
                'action'  => 'clear',
                'type'    => 'cache',
                'subject' => 'Mail templates cache cleared'
            ));
        }
        return $this;
    }
}