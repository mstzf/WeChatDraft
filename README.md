# WeChatDraft 插件

WeChatDraft 是一个用于 Typecho 博客系统的插件，它可以在发布文章的同时将内容同步到微信公众号的草稿中。这个插件支持带图同步，让你可以更方便地在微信上发布文章。只需在微信公众平台或订阅号助手 APP 根据自己的需要稍作调整，即可完成发布，填补了 Typecho 插件在个人公众号领域的空白。

## 自定义

在作者大大的基础上做了一点点修改（感谢原作者🥰）。

做了针对主题的定制化开发，直接下载下来很大可能无法正常使用，如果不需要下列功能不建议使用，推荐直接使用原作者发布的插件。

定制化内容：

1. 将上传图片文章部分修改为异步操作，不再阻塞文章发布。
2. 添加摘要提交。
3. 修改文章封面获取逻辑，直接和博客文章封面一致
4. 美化上传后的代码块部分内容

## 功能特点

- 将文章内容同步到微信公众号的草稿中。
- 支持同步带图，将文章中的图片自动上传至微信服务器。
- 与订阅号助手 APP 配合使用，轻松完成文章发布。

## 安装方法

1. 将插件文件夹 `WeChatDraft` 复制到 Typecho 的插件目录 `/usr/plugins/` 下。
2. 登录 Typecho 后台，进入“控制台” -> “插件管理”。
3. 找到 WeChatDraft 插件，并点击“启用”。

## 配置说明

在启用插件后，你需要进行一些简单的配置步骤才能使用 WeChatDraft 插件：

1. 获取微信公众号的 AppID 和 AppSecret。你需要在微信公众平台上创建一个公众号，并获取到相应的 AppID 和 AppSecret。
2. 在 Typecho 后台的“设置” -> “插件” -> “WeChatDraft”页面中，填入获取到的 AppID 和 AppSecret。
3. 保存设置，配置完成。

## 使用方法

使用 WeChatDraft 插件非常简单：

1. 在 Typecho 编辑器中编写你的文章，并设置好标题、内容、标签等相关信息。
2. 点击“发布”按钮，文章将会自动同步到微信公众号的草稿中。
3. 进入微信公众平台或打开订阅号助手 APP，在草稿箱中找到同步过来的草稿，稍作调整并发布即可。

注意：确保你已经正确配置了微信公众号的相关信息。

## 支持

如果本插件帮到了你，不妨给点赞赏鼓励一下作者

<img width="300" height="300" alt="支付宝" src="https://raw.githubusercontent.com/qiuzhangsaer/imageWarehouse/main/alipay.jpg"><img width="300" height="300" alt="微信" src="https://raw.githubusercontent.com/qiuzhangsaer/imageWarehouse/main/wechat.jpg">


## 帮助

如果在安装、配置或使用 WeChatDraft 插件过程中遇到任何问题，请查阅以下资源获取帮助：

- Typecho 官方论坛：[https://forum.typecho.org/](https://forum.typecho.org/)
- 蓄客博客插件说明：[https://www.xvkes.cn/archives/290/](https://www.xvkes.cn/archives/290/)
- 项目仓库：[https://github.com/qiuzhangsaer/WeChatDraft](https://github.com/qiuzhangsaer/WeChatDraft)
- 提交 Issue：[https://github.com/qiuzhangsaer/WeChatDraft/issues](https://github.com/qiuzhangsaer/WeChatDraft/issues)
