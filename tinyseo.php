<?php
/**
 * @Copyright
 * @package     tinySEO - Small but Powerful SEO tool for Joomla 3.x
 * @author      RN Kushwaha <rn.kushwaha022@gmail.com>
 * @version     1.0.0
 * @copyright   Copyright (C) 2017 RN Kushwaha. All rights reserved.
 * @link        https://github.com/RNKushwaha022/tinySEO
 * 
 * @license     GNU/GPL
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemTinyseo extends JPlugin
{
    function getUrlProtocol(){
        $protocol = 'http';
        if ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || 
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
            ){
                $protocol = 'https';
            }
            return $protocol;
    }

    function onBeforeCompileHead () {
        $mainframe      = JFactory::getApplication();
        if ($mainframe->isSite()){

            $plugin = JPluginHelper::getPlugin('system', 'tinyseo');
            // Check if plugin is enabled
            if ($plugin){
                $pluginParams            = new JRegistry($plugin->params);
                $remove_trailing_slashes = $pluginParams->get('remove_trailing_slashes');
                $request_uri= $_SERVER['REQUEST_URI'];

                 if( $remove_trailing_slashes==1 && strlen($request_uri)>0 && preg_match('/\/$/',$request_uri)){
                    $doc = JFactory::getDocument();
                    $url        = JURI::root();
                    $sch        = parse_url($url, PHP_URL_SCHEME);
                    $server     = parse_url($url, PHP_URL_HOST);
                    $canonical  = htmlspecialchars($request_uri); 
                    $canonicalUrl = $sch.'://'.$server.rtrim($canonical,'/');
                    //remove slashes from canonical tag
                    foreach ( $doc->_links as $key => $array ) {
                        if ( $array['relation'] == 'canonical') {
                            unset($doc->_links[$key]);
                            $doc->_links[$canonicalUrl] = $array;
                        }
                    }
                }
            }

            
        }
    }

    public function onAfterInitialise()
    {
       $app = JFactory::getApplication();
       if ($app->isSite()){
            $protocol = $this->getUrlProtocol();
            
            $plugin = JPluginHelper::getPlugin('system', 'tinyseo');
            // Check if plugin is enabled
            if ($plugin){
                $pluginParams            = new JRegistry($plugin->params);
                $redirect_status_code    = $pluginParams->get('redirect_status_code');
                $map_ip_address          = $pluginParams->get('map_ip_address');
                $remove_trailing_slashes = $pluginParams->get('remove_trailing_slashes');
                $remove_index_file       = $pluginParams->get('remove_index_file');
                $map_to_url              = $pluginParams->get('map_to_url');
                $redirect_to_lowercase   = $pluginParams->get('redirect_to_lowercase');
                $http_host               = $_SERVER['HTTP_HOST'];
                $request_uri             = $_SERVER['REQUEST_URI'];
                $redirect = $mapurl = $redirect_home = $trailpresent = $lowercase = $preg_match = false;
                
                //redirect 162.209.6.223 to abc-test.com
                if(strlen($map_ip_address)>=5 && $http_host==$map_ip_address){
                    $redirect = $mapurl   = true;
                }

                //redirect https://www.example.com/index.php to https://www.example.com
                if($remove_index_file==1 && $request_uri=='/index.php'){
                    $redirect      = $redirect_home = true;
                }

                //redirect https://www.example.com/about/ to https://www.example.com/about
                 if( $remove_trailing_slashes==1 && strlen($request_uri)>1 && preg_match('/\/$/',$request_uri)){
                    $redirect     = $trailpresent = true;
                }
               
                //redirect https://www.example.com/Loan-Process to https://www.example.com/loan-process
                if ($redirect_to_lowercase==1 ){
                  if( preg_match('/\?/',$http_host.$request_uri)) {
                    if(strtolower(strchr($protocol.'://'.$http_host.$request_uri,'?',true)) != strchr($protocol.'://'.$http_host.$request_uri,'?',true)){
                        $redirect  = $lowercase = $preg_match =  true;
                    }
                  }else{
                    if( strtolower($protocol.'://'.$http_host.$request_uri) != $protocol.'://'.$http_host.$request_uri){
                        $redirect  = $lowercase = true;
                    }
                  }
               }

                if($redirect){
                    if($redirect_status_code==1) header("HTTP/1.1 301 Moved Permanently");
                    //if need to use url map
                    if($mapurl  == true) $url = $map_to_url.$request_uri;
                    else  $url = $protocol.'://'.$http_host.$request_uri;

                    //if need to remove index.php
                    if($redirect_home == true) $url = $protocol.'://'.$http_host;

                    //if need to remove trailing slashes
                    if($remove_trailing_slashes==1 && $trailpresent == true) $url = rtrim($url,'/');
                    
                    //if need to lowercase url
                    if($lowercase == true) {
                        //skip lowercase of query string if it is found because it may break the site
                        if($preg_match==true) $url = strtolower(strchr($url,'?',true)).strchr($url,'?',false);
                        else $url = strtolower($url);
                    }
                    header('Location: '.$url);
                    exit();
                }
            }
        }
    }
}