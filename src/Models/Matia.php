<?php

namespace Msx\Portal\Models;

use Msx\Portal\Database\Connection;
use Msx\Portal\Helpers\MateriaHelper;
class Matia {
    private $connection;
    const DEFAULT_RELATIONS = [
        'publis', 
        'midmas',     
        'retmts',     
        'site',       
        'citnos',	
        'autors',	
        'tagmts',	
        'marels',	
    ];

    private $with = [];

    /**
     * Construct a new instance of the Matia controller
     *
     * Gets an instance of the database connection
     *
     * @return void
     */
    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    public function setWith($with) {
        $this->with = $with;
        return $this;
    }

    public function getWith() {
        return $this->with;
    }

    public function find($id) {
        $sql = 'SELECT * FROM matia WHERE matia.cd_matia = ?';
        return (array) $this->connection->fetchAll($sql, [$id]);
    }

    public function getMatias($params = []) {
        
        $ids     = $params['cd_matia']; 
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
       
        $sql = "SELECT * 
        FROM matia 
        WHERE matia.cd_matia IN ({$inQuery})";

        $sql = $this->queryMatia($sql, $params);

        $map = (array) $this->connection->fetchAll($sql, $ids);

        if(isset($map) && count($map) > 0 && count($this->with) > 0){
            foreach($map as $key => $value){
                foreach($this->with as $with){
                    if(in_array($with, self::DEFAULT_RELATIONS)){
                        $func = 'get'.ucfirst($with);
                        $map[$key][$with] = $this->$func($value['cd_matia']);
                    }
                }
                if(isset($params['cd_sesit']) == false || $params['cd_sesit'] == null)
                    $map[$key]['ds_matia'] = MateriaHelper::processContent($value['ds_matia']);
                else
                    unset($map[$key]['ds_matia']);
            }    
        }

        return $map;
    }

    public function getPublis($cd_matia) {
        $sql = 'SELECT * FROM publi WHERE publi.cd_matia = ?';
        return (array) $this->connection->fetchAll($sql, [$cd_matia]);
    }

    public function getSite($cd_matia) {
        $sql = "SELECT poral.*, site.*
            , REPLACE(REPLACE(REPLACE(site.cd_site_url_capa, 'index.php?id=', ''), '/index.php', ''), 'index.php', '') as cd_site_url_capa_short
            , REPLACE(REPLACE(REPLACE(site.cd_site_url_capa, 'index.php?id=', ''), '/index.php', ''), 'index.php', '') as cd_site_url_capa
            , site.cd_site_url_capa AS cd_site_url_capa_full
        FROM matia
                INNER JOIN site ON (matia.cd_site = site.cd_site)
                INNER JOIN poral ON (site.cd_poral = poral.cd_poral)
        WHERE matia.cd_matia = ? ";
         return (array) $this->connection->fetchAll($sql, [$cd_matia]);
    }

    public function getCitnos($cd_matia) {
        $sql = 'SELECT * FROM citno WHERE citno.cd_matia = ?';
        return (array) $this->connection->fetchAll($sql, [$cd_matia]);
    }

    public function getMarels($cd_matia) {
        $sql = 'SELECT * FROM marel INNER JOIN matia ON (marel.cd_matia_rel = matia.cd_matia) WHERE marel.cd_matia = ?';
        return (array) $this->connection->fetchAll($sql, [$cd_matia]);
    }

    public function getAutors($cd_matia) {
        $sql = "SELECT 
            (SELECT ds_midia_link FROM midia WHERE midia.cd_midia = autor.cd_midia) AS ds_midia_link, 
            autor.*
        FROM autor 
            INNER JOIN autmt ON (autor.cd_autor = autmt.cd_autor)
        WHERE autmt.cd_matia = ?
        ORDER BY autor.cd_autor ";
        return (array) $this->connection->fetchAll($sql, [$cd_matia]);
    }

    public function getTagmts($cd_matia) {
        $sql = "SELECT *
        FROM tagmt
            INNER JOIN tags ON (tagmt.cd_tags = tags.cd_tags)
        WHERE tagmt.cd_matia = ?
        ORDER BY tags.ds_tags";
        return (array) $this->connection->fetchAll($sql, [$cd_matia]);
    }

    public function getRetmts($cd_matia) {

        $conds = "";
        if(!isset($_REQUEST['preview']) || $_REQUEST['preview'] != '1')
            $conds = " AND matia.cd_matia_statu IN (2) ";

        $sql = "SELECT 
            matia.*
            , REPLACE(IFNULL(matia.ds_matia_link, CONCAT_WS('',poral.ds_poral_url,matia.ds_matia_path)), '/_conteudo', '') AS ds_matia_link
        FROM matia
            INNER JOIN site ON (matia.cd_site = site.cd_site)
            INNER JOIN poral ON (site.cd_poral = poral.cd_poral)
            LEFT JOIN tetag ON (matia.cd_tetag = tetag.cd_tetag)
        WHERE cd_matia_pai = ?
            $conds
        ORDER BY id_matia_pauta_ordem";
        return (array) $this->connection->fetchAll($sql, [$cd_matia]);
    }

