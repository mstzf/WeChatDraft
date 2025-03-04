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
$post = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', 173));

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

// 返回处理后的内容
error_log($htmlContent,3,dirname(__FILE__) . "/cache/htmlContent.html");