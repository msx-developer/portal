<?php 

namespace Msx\Portal\Helpers;

class MateriaHelper {

    public static function processContent($ds_matia, $modelo = null) {

        $ds_matia = self::processTemplateTag($ds_matia, $modelo);

        preg_match_all("/<tinymce(.*?)\"\s?>/is", $ds_matia, $matchOpen, PREG_SET_ORDER);
        preg_match_all("/<\/tinymce>/is", $ds_matia, $matchClose, PREG_SET_ORDER);

        for ($i = 0; $i < count($matchOpen); $i++) {
            $replace = $matchOpen[$i][0];
            $ds_matia = str_replace($replace, "", $ds_matia, $count);
        }
        for ($i = 0; $i < count($matchClose); $i++) {
            $replace = $matchClose[$i][0];
            $ds_matia = str_replace($replace, "", $ds_matia);
        }
        
        $ds_matia = self::stringEntitiesFromScapeEditor($ds_matia);
        $ds_matia = preg_replace("(<p>)", '<p class="texto">', $ds_matia);
        
        return $ds_matia;

    }

    public static function processTemplateTag($ds_matia, $modelo = null) {

        if($modelo == null)
            $modelo == 'desktop';

        $smarty = new \Smarty\Smarty;
        $matia = new \Msx\Portal\Models\Matia;

        $tetags = $matia->getTetags();

        preg_match_all("/<tinymce(.*?)\"\s?>/is", $ds_matia, $matchOpen, PREG_SET_ORDER);
        preg_match_all("/<\/tinymce>/is", $ds_matia, $matchClose, PREG_SET_ORDER);

        foreach ($matchOpen as $keyOp => $valueOp) {
            $json = trim(str_replace(array('class="clickTinyMCE" title="{','}"'), array('{','}'),$valueOp[1]));
            $tetagsContent[$keyOp] =  json_decode(json_encode($json, true), true);

            if ($tetagsContent[$keyOp] === null && count($tetagsContent[$keyOp]) == 0) {
                $attrs = explode(',', $json);
                if(count($attrs) > 0) {
                    foreach ($attrs as $attr) {
                        $att = explode("':'", $attr);
                        $kAttr = trim(str_replace("'", "",$att[0]));

                        $att01 = str_replace("'", "",$att[1]);
                        if( function_exists('mb_convert_encoding') ) {
                            $att01 = mb_convert_encoding( $att01, "UTF-8", mb_detect_encoding($att01));
                        } else {
                            $att01 = utf8_encode(str_replace("'", "", $att01));
                        }
                    }
                }
            }

            $posIni = strpos($ds_matia, $valueOp[0]);
            $posFim = strpos($ds_matia, "</tinymce>", $posIni);
            
            $tagContent = $tetagsContent[$keyOp];

            if(isset($tagContent['cd_tetag']) == false || $tagContent['cd_tetag'] == "") {                
                $ds_matia = substr_replace($ds_matia, "", $posIni, $posFim - $posIni);
            } else {
                $tag = $tetags[intval($tagContent['cd_tetag'])];
                
                switch ($modelo) {
                    case 'amp':
                        $templateTag = $tag['ds_tetag_tag_amp'];
                        break;
                    case 'mobile':
                        $templateTag = $tag['ds_tetag_tag_mobile'];
                        break;
                    case 'ia':
                        $templateTag = $tag['ds_tetag_tag_ia'];
                        break;
                    default:
                        $templateTag = $tag['ds_tetag_tag_html'];
                        break;
                }
    
                if($tag['id_tetag_templ_tipo'] == '1'){ //smarty              
                    if($tagContent['id_tetag_galer'] == '1'){
                         if(count($tagContent['midias']) > 0) {  
                            foreach ( $tagContent['midias'] as $keyMidias => $valueMidias) {
                                $tagContent['midias'][$keyMidias] = $matia->getMidiasByMidias($valueMidias);
                            }
                        }
                    }
                    if($tagContent['matias'] > 0) {
                        foreach ( $tagContent['matias'] as $keymatias => $valuematias) {
                            $mapMatia = $matia->getMatias(['cd_matia' => [$valuematias]]);
                            $mapSite = $matia->getSite($valuematias);
                            if($mapMatia['cd_midia'] != "")
                                $mapMidia = $matia->getMidiasByMidias($mapMatia['cd_midia']);
                            $tagContent['matias'][$keymatias] = $mapMatia;
                            $tagContent['matias'][$keymatias]['ds_poral_url'] =  $mapSite['ds_poral_url'];
                            $tagContent['matias'][$keymatias]['ds_site'] =  $mapSite['ds_site'];
                            $tagContent['matias'][$keymatias]['ds_midia_link'] = ( $mapMatia['cd_midia'] != "" && isset($mapMidia) ) ? $mapMidia['ds_midia_link'] : '';
                        }
                    }
                    $smarty->clearCache("string:" . $templateTag);
                    $smarty->caching = false;
                    $smarty->assign("item", $tagContent);
                    $smarty->assign("conteudos", $tagContent["matias"]);
                    $templateTag = $smarty->fetch("string:" . $templateTag);
                }else{
                    foreach($tagContent as $tagContentKey => $tagContentValue){
                        $templateTag = str_replace("%{$tagContentKey}%", $tagContentValue, $templateTag);
                    }
                }
                $ds_matia = str_replace(substr($ds_matia, $posIni, intval($posFim) - intval($posIni)), $templateTag, $ds_matia);                
            }            
        }
        return $ds_matia;
    }

    public static function shortLink($str, $tag = "link_conteudo") {
        if($tag == "link_conteudo")
            $str = str_replace("/_conteudo", "", $str);
        
        if($tag == "url_capa")
            $str = str_replace(array("index.php?id=", "/index.php"), "", $str);

        return $str;
    }

    public static function removeStringBetweenTag($tag, $text) {
        $inicio = strpos($text,"<".$tag);
        if($inicio!== false){
            $fim = strpos($text,"</".$tag.">");
            if($fim !== false && $fim > $inicio){
                $fim = strlen($tag) + 3 + $fim;
                $text = substr($text, 0, $inicio) . substr($text, $fim , strlen($text));
                self::removeStringBetweenTag($tag, $text);
            }  
        }
        
        return $text;
    }
        
    public static function stringScapeEditorFromNormalString($string){
        $string = str_replace ('\'', 'ENTITY_apos_ENTITY', $string);
        $string = str_replace ('"', 'ENTITY_quot_ENTITY', $string);
        $string = str_replace ('#', 'ENTITY_sharp_ENTITY', $string);
        $string = preg_replace('/(&[^a-zA-Z]|&$|&amp;)/i', 'ENTITY_amp_ENTITY', $string); 
        return $string;
    }
    
    public static function stringEntitiesFromScapeEditor($string){
        $string = str_replace ('ENTITY_apos_ENTITY', '&apos;', $string);
        $string = str_replace ('ENTITY_quot_ENTITY', '&quot;', $string);
        $string = str_replace ('ENTITY_sharp_ENTITY', '#', $string);
        $string = str_replace ('ENTITY_amp_ENTITY', '&amp;', $string);
        return $string;
    }
    
    public static function stringNormalStringFromScapeEditor($string){
        $string = str_replace ('ENTITY_apos_ENTITY', '\'', $string);
        $string = str_replace ('ENTITY_quot_ENTITY', '"', $string);
        return $string;
    }
}