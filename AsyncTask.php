<?php
// 加载 Typecho 核心框架
define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 3)); // 根据实际路径调整
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/Db.php';
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/Widget.php';
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/Plugin.php';
require dirname(__FILE__) .  '/parsedown/CustomParsedown.php';
require dirname(__FILE__) .  '/geshi/geshi.php';
// 加载 Typecho 配置文件
require __TYPECHO_ROOT_DIR__ . '/config.inc.php';


// 获取命令行参数
$postId = $argv[1]; // 文章 ID
$author =  $argv[2];
$content_source_url =  $argv[3];

class AsyncTask{
    // 获取文章对象
    public static function getPost($cid){
        $db = Typecho_Db::get();
        return $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $cid));
    }

    // 获取文章自定义摘要
    public static function getCustomSummary($cid){
        $db = Typecho_Db::get();
        $summary = $db->fetchRow($db->select('str_value')
        ->from('table.fields')
        ->where('cid = ?', $cid)
        ->where('name = ?', 'customSummary'));
        return $summary['str_value'];
    }

    public static function getSetting(){
        $options = Typecho_Widget::widget('Widget_Options');
        return $options->plugin('WeChatDraft');
    }

    /* 获取微信access_token的方法 */
    public static function getAccessToken()
    {
        // 检查缓存中是否存在access_token
        $file = dirname(__FILE__) . '/cache/accessToken';
        $accessToken = file_exists($file) ? unserialize(file_get_contents($file)) : '';
        if (empty($accessToken) || self::isAccessTokenExpired($accessToken)) {
            // 如果缓存中不存在或已过期，重新请求获取access_token
            $newAccessToken = self::requestAccessToken();

            // 将新的access_token存储到缓存中
            file_put_contents($file, serialize($newAccessToken));

            return $newAccessToken->access_token;
        }

        return $accessToken->access_token;
    }

    /* 判断access_token是否过期的方法 */
    public static function isAccessTokenExpired($accessToken)
    {
        $time = time();
        if ($time > ($accessToken->expires_time)) {
            return true;
        }
        return false; // 假设access_token未过期
    }

    /* 请求获取新的微信access_token的方法 */
    public static function requestAccessToken()
    {
        $appid = self::getSetting()->appid;
        $secret = self::getSetting()->secret;
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
        $newAccessToken = self::curl($url);
        $newAccessToken->expires_time = time()+$newAccessToken->expires_in;

        return $newAccessToken;
    }

    /* 获取mediaid的方法 */
    public static function getMediaId(){
        $file = dirname(__FILE__) . '/cache/mediaId';
        $mediaId = file_exists($file) ? file_get_contents($file) : '';
        if (empty($mediaId)) {
            $accessToken = self::getAccessToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$accessToken;
            // 获取图片素材列表中的图片作为图文消息的封面
            $array = [
                "type"=>"image",
                "offset"=>0,
                "count"=>20
            ];
            $mediaList = (self::curl($url,json_encode($array),true))->item;
            // return $mediaList;
            $matching = null;
            foreach ($mediaList as $media) {
                if ($media->name == "typecho.jpg") {
                    $matching = $entry;
                    break;
                }
            }
            if ($matching != null) {
                $media_id = $matching->media_id;
            } else {
                // 如果不存在匹配的条目，获取数组的第一个条目的media_id
                $media_id = $mediaList[0]->media_id;
            }
            file_put_contents($file, $media_id);
            return $media_id;
        }
        return $mediaId;

    }
    /* 上传封面图片 */
    public static function uploadCover($cid){
        $db = Typecho_Db::get();
        $imagePath = $db->fetchRow($db->select('str_value')
        ->from('table.fields')
        ->where('cid = ?', $cid)
        ->where('name = ?', 'thumb'));

        $imagePath = $imagePath['str_value'] ;

        if(empty($imagePath)){
         return self::getMediaId();
        }

        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token='.$accessToken ."&type=image";
        $res = self::curl($url,'',true,$imagePath);
        return $res->media_id;
    }
    /* 上传图片到素材库 */
    public static function uploadImageToWeChat($text){
        $html = self::renderMarkdown($text);
        $accessToken = self::getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$accessToken;

        // 匹配所有的 <img> 标签
        preg_match_all('/<img[^>]+>/i', $html, $matches);
        $images = $matches[0];

        foreach ($images as $image) {
            // 提取 <img> 标签中的 src 属性值
            preg_match('/src="([^"]+)"/i', $image, $srcMatches);
            $src = $srcMatches[1];

            // 上传图片文件
            $res = self::curl($url,'',true,$src);

            // 获取上传后的图片 URL
            $wxImageUrl = $res->url;

            // 替换 HTML 中的图片标签中的 src 属性为上传后的图片 URL
            $html = str_replace($src, $wxImageUrl, $html);
        }

	$html = self::formatHtmlWithDOM($html);
        return $html;
    }

    /**
     * Curl 请求
     * @param $url
     */
    public static function curl($url,$jsonData = '',$ispost = false,$imagePath ='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 忽略 SSL 证书验证
        if ($ispost) {
            // POST 请求
            curl_setopt($ch, CURLOPT_POST, true);

            if (empty($imagePath)) {
                $postData = $jsonData;
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                ));
            } else {
                $postData = array(
                    'media' => new CURLFile($imagePath)
                );
            }
            // 设置请求体数据为 JSON 字符串
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

            // 设置请求头为 application/json

        }

        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response);
        if (!isset($responseData->errmsg)) {
            return $responseData;
        } else {
            // 请求失败，处理错误信息
            throw new Exception($responseData->errmsg);
        }
    }
    public static function codeHightLight($text){
        $text = preg_replace_callback(
            '/<pre><code(?: class="language-(.*?)")?>(.*?)<\/code><\/pre>/s',
            function ($matches) {
                // 如果没有匹配到语言类型，默认使用 'plaintext'
                $language = isset($matches[1]) && !empty($matches[1]) ? $matches[1] : 'plaintext';
                $code = $matches[2]; // 获取代码并转义 HTML 实体
                $geshi = new GeShi($code, $language);
                $highlighted_code = $geshi->parse_code();
                $highlighted_code = preg_replace('/^<pre[^>]*>|<\/pre>$/', '', $highlighted_code);
                // 将代码分割成行
                // 分割成行
                $lines = explode("\n", $highlighted_code);
                $line_num = null;
                $line_code = null;
                $line_numbered_code = '';
                foreach ($lines as $index => $line) {
                    $line_num .= '<li style="visibility: visible;"></li>';
                    $line_code .= '<code style="visibility: visible;">' . $line . '</code>';
                }
                return '
                <section class="code-snippet__fix code-snippet__js"
                style="margin-top: 5px; margin-bottom: 5px; text-align: left; font-weight: 500; font-size: 14px; margin: 10px 0; display: block; color: #333; position: relative; background-color: rgba(0,0,0,0.03); border: 1px solid #f0f0f0; border-radius: 2px; display: flex; line-height: 20px; word-wrap: break-word !important;"
                >
                    <ul class="code-snippet__line-index code-snippet__js" style="visibility: visible;">
                        ' . $line_num . '
                    </ul>
                    <pre class="code-snippet__js" data-lang="'. $language .'" style="visibility: visible;">
                        ' . $line_code . '
                    </pre>
                </section>
                ';
            },
            $text
        );
        return $text;
    }
    public static function formatHtmlWithDOM($html) {
    // 创建 DOMDocument 实例
    $dom = new DOMDocument();

    // 处理HTML错误
    libxml_use_internal_errors(true);

    // 加载HTML内容
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // 清理 DOMDocument 中的多余空格
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//text()') as $textNode) {
        $textNode->nodeValue = trim(preg_replace('/\s+/', ' ', $textNode->nodeValue));
    }

    foreach ($dom->getElementsByTagName('*') as $element) {
    if ($element->hasAttribute('style')) {
        $style = $element->getAttribute('style');
        $element->setAttribute('style', preg_replace('/\s+/', ' ', $style));
    }
    if ($element->hasAttribute('class')) {
        $class = $element->getAttribute('class');
        $element->setAttribute('class', preg_replace('/\s+/', ' ', $class));
    }
    }

    // 输出格式化后的HTML
    return $dom->saveHTML();
    }
    /* 格式化标签 */
    public static function ParseCode($text)
    {
        $text = self::codeHightLight($text);
        return $text;
    }

    public static function renderMarkdown($text)
    {
        // 重新赋值给$text
        $text = str_replace("<!--markdown-->", "", $text);
        
        // 实例化Parsedown对象
        $parsedown = new CustomParsedown();
        
        // 将Markdown转换为HTML
        $htmlContent = $parsedown->text($text);
        $htmlContent = self::ParseCode($htmlContent);
        // 返回处理后的内容

        $htmlContent = '<section id="nice" data-tool="markdown编辑器" data-website="https://markdown.com.cn/editor"
style="font-size: 16px; color: black; padding: 25px 30px; line-height: 1.6; word-spacing: 0px; letter-spacing: 0px; word-wrap: break-word; text-align: justify; margin-top: -10px; font-family: \'PingFang SC\', \'Microsoft YaHei\', sans-serif; word-break: break-all;">' . $htmlContent . '</section>';

        return $htmlContent;
    }


    
    /* 插件实现方法 */
    public static function render($cid,$obj){
        $setting = self::getSetting();
        $post = self::getPost($cid);
        if (empty($post['password']) && strlen($post['text']) > 100 && $setting->appid && $setting->secret) {
            $accessToken = self::getAccessToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/draft/add?access_token='.$accessToken;

            $mediaId = self::uploadCover($cid);

            $customSummary = self::getCustomSummary($cid);
            $html = html_entity_decode(self::uploadImageToWeChat($post['text']), ENT_QUOTES, 'UTF-8');
            $array = [
                "articles"=>[
                    [
                        "title"=>$post['title'],
                        "author"=>$obj['author'],
                        "content"=>$html,
                        "content_source_url"=>$obj['content_source_url'],
                        "thumb_media_id"=>$mediaId,
                        "digest" => $customSummary
                    ]
                ]
            ];
            self::curl($url,json_encode($array, JSON_UNESCAPED_UNICODE),true);
        }
    }
}
$baseObj = array(
    "author" => $author,
    "content_source_url" => $content_source_url,
);
$AsyncTask = new AsyncTask();
$AsyncTask->render($postId,$baseObj);
