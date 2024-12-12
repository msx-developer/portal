<?php

namespace Msx\Portal\Controllers;

use Msx\Portal\Models\Matia;
use Msx\Portal\Models\Sesit;
use Msx\Portal\Models\Site;
use Msx\Portal\Helpers\GenericMapHelper;

class PortalController 
{
    private $matia;
    private $sesit;
    private $site;

    public function __construct()
    {
        $this->matia = new Matia();
        $this->sesit = new Sesit();
        $this->site = new Site();
    }
    
    public function getMaterias($id) {
        return $this->matia->setWith($this->matia::DEFAULT_RELATIONS)->getMatias(['cd_matia'  => (is_array($id) ? $id : [$id])]);        
    }

    public function getMateriasSesit($cd_sesit) {
        $fivelive = false;
        if(isset($_REQUEST['fivelive']) && $_REQUEST['fivelive'] == '1') {
            $fivelive = true;
        } 
        $publis = $this->sesit->getPublis($cd_sesit);
        $ids = array_column($publis, 'cd_matia');
        $matias = $this->matia->setWith(['midmas','site'])->getMatias(['cd_matia' => $ids, 'cd_sesit' => $cd_sesit]);        
        if(isset($matias) && count($matias) > 0){
            foreach($matias as $key => $value){
                $indice = array_search($value['cd_matia'], array_column($publis, 'cd_matia'));
                $matias[$key] = array_merge($value, $publis[$indice]);

                $matias[$key]['fivelive'] =  $fivelive;
                $matias[$key]['cd_midia'] = isset($publis[$indice]['cd_midia']) ? $publis[$indice]['cd_midia'] : $value['cd_midia'];
                $matias[$key]['ds_matia_titlo'] = isset($publis[$indice]['ds_publi_titlo']) && $publis[$indice]['ds_publi_titlo'] != "" ? $publis[$indice]['ds_publi_titlo'] : $value['ds_matia_titlo'];
                $matias[$key]['ds_matia_assun'] = isset($publis[$indice]['ds_publi_assun']) && $publis[$indice]['ds_publi_assun'] != "" ? $publis[$indice]['ds_publi_assun'] : $value['ds_matia_assun'];
                $matias[$key]['ds_matia_grata'] = isset($publis[$indice]['ds_publi_grata']) && $publis[$indice]['ds_publi_grata'] != "" ? $publis[$indice]['ds_publi_grata'] : $value['ds_matia_grata'];
                $matias[$key]['ds_matia_chape'] = isset($publis[$indice]['ds_publi_chape']) && $publis[$indice]['ds_publi_chape'] != "" ? $publis[$indice]['ds_publi_chape'] : $value['ds_matia_chape'];

                if(isset($publis[$indice]['cd_publi'])) 
                    $matias[$key]['mareps'] = $this->sesit->getMareps($publis[$indice]['cd_publi']);

                $matias[$key]['ds_matia_link'] = isset($matias[$key]['ds_matia_link']) ? $matias[$key]['ds_matia_link'] :  $matias[$key]['site'][0]['ds_poral_url'] . $matias[$key]['ds_matia_path'];
            }
            if($fivelive == true) {
                $matias[] = GenericMapHelper::getMatia(['nm_sesit' => isset($matias[$key]['nm_sesit']) ? $matias[$key]['nm_sesit'] : "Seção", 'cd_publi' => ((int) $cd_sesit*-1)])[0];
            }
        }        
        return $matias;
    }

    public function getSecoes($cd_site) {        
        return $this->sesit->getSesits($cd_site);
    }

    public function getSites($id = null) {
        $fivelive = false;
        if(isset($_REQUEST['fivelive']) && $_REQUEST['fivelive'] == '1') {
            $fivelive = true;
        }
        
        if($id){
            $map = $this->site->find($id);
            if($fivelive == false)
                unset($map['fivelive']);
            return $map;
        }
        return $this->site->getSites();
    }

    public function getSiteByView($view) {
        return $this->site->getSiteView($view);
    }
}