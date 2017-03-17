/**
* markdown - Script that will transform your notes taken in the Markdown format (.md files) into a rich website
* @version   : 1.0.5
* @author    : christophe@aesecure.com
* @license   : MIT
* @url       : https://github.com/cavo789/markdown
* @package   : 2017-02-16T12:37:19.404Z
*/
function ajaxify($params){var $data={};$data.task="undefined"===$params.task?"":$params.task,$data.param="undefined"===$params.param?"":$params.param,"undefined"!==$params.param2&&($data.param2=$params.param2),"undefined"!==$params.param3&&($data.param3=$params.param3);var $target="#"+("undefined"===$params.target?"TDM":$params.target);$.ajax({beforeSend:function(){$($target).html('<div><span class="ajax_loading">&nbsp;</span><span style="font-style:italic;font-size:1.5em;">'+markdown.message.pleasewait+"</span></div>")},async:!0,cache:!1,type:markdown.settings.debug?"GET":"POST",url:markdown.url,data:$data,datatype:"html",success:function(data){$($target).html(data);var $callback=void 0===$params.callback?"":$params.callback;""!==$callback&&eval($callback)}})}function addSearchEntry(e){if($bReset="undefined"!==e.reset&&e.reset,$current=$("#search").val().trim(),""!==$current&&$bReset===!1){var a=$current.split(",");a.push(e.keyword),$("#search").val(a.join(","))}else $("#search").val(e.keyword);return!0}function initFiles(e){var a="";if("undefined"==typeof e)return a=markdown.message.json_error,Noty({message:a.replace("%s","listFiles"),type:"error"}),!1;try{e.hasOwnProperty("count")&&(a=markdown.message.filesfound,Noty({message:a.replace("%s",e.count),type:"notification"}))}catch(e){console.warn(e.message)}jstree_init(e),$.isFunction($.fn.flexdatalist)&&($(".flexdatalist").flexdatalist({toggleSelected:!0,minLength:2,valueProperty:"id",selectionRequired:!1,visibleProperties:["name","type"],searchIn:"name",data:"index.php?task=tags",focusFirstResult:!0,noResultsText:markdown.message.search_no_result}),""!==markdown.settings.auto_tags&&addSearchEntry({keyword:markdown.settings.auto_tags})),$("#search").css("width",$("#TDM").width()-5),$(".flexdatalist-multiple").css("width",$(".flexdatalist-multiple").parent().width()-10).show();try{$("#search-flexdatalist").focus()}catch(e){console.warn(e.message)}try{"undefined"!=typeof custominiFiles&&$.isFunction(custominiFiles)&&custominiFiles()}catch(e){console.warn(e.message)}return!0}function initializeTasks(){if($.isFunction($.fn.printPreview))try{$('[data-task="printer"]').printPreview()}catch(e){console.warn(e.message)}return $("[data-task]").click(function(){$("#IMG_BACKGROUND").length&&$("#IMG_BACKGROUND").remove();var e=$(this).data("task"),a=$(this).attr("data-file")?$(this).data("file"):"",t=$(this).attr("data-tag")?$(this).data("tag").replace("\\","/"):"";switch(""!==a&&"window"!==e&&(a=window.btoa(encodeURIComponent(JSON.stringify(a)))),e){case"clipboard":if("function"==typeof Clipboard){var s=new Clipboard('*[data-task="clipboard"]');s.on("success",function(e){e.clearSelection()}),Noty({message:markdown.message.copy_clipboard_done,type:"success"})}else $(this).remove();break;case"display":ajaxify({task:e,param:a,callback:"afterDisplay($data.param)",target:"CONTENT"});break;case"edit":ajaxify({task:e,param:a,callback:"afterEdit($data.param)",target:"CONTENT"});break;case"fullscreen":toggleFullScreen();break;case"link_note":"function"==typeof Clipboard?(new Clipboard('*[data-task="link_note"]'),Noty({message:markdown.message.copy_link_done,type:"success"})):$(this).remove();break;case"pdf":window.open("index.php?task=pdf&param="+a);break;case"printer":break;case"settings":ajaxify({task:"clean",callback:"afterClean(data)"});break;case"slideshow":slideshow(a);break;case"tag":addSearchEntry({keyword:t,reset:!0});break;case"window":window.open(a);break;default:console.warn("Sorry, unknown task ["+e+"]")}}),!0}function afterClean(e){console.log(e),e.hasOwnProperty("status")&&($status=e.status,Noty(1==$status?{message:e.msg,type:"success"}:{message:e.msg,type:"error"}))}function replaceLinksToOtherNotes(){try{for(var e=$("#CONTENT").html(),a=location.protocol+"//"+location.host+location.pathname,t=new RegExp("<a href=['|\"]"+RegExp.quote(a)+"?.*>(.*)</a>","i"),s=t.exec(e),n=[],i="";null!==s;)n=s[0].match(/param=(.*)['|"]/),i=JSON.parse(decodeURIComponent(window.atob(n[1]))),$sNodes='<span class="note" title="'+markdown.message.display_that_note+'" data-task="display" data-file="'+i+'">'+s[1]+"</span>",e=e.replace(s[0],$sNodes),s=t.exec(e);$("#CONTENT").html(e)}catch(e){console.warn(e.message)}}function addLinksToTags(){var e=$("#CONTENT").html();try{for(var a=new RegExp("( |,|;|\\.|\\n|\\r|\\t)*"+markdown.settings.prefix_tag+"([(\\&amp;)\\.a-zA-Z0-9\\_\\-]+)( |,|;|\\.|\\n|\\r|\\t)*","i"),t=a.exec(e);null!==t;)$sTags=(void 0!==t[1]?t[1]:"")+'<span class="tag" title="'+markdown.message.apply_filter_tag+'" data-task="tag" data-tag="'+t[2]+'">'+t[2]+"</span>"+(void 0!==t[3]?t[3]:""),e=e.replace(new RegExp(t[0],"g"),$sTags),t=a.exec(e);$("#CONTENT").html(e)}catch(e){console.warn(e.message)}}function forceNewWindow(){var e=location.protocol+"//"+location.host;return $('a[href^="http:"], a[href^="https:"]').not('[href^="'+e+'/"]').attr("target","_blank"),!0}function addIcons(){try{$("a").each(function(){$href=$(this).attr("href"),$sAnchor=$(this).text(),/\.doc[x]?$/i.test($href)?($sAnchor+='<i class="icon_file fa fa-file-word-o" aria-hidden="true"></i>',$(this).html($sAnchor).addClass("download")):/\.(log|md|markdown|txt)$/i.test($href)?($sAnchor+='<i class="icon_file fa fa-file-text-o" aria-hidden="true"></i>',$(this).html($sAnchor).addClass("download-link").attr("target","_blank")):/\.pdf$/i.test($href)?($sAnchor+='<i class="icon_file fa fa-file-pdf-o" aria-hidden="true"></i>',$(this).html($sAnchor).addClass("download-link").attr("target","_blank")):/\.ppt[x]?$/i.test($href)?($sAnchor+='<i class="icon_file fa fa-file-powerpoint-o" aria-hidden="true"></i>',$(this).html($sAnchor).addClass("download-link")):/\.xls[m|x]?$/i.test($href)?($sAnchor+='<i class="icon_file fa fa-file-excel-o" aria-hidden="true"></i>',$(this).html($sAnchor).addClass("download-link")):/\.(7z|gzip|tar|zip)$/i.test($href)&&($sAnchor+='<i class="icon_file fa fa-file-archive-o" aria-hidden="true"></i>',$(this).html($sAnchor).addClass("download-link"))})}catch(e){console.warn(e.message)}return!0}function NiceTable(){try{$("table").each(function(){$(this).addClass("table table-striped table-hover table-bordered"),$.isFunction($.fn.DataTable)&&($(this).addClass("display"),$(this).DataTable({scrollY:"50vh",scrollCollapse:!0,info:!0,lengthMenu:[[10,25,50,-1],[10,25,50,"All"]],language:{decimal:".",thousands:",",url:"libs/DataTables/"+markdown.settings.language+".json"}}))})}catch(e){console.warn(e.message)}return!0}function afterDisplay(e){try{"function"!=typeof Clipboard&&$('[data-task="clipboard"]').remove(),$.isFunction($.fn.printPreview)||$('[data-task="printer"]').remove(),$.isFunction($.fn.linkify)&&$("page").linkify(),"object"==typeof Prism&&Prism.highlightAll(),replaceLinksToOtherNotes(),addLinksToTags(),forceNewWindow(),addIcons(),NiceTable(),initializeTasks();var a=$("#CONTENT h1").text();""!==a&&$("title").text(a),e=$("div.filename").text(),""!==e&&$("#footer").html('<strong style="text-transform:uppercase;">'+e+"</strong>");try{$("#search").focus();var t=$("#search").val().substr(0,markdown.settings.search_max_width).trim();""!==t&&$.isFunction($.fn.highlight)&&$("#CONTENT").highlight(t)}catch(e){}"undefined"!=typeof customafterDisplay&&$.isFunction(customafterDisplay)&&customafterDisplay(e)}catch(e){console.warn(e.message)}return $("#CONTENT").fadeOut(1).fadeIn(3),!0}function afterEdit(e){var a=new SimpleMDE({autoDownloadFontAwesome:!1,autofocus:!0,element:document.getElementById("sourceMarkDown"),indentWithTabs:!1,codeSyntaxHighlighting:!1,toolbar:[{name:"Save",action:function(t){buttonSave(e,a.value())},className:"fa fa-floppy-o",title:markdown.message.button_save},{name:"Encrypt",action:function(e){buttonEncrypt(e)},className:"fa fa-user-secret",title:markdown.message.button_encrypt},"|",{name:"Exit",action:function(a){$("#sourceMarkDown").parent().hide(),ajaxify({task:"display",param:e,callback:"afterDisplay($data.param)",target:"CONTENT"})},className:"fa fa-sign-out",title:markdown.message.button_exit_edit_mode},"|","preview","side-by-side","fullscreen","|","bold","italic","strikethrough","|","heading","heading-smaller","heading-bigger","|","heading-1","heading-2","heading-3","|","code","quote","unordered-list","ordered-list","clean-block","|","link","image","table","horizontal-rule"]});return $(".editor-toolbar").addClass("fa-2x"),!0}function buttonSave(e,a){var t={};return t.task="save",t.param=e,t.markdown=window.btoa(encodeURIComponent(JSON.stringify(a))),$.ajax({async:!0,type:"POST",url:markdown.url,data:t,datatype:"json",success:function(e){Noty({message:e.status.message,type:1==e.status.success?"success":"error"})}}),!0}function buttonEncrypt(e){var a=e.codemirror,t="",s=a.getSelection(),n=s||"your_confidential_info";t="<encrypt>"+n+"</encrypt>",a.replaceSelection(t)}function onChangeSearch(){try{var e=$("#search").val().substr(0,markdown.settings.search_max_width).trim(),a=!0;"undefined"!=typeof customonChangeSearch&&$.isFunction(customonChangeSearch)&&(a=customonChangeSearch(e)),a===!0&&(""!==e&&($msg=markdown.message.apply_filter,Noty({message:$msg.replace("%s",e),type:"notification"})),ajaxify({task:"search",param:window.btoa(encodeURIComponent(e)),callback:'afterSearch("'+e+'",data)'}))}catch(e){console.warn(e.message)}return!0}function afterSearch(e,a){try{Object.keys(a).length>0?$.isFunction($.fn.jstree)?($files=a.files,$filename="",$("#TOC").jstree("open_all"),$.each($("#TOC").jstree("full").find("li"),function(e,a){$filename=$("#TOC").jstree(!0).get_path(a,markdown.settings.DS),$(a).hasClass("jstree-leaf")&&$(a).hide(),""!==$filename&&$.each($files,function(e,t){if(t===$filename+".md")return $(a).hasClass("jstree-leaf")&&($(a).addClass("highlight").show(),$("#TOC").jstree("select_node",a)),!1})}),$.each($("#TOC").jstree("full").find("li"),function(e,a){$(a).hasClass("highlight")||$("#TOC").jstree("close_node",a)}),$.each($("#TOC").jstree("full").find("li"),function(e,a){$(a).hasClass("highlight")&&$("#TOC").jstree("open_node",a,function(e,a){for(var t=0;t<e.parents.length;t++)$("#TOC").jstree("open_node",e.parents[t])})})):$("#tblFiles > tbody  > tr > td").each(function(){$(this).attr("data-file")&&($filename=$(this).data("file"),$tr=$(this).parent(),$tr.hide(),$.each(a,function(){$.each(this,function(e,a){if(a===$filename)return $tr.show(),!1})}))}):""!==e?noty({message:markdown.message.search_no_result,type:"success"}):$.isFunction($.fn.jstree)?($.each($("#TOC").jstree("full").find("li"),function(e,a){$(a).removeClass("highlight").show()}),$("#TOC").jstree("open_all")):$("#tblFiles > tbody  > tr > td").each(function(){$(this).attr("data-file")&&$(this).parent().show()});try{"undefined"!=typeof customafterSearch&&$.isFunction(customafterSearch)&&customafterSearch(e,a)}catch(e){console.warn(e.message)}}catch(e){console.warn(e.message)}}function slideshow(e){try{var a={};a.task="slideshow",a.param=e,$.ajax({async:!0,type:markdown.settings.debug?"GET":"POST",url:markdown.url,data:a,datatype:"json",success:function(e){var a=window.open(e,"slideshow");void 0===a&&Noty({message:markdown.message.allow_popup_please,type:"notification"})}})}catch(e){console.warn(e.message)}return!0}function Noty(e){if($.isFunction($.fn.noty)){if(""===e.message)return!1;$type="undefined"===e.type?"info":e.type;noty({text:e.message,theme:"relax",timeout:2400,layout:"bottomRight",type:$type})}}var QueryString=function(){for(var e={},a=window.location.search.substring(1),t=a.split("&"),s=0;s<t.length;s++){var n=t[s].split("=");if("undefined"==typeof e[n[0]])e[n[0]]=decodeURIComponent(n[1]);else if("string"==typeof e[n[0]]){var i=[e[n[0]],decodeURIComponent(n[1])];e[n[0]]=i}else e[n[0]].push(decodeURIComponent(n[1]))}return e}();Array.prototype.contains=function(e){for(var a=0;a<this.length;a++)if(this[a]===e)return!0;return!1},Array.prototype.unique=function(){for(var e=[],a=0;a<this.length;a++)e.contains(this[a])||e.push(this[a]);return e},RegExp.quote=function(e){return(e+"").replace(/[.?*+^$[\]\\(){}|-]/g,"\\$&")},$(document).ready(function(){Noty({message:markdown.message.loading_tree,type:"info"}),ajaxify({task:"listFiles",callback:"initFiles(data)"}),$("#TDM").css("max-height",$(window).height()-30),$("#TDM").css("min-height",$(window).height()-30),$("#CONTENT").css("max-height",$(window).height()-10),$("#CONTENT").css("min-height",$(window).height()-10),$("#search").change(function(e){onChangeSearch()})});