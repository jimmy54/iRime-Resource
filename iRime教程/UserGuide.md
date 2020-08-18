# 简易索引  
- 下载及安装
  - 稳定版：[F-Droid][]，[酷安][]，[Google Play][]，[Releases][]  
  - [测试版][]
- 添加输入方案
  - [简易图解][]（待更新）  
  - [常用输入方案][]（或至 [Rime 同文堂][]（480159874）下载“Brise 懒人包”）  
  - [更多开源输入方案][]  
- 学习如何DIY
  - 配置输入法（键盘及各种界面功能），可参考[`trime.yaml`详解][]。  
  - 配置输入方案，请参阅 [Rime说明书][]。

`附表1: （同文）Rime 文件分布、作用及相关教程`  

|文件&文件夹|作用及相关教程|
|------|------|
|:file_folder:`<词典名>.userdb`|<u>用户词典</u>：存储用户输入习惯。|
|:file_folder:`backgrounds`|<u>主题图片(可选)</u>：主题中要用到的图片都是放在这里。|
|:file_folder:`build`|<u>编译结果</u>：部署成功后，会在此处生成编译结果文件（ yaml 或 bin 格式）。输入法程序运行时读取的也是这里的文件。对于较复杂的输入方案，在手机端若无法部署，也可将 PC 端部署生成的编译结果文件拷贝到这里使用（新版 librime 生成的 bin 文件可通用）。编辑方案或主题请直接操作用户文件夹中的源文件，而非这里的编译结果。|
|:file_folder:`fonts`|<u>自定义字体(可选)</u>：用于改变界面字体。将个性化的字体存放于此文件夹中，再在`trime.yaml`中调用，示例：[更改界面字体][]|
|:file_folder:`opencc`|<u>简繁转换组件(可选)</u>：简繁转换。[原理及示例][]|
|:file_folder:`sync`|<u>同步文件夹</u>：备份方案&词库及相关配置文件，导出的用户词典也存放在此处。详见[同步用户资料][]。|
|:page_facing_up:`custom_phrase.txt`|<u>自定义短语(可选)</u>：存储少量的固定短语等数据。配置步骤：①[新建短语翻译器][] ②[配置翻译器][] ③往custom_phrase.txt添加自定义短语 （[custom_phrase样例文件][] *）|
|:page_facing_up:`default.yaml`<br>:page_facing_up:`default.custom.yaml`|<u>全局设定及其补丁文件</u>：Rime各个平台通用的**全局参数** (功能键定义、按键捆绑、方案列表、候选条数……)。请参考[定制指南][]|
|:page_facing_up:`essay.txt`|<u>八股文(可选)</u>：一份词汇表和简陋的语言模型。[八股文的详细说明][]|
|:page_facing_up:`installation.yaml`|<u>安装信息</u>：保存安装ID用以区分不同来源的备份数据，也可以在此处设定同步位置。详见[同步用户资料][]|
|:page_facing_up:`<方案标识>.schema.yaml`<br>:page_facing_up:`<方案标识>.custom.yaml `|<u>输入方案定义及其补丁文件</u>：输入方案的**设定**。可参考[详解输入方案][] 以及 [`schema.yaml`详解][]|
|:page_facing_up:`<词典名>.dict.yaml`<br>:page_facing_up:`<词典名>.<分词库名>.dict.yaml`|<u>输入方案词典及其分词库</u>：输入方案所使用的**词典**(包含词条、编码、构词码、权重等信息)。详见[码表与词典][] 以及 [`dict.yaml`详解][]|
|:page_facing_up:`symbols.yaml`|<u>扩充的特殊符号</u>：提供了比`default.yaml`更为丰富的特殊符号，[symbols.yaml用法说明][]。|
|:page_facing_up:`trime.yaml`<br>:page_facing_up:`trime.custom.yaml`<br>:page_facing_up:`xxx.trime.yaml`<br>:page_facing_up:`xxx.trime.custom.yaml`|<u>同文主题及其补丁文件</u>：定义键盘**配色、布局、样式等**。可参考[`trime.yaml`详解][]|
|:page_facing_up:`user.yaml`|<u>用户状态信息</u>：用来保存当前所使用的方案ID，以及各种开关的状态。|

[Google Play]:https://play.google.com/store/apps/details?id=com.osfans.trime
[Releases]:https://github.com/osfans/trime/releases  
[酷安]:https://www.coolapk.com/apk/com.osfans.trime
[F-Droid]:https://f-droid.org/packages/com.osfans.trime/
[测试版]:https://osfans.github.io/trime/
[Rime 同文堂]:http://shang.qq.com/wpa/qunwpa?idkey=e31ecec8f92699597d9154f890841b3e477f5185902f10400e7c9e670a11202f
[简易图解]:https://user-images.githubusercontent.com/16501929/39121157-583bfda6-4723-11e8-9cf0-b08718ca127e.jpg
[常用输入方案]:https://github.com/rime/plum/blob/master/README.md#packages
[更多开源输入方案]:https://github.com/osfans/rime-tool
[Rime说明书]:https://github.com/rime/home/wiki/UserGuide
[更改界面字体]:https://github.com/osfans/trime/wiki/trime.yaml詳解#%E7%A4%BA%E4%BE%8B%E6%9B%B4%E6%94%B9%E5%AD%97%E4%BD%93
[原理及示例]:https://github.com/rime/home/wiki/CustomizationGuide/0dd06383528e7794013815c1b12c32ec8647ef56#%E4%B8%80%E4%BE%8B%E5%AE%9A%E8%A3%BD%E7%B0%A1%E5%8C%96%E5%AD%97%E8%BC%B8%E5%87%BA
[同步用户资料]:https://github.com/rime/home/wiki/UserGuide#同步用戶資料
[配置翻译器]:https://github.com/rime/rime-luna-pinyin/blob/master/luna_pinyin.schema.yaml#L81-L87
[新建短语翻译器]:https://github.com/rime/rime-luna-pinyin/blob/master/luna_pinyin.schema.yaml#L49
[custom_phrase样例文件]:https://gist.github.com/lotem/5440677
[定制指南]:https://github.com/rime/home/wiki/CustomizationGuide#定製指南
[八股文的详细说明]:https://github.com/rime/home/wiki/RimeWithSchemata#八股文
[详解输入方案]:https://github.com/rime/home/wiki/RimeWithSchemata#詳解輸入方案
[`schema.yaml`详解]:https://github.com/LEOYoon-Tsaw/Rime_collections/blob/master/Rime_description.md#schemayaml-詳解
[码表与词典]:https://github.com/rime/home/wiki/RimeWithSchemata#碼表與詞典
[`dict.yaml`详解]:https://github.com/LEOYoon-Tsaw/Rime_collections/blob/master/Rime_description.md#dictyaml-詳解
[symbols.yaml用法说明]:https://github.com/rime/rime-prelude/blob/master/symbols.yaml#L4-L10
[`trime.yaml`详解]:https://github.com/osfans/trime/wiki/trime.yaml詳解
