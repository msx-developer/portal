<?php

namespace Msx\Portal\Controllers;

// use Msx\Portal\Database\Connection;
use Msx\Portal\Helpers\RequestSanitizerHelper;
use Msx\Portal\Models\Busca;
class SearchController  
{
    private $busca;
    // private $connection;
    public function __construct() {
        $this->busca = new Busca();
        // $this->connection = Connection::getInstance();
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
                            $filhas = $this->busca->midiasFilhas($v['cd_midia']);
                            if (is_array($filhas["hits"]) && count($filhas["hits"]) > 0) {
                                foreach ($filhas["hits"] as $kF => $filha) {
                                    if (isset($filha['_source'])) {
                                        $j = $filha['_source'];
                                        $map[$k]['crops']["{$j['cd_midia_w']}x{$j['cd_midia_h']}"] = $j;                                   
                                    }
                                }
                            } else
								$map[$k]['crops'] = [];
                            break;
                        default:
                            $autores = (isset($v['ds_autor_slug']) && $v['ds_autor_slug'] != null) ? $v['ds_autor_slug'] : $v['nm_notia_autor'];
							
                            $map[$k]['cod'] 		= isset($v['cd_matia']) 			 ? $v['cd_matia'] 				: "";
                            $map[$k]['url'] 		= isset($v['ds_matia_link']) 		 ? $v['ds_matia_link'] 			: "";
                            $map[$k]['title'] 		= isset($v['ds_matia_titlo']) 		 ? $v['ds_matia_titlo'] 		: "";
                            $map[$k]['date'] 		= isset($v['dt_matia_publi']) 		 ? $v['dt_matia_publi'] 		: "";
                            $map[$k]['channel'] 	= isset($v['ds_site']) 				 ? $v['ds_site'] 				: "";
                            $map[$k]['subject'] 	= isset($v['ds_matia_assun']) 		 ? $v['ds_matia_assun'] 		: "";
                            $map[$k]['description'] = isset($v['ds_matia_chape']) 		 ? $v['ds_matia_chape'] 		: "";
                            $map[$k]['views'] 		= isset($v['qt_matia_visua']) 		 ? $v['qt_matia_visua'] 		: "";
                            $map[$k]['tags'] 		= isset($v['ds_matia_palvr']) 		 ? $v['ds_matia_palvr'] 		: "";
                            $map[$k]['publish'] 	= isset($v['dt_matia_publi']) 		 ? $v['dt_matia_publi'] 		: "";
                            $map[$k]['midiaId'] 	= isset($v['cd_midia']) 			 ? $v['cd_midia'] 				: "";
                            $map[$k]['img'] 		= isset($v['ds_midia_link']) 		 ? $v['ds_midia_link'] 			: "";
                            $map[$k]['thumb'] 		= isset($v['nm_midia_inter_thumb1']) ? $v['nm_midia_inter_thumb1'] 	: "";

                            $map[$k]['autors'] = $this->busca->autor($autores);
							$map[$k]['midias'] = [];
							// foreach ($midmas as $midma) {
								// $midia = $this->midia(["cd_midia" => $midma["cd_midia"]])['data'][0];
								// $map[$k]['midias'][$midia["id"]] = $midia; 
							// }

							$midia = $this->midia(["cd_midia" => $v['cd_midia']]);
                            if(isset($midia['data']) && is_array($midia['data']) && count($midia['data']) > 0)
                                $midia = $midia['data'][0];
                            else
                                $midia = null;
							$map[$k]['midias'][$midia["id"]] = $midia; 
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