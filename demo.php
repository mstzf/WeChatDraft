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


$db = Typecho_Db::get();
$post = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', 49));

$text = $post['text'];

// 重新赋值给$text
$text = str_replace("<!--markdown-->", "", $text);

// 实例化Parsedown对象
$parsedown = new CustomParsedown();

// 将Markdown转换为HTML
$htmlContent = $parsedown->text($text);

$htmlContent = preg_replace_callback(
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
    $htmlContent
);

$htmlContent = '<section id="nice" data-tool="markdown编辑器" data-website="https://markdown.com.cn/editor"
style="font-size: 16px; color: black; padding: 25px 30px; line-height: 1.6; word-spacing: 0px; letter-spacing: 0px; word-wrap: break-word; text-align: justify; margin-top: -10px; font-family: \'PingFang SC\', \'Microsoft YaHei\', sans-serif; word-break: break-all;">' . $htmlContent . '</section>';

function formatHtmlWithDOM($html) {
    // 创建 DOMDocument 实例
    $dom = new DOMDocument('1.0', 'UTF-8');  // 设置编码为 UTF-8

    // 处理HTML错误
    libxml_use_internal_errors(true);

    // 加载HTML内容，并强制指定 UTF-8 编码
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');  // 转换为HTML实体
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // 清理 DOMDocument 中的多余空格
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//text()') as $textNode) {
        // 跳过 <code> 标签中的内容
        if ($textNode->parentNode->nodeName != 'code') {
            $textNode->nodeValue = trim(preg_replace('/\s+/', ' ', $textNode->nodeValue));
        }
    }

    // 处理 style 和 class 属性中的多余空格
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

    // 输出格式化后的HTML，确保保存为 UTF-8 编码
    return mb_convert_encoding($dom->saveHTML(), 'UTF-8', 'HTML-ENTITIES');
}


$htmlContent = formatHtmlWithDOM($htmlContent);


// 返回处理后的内容
error_log($htmlContent,3,dirname(__FILE__) . "/cache/htmlContent.html");
