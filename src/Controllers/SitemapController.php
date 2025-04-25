<?php 
    
namespace Msx\Portal\Controllers;

use Msx\Portal\Helpers\RequestSanitizerHelper;
use Msx\Portal\Models\Sitemap;

class SitemapController {

    public function sitemap($request) {
        $request = RequestSanitizerHelper::sanitize($request);
        $request['tipo'] = 'sitemap';
        $sitemap = new Sitemap($request);
        return $sitemap->getMaterias()->getXML();
    }

}   