<?php

abstract class dmBaseRichEditorActions extends dmBaseActions {

    public function executeGetDmMediaMetadata(dmWebRequest $request)
    {
        if (!($mediaId = $request->getParameter('media_id'))) {
            return $this->forward404();
        }

        if (dmString::startsWith($mediaId, 'page:')) {

            if (!$this->isAuthorized('page')) return $this->forwardSecure();

            $page = dmDb::table('DmPage')->findOneBy('id', str_replace('page:', '', $mediaId));
            return $this->renderJson(array(
                'src' => $this->getHelper()->link($page)->getHref(),
                'type' => 'page',
                'id' => trim(str_replace('page:', '', $mediaId)),
                'title' => $page->get('title')
            ));
        } elseif (dmString::startsWith($mediaId, 'media:')){

            if (!$this->isAuthorized('media')) return $this->forwardSecure();

            $id = trim(str_replace('media:', '', $mediaId));
            $media = dmDb::table('DmMedia')->findOneBy('id', $id);
            $size = explode('x', $media->get('dimensions'));
            return $this->renderJson(array(
                'id' => $media->get('id'),
                'src' => '/'.$media->getWebPath(),
                'legend' => $media->get('legend'),
                'mime' => $media->get('mime'),
                'size' => $media->get('size'),
                'width' => (isset($size[0])) ? $size[0]: '',
                'height' => (isset($size[1])) ? $size[1]: '',
                'type' => 'media'
            ));
        } else {
            return $this->forward404();
        }
    }

    public function executeGetProcessedImageURL(dmWebRequest $request)
    {
        if (!$this->isAuthorized('media')) return $this->forwardSecure();

        if (!($mediaId = $request->getParameter('media_id'))) {
            return $this->forward404();
        }

        $media = dmDb::table('DmMedia')->findOneBy('id', $mediaId);
        $src = '/'.$media->getWebPath();

        $width = ($request->getParameter('width')) ? intval($request->getParameter('width')) : null;
        $height = ($request->getParameter('height')) ? intval($request->getParameter('height')) : null;

        if ($width || $height) {
            $src = $this->getHelper()->media($media)
                ->size($width, $height)
                ->method(($request->getParameter('method')) ? $request->getParameter('method') : dmConfig::get('image_resize_method'))
                ->getSrc();
        }
        return $this->renderJson(array('src' => $src));
    }

    protected abstract function isAuthorized($type);

}