    private function queryMatia($sql, $params = []) {
        $select = $from = $where = $order = $limit = "";

        $id_matia_tipo = isset($params['id_matia_tipo']) ? $params['id_matia_tipo'] : 1;
        $id_sesit_order = isset($params['id_sesit_order']) ? $params['id_sesit_order'] : 9;
        $qt_sesit_repag = isset($params['qt_sesit_repag']) ? $params['qt_sesit_repag'] : 0;
        $qt_sesit_matia = isset($params['qt_sesit_matia']) ? $params['qt_sesit_matia'] : 30;

        switch ($id_matia_tipo) {
            case 1:
            case 6:
            case 7:
            case 12:
				if(!preg_match('/INNER JOIN notia ON/',$sql)){
					$select = "notia.* , matia.* ";
					$from = " INNER JOIN notia ON (notia.cd_matia = matia.cd_matia) ";
				}
               break; 
            case 4://agenda
                $from = " INNER JOIN agend ON (agend.cd_matia = matia.cd_matia)     
                          LEFT JOIN tpeve ON (agend.cd_tpeve = tpeve.cd_tpeve)";
                $where .=  "  AND now() >= agend.dt_agend_ini  ";                      
                break;
            case 5: //filme
                $from = " INNER JOIN filme ON (filme.cd_matia = matia.cd_matia)
                          INNER JOIN gener ON (gener.cd_gener = filme.cd_gener) ";
                break;            
            case 7: //Guia
                $from  = " INNER JOIN guia ON (matia.cd_matia = guia.cd_matia) ";
                break;
            case 8: //Concurso
                $from = " INNER JOIN concu ON (matia.cd_matia = concu.cd_matia) ";
                break;
            case 11: //Classificados
                $from = "   INNER JOIN ancio ON matia.cd_matia = ancio.cd_matia
                            INNER JOIN retca ON ancio.cd_retca = retca.cd_retca";
                break;  
            case 15: // Playlist 
                $from = "  INNER JOIN  plays ON (matia.cd_matia = plays.cd_matia)
                           LEFT JOIN  assjo ON (plays.cd_assjo = assjo.cd_assjo) ";
                break;
            case 16: // Promoção
                $from = "  INNER JOIN  promo ON (matia.cd_matia = promo.cd_matia) ";
                break;
            case 17: //Anuncio
                $from = " INNER JOIN ancio ON (matia.cd_matia = ancio.cd_matia) ";
                break;
            case 2: 
                $from = " INNER JOIN enqte ON (matia.cd_matia = enqte.cd_matia) ";
                break;
        }

        if(( isset($params['cd_matia']) == false || $params['cd_matia'] == "") && ( isset($params['preview']) == false || $params['preview'] != 1)){
            $where .= " AND matia.cd_matia_statu in (2) ";
        }

        if( (isset($params['cd_matia']) == false || $params['cd_matia'] == "") && preg_match('/INNER JOIN publi ON/',$sql)){
            $where .= " AND now() >= publi.dt_publi_ini 
            AND (now() <= publi.dt_publi_fim OR publi.dt_publi_fim is null) 
            AND 
                (((
                            sesit.dt_sesit_publi is null OR 
                            matia.dt_matia_incl > date_sub(sesit.dt_sesit_publi, interval ifnull(sesit.qt_sesit_publi,1) day)
                        ) 
                        AND sesit.id_sesit_otimi = 1
                    ) 
                    OR sesit.id_sesit_otimi is null
                ) ";
        }

        switch ($id_sesit_order) {
            case '':
            case 1:
                $order = " ORDER BY publi.dt_publi_ini DESC ";
                break;
            case 2:
                $order = " ORDER BY publi.dt_publi_ini ASC ";
                break;
            case 3:
                $order =" ORDER BY matia.ds_matia_titlo ASC ";
                break;
            case 4:
                $order = " ORDER BY matia.dt_matia_incl DESC ";
                break;
            case 5:
                $order = " ORDER BY matia.dt_matia_incl ASC ";
                break;
            case 6:
                $order = " ORDER BY RAND() ";                
                break;
           case 7: 
               $order = " ORDER BY agend.dt_agend_ini ASC ";
                break;
           case 8:
               $order = " ORDER BY agend.dt_agend_ini DESC ";
               break;
           case 9:
               $order = " ORDER BY matia.dt_matia_publi DESC ";
               break;
           case 10:
               $order = " ORDER BY matia.dt_matia_publi ASC ";
               break;
            case 11:
               $order = " ORDER BY ancio.dt_ancio_incl DESC, retca.ds_retca_class_arvor ";
               break;
            default: 
               $order = " ORDER BY publi.dt_publi_ini DESC ";
               break;
        }

        if ($qt_sesit_matia == 0 && $qt_sesit_repag == 0)
            $qt_sesit_repag = 15;

        if($qt_sesit_matia != "" && $qt_sesit_matia > 0)
            $limit = " LIMIT " . $qt_sesit_matia;
        else
            if($qt_sesit_repag != "" && $qt_sesit_repag > 0)
                $limit = " LIMIT " . (($params['p'] == "" || $params['p'] == 0 ? 1 : $params['p']) - 1) * $qt_sesit_repag . ", " . $qt_sesit_repag;
        

        if($select != "")
            $sql = str_replace("SELECT * ", "SELECT {$select}", $sql);

        if($from != "")
            $sql = str_replace("FROM matia ", "FROM matia {$from}", $sql);

        if($where != "")
            $sql = str_replace(" WHERE matia.cd_matia IN ", "WHERE 1 = 1 {$where} AND matia.cd_matia IN ", $sql);

        if($order != "" && preg_match("/ORDER BY/i", $sql) == 0)    
            $sql .= $order;

        if($limit != "" && preg_match("/LIMIT/i", $sql) == 0)
            $sql .= $limit; 

        return $sql;
    }

