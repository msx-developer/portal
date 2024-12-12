<?php 

namespace Msx\Portal\Helpers;

class MateriaHelper {

    /**
     * Processes the content of a given string by removing certain HTML tags,
     * applying a template tag processing, and adjusting paragraph tags with a class.
     *
     * @param string $ds_matia The content to be processed, potentially containing HTML tags.
     * @param mixed|null $modelo An optional model used for template tag processing.
     * @return string The processed content with specific tags removed or adjusted.
     */
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

    /**
     * Processa o conteúdo da matéria para template tag.
     *
     * Processa o conteúdo da matéria para template tag, substituindo as tags
     * <tinymce> por seu respectivo conteúdo.
     *
     * @param string $ds_matia conteúdo da matéria.
     * @param string $modelo   modelo de template tag, desktop, mobile, amp, ia.
     *
     * @return string
     */
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

    /**
     * Shortens a URL by removing specific path segments or query strings
     * based on the provided tag.
     *
     * @param string $str The URL string to be shortened.
     * @param string $tag Optional. Determines the type of shortening to be applied.
     *                    If "link_conteudo", removes "/_conteudo" from the URL.
     *                    If "url_capa", removes "index.php?id=" and "/index.php".
     *                    Defaults to "link_conteudo".
     * @return string The shortened URL.
     */
    public static function shortLink($str, $tag = "link_conteudo") {
        if($tag == "link_conteudo")
            $str = str_replace("/_conteudo", "", $str);
        
        if($tag == "url_capa")
            $str = str_replace(array("index.php?id=", "/index.php"), "", $str);

        return $str;
    }

    /**
     * Recursively removes all occurrences of a specified HTML/XML tag and its content from a given text.
     *
     * @param string $tag  The name of the tag to remove.
     * @param string $text The text from which the tag and its content should be removed.
     * @return string The modified text with the specified tag and its content removed.
     */
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
        
    /**
     * Converts a normal string into a scape Editor formatted string.
     *
     * This function replaces certain characters in a normal string with
     * their corresponding ENTITY placeholders. Specifically, it replaces:
     * - Single quotes (') with ENTITY_apos_ENTITY
     * - Double quotes (") with ENTITY_quot_ENTITY
     * - Hash symbols (#) with ENTITY_sharp_ENTITY
     * - Ampersands (&) with ENTITY_amp_ENTITY
     *
     * @param string $string The normal string to be converted.
     * @return string The converted string with ENTITY placeholders.
     */
    public static function stringScapeEditorFromNormalString($string){
        $string = str_replace ('\'', 'ENTITY_apos_ENTITY', $string);
        $string = str_replace ('"', 'ENTITY_quot_ENTITY', $string);
        $string = str_replace ('#', 'ENTITY_sharp_ENTITY', $string);
        $string = preg_replace('/(&[^a-zA-Z]|&$|&amp;)/i', 'ENTITY_amp_ENTITY', $string); 
        return $string;
    }
    
    /**
     * Converte uma string em scape Editor para string com entidades html
     *
     * Converte uma string em scape Editor para string com entidades html<br>
     *
     * Exemplo:<br>
     *  <?= MateriaHelper::stringEntitiesFromScapeEditor("string em scape Editor"); ?><br>
     *
     * @param String $string string em scape Editor
     * @return String string com entidades html
     *
     */
    public static function stringEntitiesFromScapeEditor($string){
        $string = str_replace ('ENTITY_apos_ENTITY', '&apos;', $string);
        $string = str_replace ('ENTITY_quot_ENTITY', '&quot;', $string);
        $string = str_replace ('ENTITY_sharp_ENTITY', '#', $string);
        $string = str_replace ('ENTITY_amp_ENTITY', '&amp;', $string);
        return $string;
    }
    
    /**
     * Converte uma string em scape Editor para string normal
     *
     * Converte uma string em scape Editor para string normal<br>
     *
     * Exemplo:<br>
     *  <?= MateriaHelper::stringNormalStringFromScapeEditor("string em scape Editor"); ?><br>
     *
     * @param String $string string em scape Editor
     * @return String string normal
     *
     */
    public static function stringNormalStringFromScapeEditor($string){
        $string = str_replace ('ENTITY_apos_ENTITY', '\'', $string);
        $string = str_replace ('ENTITY_quot_ENTITY', '"', $string);
        return $string;
    }

    /**
     * Converte uma data em portugues (dd/mm/yyyy hh:ii:ss) para ingles (yyyy-mm-dd hh:ii:ss)
     *
     * Converte uma data em portugues (dd/mm/yyyy hh:ii:ss) para ingles (yyyy-mm-dd hh:ii:ss)<br>
     *
     * Exemplo:<br>
     *  <?= MateriaHelper::DataBrToEn("10/12/2008"); ?><br>
     *
     * @param String $pStrdata Data no formato Portugues
     * @return String Data no formato Americano
     *
     */
    public static function DataBrToEn($pStrdata) {
        if (strpos($pStrdata, "/") !== false) {
            $lStrdata = explode(" ", $pStrdata);
            $lStrdia = explode("/", $lStrdata[0]);
            $lStrhora = $lStrdata[1];

            // acrescentado por Jot em 22/12/2010 para validar se a data informada � valida
            // caso a data não seja válida a função retorna falso
            //if (!checkdate($lStrdia[1], $lStrdia[0], $lStrdata[2]))
            //   return false;

            if ($lStrhora == "00:00:00")
                $lStrhora = "";
            return trim($lStrdia[2] . "-" . $lStrdia[1] . "-" . $lStrdia[0] . " " . $lStrhora);
        }
        else
            return trim($pStrdata);
    }

    /**
     * Converte uma data em ingles (yyyy-mm-dd hh:ii:ss) para portugues (dd/mm/yyyy hh:ii:ss)
     *
     * Converte uma data em ingles (yyyy-mm-dd hh:ii:ss) para portugues (dd/mm/yyyy hh:ii:ss)<br>
     *
     * Exemplo:<br>
     *  <?= MateriaHelper::DataEnToBr("2008-12-10 00:00:00"); ?><br>
     *
     * @param String $pStrdata Data no formato Ingles
     * @return String Data no formato Portugues
     *
     */
    public static function DataEnToBr($campo) {
        $lStrRetValue = $campo;
        if (strpos($campo, "-") > 0) {
            $lStrRetValue = substr($campo, 8, 2) . "/" . substr($campo, 5, 2) . "/" . substr($campo, 0, 4) . substr($campo, 10);
            $lStrRetValue = str_replace("00:00:00", "", $lStrRetValue);
        }
        return trim($lStrRetValue);
    }
}