<?php
namespace zucha\tilesProxy;

use yii\base\Action;
use yii\web\Response;
use yii\web\HttpException;
use yii\helpers\FileHelper;

/**
 * Get tiles from tile servers, stores localy 
 * @author uldis@sit.lv
 * @copyright 2020
 */
class Proxy extends Action
{
    /**
     * configuraton parameter
     * @var string
     */
    const YII_PARAM_KEY = 'tiles-proxy';
    
    /**
     * list of sources
     * each item have list of array of sources
     * @var array
     */
    private $_sources = [];
    
    /**
     * optional 
     * if not set, first source option will be taken
     * @var string
     */
    private $_defaultSource;
    
    /**
     * where tile images will be stored
     * @var string
     */
    private $_sourceDir = "";
    
    /**
     * {@inheritDoc}
     * @see \yii\base\Action::beforeRun()
     */
    protected function beforeRun()
    {
        if (!array_key_exists(static::YII_PARAM_KEY, \Yii::$app->params))
        {
            \Yii::error("proxy configuration not defined");
            return false;
        }
        
        $parameters = \Yii::$app->params[static::YII_PARAM_KEY];
        
        $this->_sources = $parameters['sources'];
        
        if (isset($parameters['default-source']))
        {
            $this->_defaultSource = $parameters['default-source'];
        } else 
        {
            reset($this->_sources);
            $this->_defaultSource = key($this->_sources);
        }
        
        $this->_sourceDir = $parameters["source-dir"];
        
        return true;
    }
    
    /**
     * Tile images
     * 
     * @return string
     */
    public function run ()
    {
        $ttl = 86400 * 31; //cache timeout in seconds 86400 = 1day
        
        $x = intval(\Yii::$app->request->get("x", 0));
        $y = intval(\Yii::$app->request->get("y", 0));
        $z = intval(\Yii::$app->request->get("z", 0));
        $source = strip_tags(\Yii::$app->request->get("src", $this->_defaultSource));

        if (!array_key_exists($source, $this->_sources))
        {
            throw new HttpException(400, "bad source");
        }
        
        $sourceDir = strtr(\Yii::getAlias($this->_sourceDir), [
            ':source' => $source     ]);
        
        if (!file_exists($sourceDir))
        {
            FileHelper::createDirectory($sourceDir);
        }
        
        $file = $sourceDir . "/${z}_${x}_${y}.png";
        
        if (!is_file($file) || ( filemtime($file)<time()-($ttl) && rand(1,10)==10))
        {

            $url = $this->_sources[$source][ array_rand($this->_sources[$source]) ];
            
            $url = strtr($url, [
                ':x' => $x,
                ':y' => $y,
                ':z' => $z
            ]);
            
            \Yii::info($url);
            
            $curlH = curl_init($url);
            
            if (!$curlH)
            {
                throw new HttpException(400, "cannot init curl file");
            }
            
            curl_setopt($curlH, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlH, CURLOPT_HEADER, 0);
            curl_setopt($curlH, CURLOPT_USERAGENT, \Yii::$app->id);

            if (isset($_SERVER['HTTP_REFERER']))
            {
                curl_setopt($curlH, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
            }
            
            if (!($data = curl_exec($curlH)))
            {
                throw new HttpException(400, "cannot retrieve file");
            }
            
            $statusCode = curl_getinfo($curlH, CURLINFO_HTTP_CODE);
            curl_close($curlH);
            
            if ($statusCode != 200 )
            {
                throw new HttpException(400, "bad response from source: " . $statusCode);
            }
            
            if (!($fileH = fopen($file, "w")))
            {
                throw new HttpException(400, "cannot open file");
            }
            
            fwrite($fileH, $data);
            
            fflush($fileH);    // need to insert this line for proper output when tile is first requestet
            fclose($fileH);
        }
        
        $expireGmt = gmdate("D, d M Y H:i:s", time() + $ttl * 2) ." GMT";
        $modifiedGmt = gmdate("D, d M Y H:i:s", filemtime($file)) ." GMT";
        
        \Yii::$app->response->format = Response::FORMAT_RAW;
        
        \Yii::$app->response->headers->set("Expires", $expireGmt);
        \Yii::$app->response->headers->set("Last-Modified", $modifiedGmt);
        \Yii::$app->response->headers->set("Cache-Control", "public, max-age=" . $ttl * 60);
        
        // for MSIE 5
        //\Yii::$app->response->headers->set("Cache-Control", "pre-check=" . $ttl * 60);
        \Yii::$app->response->headers->set("Content-Type", "image/png");
        
        \Yii::$app->response->headers->set("Access-Control-Allow-Origin", "*");
        
        \Yii::$app->response->stream = fopen($file, "r");
        
        return \Yii::$app->response;
    }
}