    public function getMidmas($cd_matia){
        $map = $mapFilhas = $filhas = [];

        $map = $this->getMidias($cd_matia);
        $midias = array_column($map, "cd_midia");
        $mapFilhas = $this->getMidiasByMidias($midias);
        
        $arr = $map;
		if (count($mapFilhas) > 0) {
			$arr = array();
			foreach ($mapFilhas as $value) {
				$midiaPai = ($value['cd_midia_pai'] == "" ? $value['cd_midia'] : $value['cd_midia_pai']);
				$filhas[$midiaPai]["{$value['cd_midia_w']}x{$value['cd_midia_h']}"] = $value;
                //$arr[$midiaPai]["{$value['cd_midia_w']}x{$value['cd_midia_h']}"] = $value;
			}
			foreach ($map as $value) {
				$midiaPai = ($value['cd_midia_pai'] == "" ? $value['cd_midia'] : $value['cd_midia_pai']);
				$arr[$midiaPai] = $value;
				$arr[$midiaPai]["midias"] = $filhas[$midiaPai];
			}
		}

		return $arr;
    }

    public function getMidias($cd_matia) {
        $sql = "SELECT 
                *,
                midia.ds_midia_link AS  ds_midia_link,
                midia.cd_midia AS  cd_midia,
                midia.cd_midia_pai AS  cd_midia_pai
            FROM midma
                INNER JOIN midia ON (midma.cd_midia = midia.cd_midia)
                INNER JOIN tpmid ON (midia.cd_tpmid = tpmid.cd_tpmid)
                INNER JOIN poral ON (midia.cd_poral = poral.cd_poral)
                LEFT JOIN cremd ON (cremd.cd_cremd = midia.cd_cremd)
            WHERE midma.cd_matia = ? 
            ORDER BY midma.id_midma_princ DESC, midma.id_midma_ordem ";
        return (array) $this->connection->fetchAll($sql, [$cd_matia]);        
    }


    public function getMidiasByMidias($cd_midia_list){

        $ids     = $cd_midia_list; 
        $inQuery = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT 
				*, 
				IFNULL(cd_tammi_w, midia.cd_midia_w) as cd_midia_w, 
				IFNULL(cd_tammi_h, midia.cd_midia_h) as cd_midia_h,
				IF( 
					midia.dt_midia_ref IS NULL, midia.ds_midia_link, CONCAT(midia.ds_midia_link, '?', DATE_FORMAT(midia.dt_midia_ref, '%Y%m%d%H%i%S'))
				) AS ds_midia_link,
				midia.cd_midia AS  cd_midia,
				midia.cd_midia_pai AS  cd_midia_pai
			FROM midia
				INNER JOIN tpmid ON (midia.cd_tpmid = tpmid.cd_tpmid)
				INNER JOIN poral ON (midia.cd_poral = poral.cd_poral)
				LEFT JOIN tammi ON (midia.cd_tammi = tammi.cd_tammi)
				LEFT JOIN cremd ON (cremd.cd_cremd = midia.cd_cremd)
			WHERE midia.cd_midia_pai IN ({$inQuery}) OR midia.cd_midia IN ({$inQuery})"; 
        
        return (array) $this->connection->fetchAll($sql, array_merge($ids, $ids));
    }

    public function getTetags(){
        if(isset($_SESSION['msx']['portal'])) {
            $sql = 'SELECT * FROM tetag WHERE tetag.cd_poral = ?';
            return (array) $this->connection->fetchAll($sql, [$_SESSION['msx']['portal']]);
        }
        return [];        
    }

}