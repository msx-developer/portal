<?php

namespace Msx\Portal\Controllers;

use Msx\Portal\Helpers\RequestSanitizerHelper;
use Msx\Portal\Models\Busca;
class SearchController  
{
    private $busca;
    public function __construct() {
        $this->busca = new Busca();
    }
     public function busca($request) {
        return $this->matia($request, 'busca');
    }

    public function tags($request) {
        return $this->matia($request, 'tags');
    }

    public function autor($request) {
        return $this->matia($request, 'autor');        
    }

    public function related($request) {
        return $this->matia($request, 'related');
    }

    public function midia($request) {
        $request = RequestSanitizerHelper::sanitize($request);
        $request['tipo'] = 'midia';
        $result = $this->busca->midia($request);
        return $this->mapFields($result, $request['tipo']);
    }

    private function matia($request, $tipo) {
        $request = RequestSanitizerHelper::sanitize($request);
        $request['tipo'] = $tipo;
        $result = $this->busca->matia($request);
        return $this->mapFields($result, $tipo);   
    }

    private function mapFields($result, $tipo = null) {
        $map = [];
        if(is_array($result) && count($result) > 0) { 
            if(isset($result['hits']) && is_array($result['hits']) && count($result['hits']) > 0){
                foreach($result['hits'] as $k => $value) {         
                    $v = $value['_source'];
                    $map[$k] = $v;
    
                    switch ($tipo) {
                        case 'midia':
                            if(isset($v['cd_midia_pai']) && $v['cd_midia_pai'] != null) {
                                $map[$k]['midmas']["{$v['cd_midia_w']}x{$v['cd_midia_h']}"] = $v;
                                unset($map[$k]);
                            }
                            break;
                        default:
                            $autores = (isset($v['ds_autor_slug']) && $v['ds_autor_slug'] != null) ? $v['ds_autor_slug'] : $v['nm_notia_autor'];
                            $map[$k]['cod'] = $v['cd_matia'];
                            $map[$k]['url'] = $v['ds_matia_link'];
                            $map[$k]['title'] = $v['ds_matia_titlo'];
                            $map[$k]['date'] = $v['dt_matia_publi'];
                            $map[$k]['channel'] = $v['ds_site'];
                            $map[$k]['subject'] = $v['ds_matia_assun'];
                            $map[$k]['description'] = $v['ds_matia_chape'];
                            $map[$k]['views'] = $v['qt_matia_visua'];
                            $map[$k]['tags'] = $v['ds_matia_palvr'];
                            $map[$k]['publish'] = $v['dt_matia_publi'];
                            $map[$k]['midiaId'] = $v['cd_midia'];
                            $map[$k]['img'] = $v['ds_midia_link'];
                            $map[$k]['thumb'] = $v['nm_midia_inter_thumb1'];
                            $map[$k]['autors'] = $this->busca->autor($autores);
                            break;
                    }
                }
            }
        }
        switch ($tipo) {
            case 'autor':
                $result['info'] = $this->busca->autor($result['q']);
                break;            
            default:
                break;
        }
        $result['data'] = $map;
        unset($result['hits']);

		return $result;
	}
}