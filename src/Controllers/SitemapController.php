<?php 
    
namespace Msx\Portal\Controllers;

use Msx\Portal\Helpers\RequestSanitizerHelper;
use Msx\Portal\Models\Sitemap;

class SitemapController {

    public function sitemap($request) {
        try {
            $request = RequestSanitizerHelper::sanitize($request);
            $request['tipo'] = 'sitemap';

            if($request['type'] == 'index' && isset($request['page']) && is_numeric($request['page']) & $request['page'] > 0) {
                $request['type'] = 'indexmap';
            } 

            $sitemap = new Sitemap($request);
            return $sitemap->getMaterias()->getXML();           
        } catch (\Exception $e) {
            return "<root />";
        }        
    }
}   