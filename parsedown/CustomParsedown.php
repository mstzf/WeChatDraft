<?php
require 'Parsedown.php';

class CustomParsedown extends Parsedown
{
    protected function blockHeader($Line)
    {
        $Block = parent::blockHeader($Line);
        if ($Block !== null) {
            $level = $Block['element']['name']; // 获取h1-h6
            $Block['element']['attributes']['class'] = "custom-{$level}";

            switch ($level) {
                case 'h1':
                    $Block['element']['attributes']['style'] = '
                        font-size: 1.7em;
                        font-weight: normal;
                        border-bottom: 2px solid hsl(216, 100%, 68%);
                        margin-top: 30px;
                        margin-bottom: 15px;
                    ';
                    $Block['element']['text'] = '<span style="
                        background: hsl(216, 100%, 68%);
                        color: white;
                        padding: 3px 10px;
                        border-top-right-radius: 3px;
                        border-top-left-radius: 3px;
                        margin-right: 3px;
                        margin-top: 30px;
                        margin-bottom: 15px;
                    ">' . $Block['element']['text'] . '</span>';
                    break;

                case 'h2':
                    $Block['element']['attributes']['style'] = '
                        font-weight: normal;
                        color: #333;
                        font-size: 1.4em;
                        border-bottom: 1px solid hsl(216, 100%, 68%);
                        margin-top: 30px;
                        margin-bottom: 15px;
                    ';
                    $Block['element']['text'] = '<span style="border-bottom: 1px solid hsl(216, 100%, 68%);">' . $Block['element']['text'] . '</span>';
                    break;

                case 'h3':
                    $Block['element']['attributes']['style'] = '
                        font-weight: normal;
                        color: #333;
                        font-size: 1.2em;
                        margin-top: 30px;
                        margin-bottom: 15px;
                    ';
                    break;

                case 'h4': // 摘要
                    $Block['element']['attributes']['style'] = '
                        font-weight: normal;
                        font-size: 1em;
                        width: 80%;
                        border: 1px solid hsl(216, 100%, 68%);
                        border-top: 4px solid hsl(216, 100%, 68%);
                        padding: 10px;
                        margin: 30px auto;
                        color: #333;
                        margin-top: 30px;
                        margin-bottom: 15px;
                    ';
                    break;

                case 'h5': // 强调
                    $Block['element']['attributes']['style'] = '
                        font-weight: normal;
                        font-size: 1.3em;
                        text-align: center;
                        background: hsl(216, 100%, 68%);
                        border: 3px double #fff;
                        width: 80%;
                        padding: 10px;
                        margin: 30px auto;
                        color: #fff;
                        margin-top: 30px;
                        margin-bottom: 15px;
                    ';
                    break;

                case 'h6': // 序号
                    $Block['element']['attributes']['style'] = '
                        font-size: 1.5em;
                        font-weight: normal;
                        color: hsl(216, 100%, 68%);
                        border-bottom: 1px solid hsl(216, 100%, 68%);
                        margin-top: 30px;
                        margin-bottom: 15px;
                    ';
                    break;
            }
        }
        return $Block;
    }
    protected function paragraph($Line)
    {
        // 调用父类方法获取默认的段落结构
        $Block = parent::paragraph($Line);
        if ($Block !== null) {
            $Block['element']['attributes']['style'] = '
                margin: 0px;
                line-height: 26px;
                font-size: 16px;
            ';
        }
        return $Block;
    }
    
    protected function blockQuote($Line)
    {
        $Block = parent::blockQuote($Line);
        if ($Block !== null) {
            $Block['element']['attributes']['class'] = 'custom-blockquote';
            $Block['element']['attributes']['style'] = '
                background: #f9f9f9;
                border-left: 4px solid hsl(216, 100%, 68%);
                padding: 10px;
            ';
        }
        return $Block;
    }
    
    protected function inlineCode($Line, $Block = null)
    {
        $Block = parent::inlineCode($Line, $Block);
        if ($Block !== null) {
            // 这里可以根据需要添加自定义的 CSS 类
            if (isset($Block['element']['attributes']['class']) && $Block['element']['attributes']['class'] === 'inline-code') {
                $Block['element']['attributes']['class'] = 'custom-inline-code'; // p 标签内的 code
                $Block['element']['attributes']['style'] = '
                font-size: 14px;
                word-wrap: break-word;
                padding: 2px 4px;
                border-radius: 4px;
                margin: 0 2px;
                color: #1e6bb8;
                background-color: rgba(27, 31, 35, .05);
                font-family: Operator Mono, Consolas, Monaco, Menlo, monospace;
                word-break: break-all;
                color: hsl(216, 100%, 68%);
            ';
            } else {
                $Block['element']['attributes']['class'] = 'custom-block-code'; // 非 p 标签内的 code
            }
            $Block['element']['attributes']['style'] = '
                font-size: 14px;
                word-wrap: break-word;
                padding: 2px 4px;
                border-radius: 4px;
                margin: 0 2px;
                color: #1e6bb8;
                background-color: rgba(27, 31, 35, .05);
                font-family: Operator Mono, Consolas, Monaco, Menlo, monospace;
                word-break: break-all;
                color: hsl(216, 100%, 68%);
            ';
        }
        return $Block;
    }

    protected function blockList($Block)
    {
        $Block = parent::blockList($Block);
        if ($Block !== null) {
            $Block['element']['attributes']['style'] = '
                padding-left: 2em;
            ';
        }
        return $Block;
    }

    protected function blockTable($Line, ?array $Block = null)
    {
        $Block = parent::blockTable($Line, $Block);
        if ($Block !== null) {
            $Block['element']['attributes']['class'] = 'custom-table';
            $Block['element']['attributes']['style'] = '
                margin: 1.5em auto;
                width: auto;
                border-collapse: collapse;
            ';
        }
        return $Block;
    }

    protected function inlineEmphasis($Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return;
        }

        $marker = $Excerpt['text'][0];

        if ($Excerpt['text'][1] === $marker && preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'strong';
        } elseif (preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'em';
        } else {
            return;
        }

        $result = [
            'extent' => strlen($matches[0]),
            'element' => [
                'name' => $emphasis,
                'handler' => 'line',
                'text' => $matches[1],
            ],
        ];

        if ($emphasis === 'strong') {
            $result['element']['attributes'] = [
                'style' => 'color: hsl(216, 80%, 44%);',
            ];
        }

        return $result;
    }

    protected function inlineLink($Excerpt)
    {
        $Inline = parent::inlineLink($Excerpt);
        if ($Inline !== null) {
            $Inline['element']['attributes']['style'] = '
                color: hsl(187, 100%, 45%);
                font-weight: normal;
                border-bottom: 1px solid hsl(187, 100%, 45%);
            ';
        }
        return $Inline;
    }
}
?>
