<?php

/**
 * Manages the page cache in order to speed up page loads
 * It is Symfony and Diem Extended independed
 * @author TheCelavi
 */
class dmPageCacheManager {
    
    protected $static = false;
    protected $secure = array();
    protected $user = null;
    
    /**
     * If subject of caching is static
     * @return boolean
     */
    public function isStatic()
    {
        return $this->static;
    }
    
    public function isSecure()
    {
        return (bool) count($this->secure);
    }
    
    public function isUserSpecific()
    {
        return !is_null($this->user);
    }
    
    public function isSecureUserSpecific()
    {
        return $this->isSecure() && $this->isUserSpecific();
    }
    
    
}

