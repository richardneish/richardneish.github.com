/* modified from the Sajax PHP/AJAX include library: (c) copyright 2005 modernmethod, inc */
var sajax_debug_mode=false;var sajax_request_type="POST";var sajax_target_id="";var sajax_failure_redirect="";function sajax_debug(_1){if(sajax_debug_mode){alert(_1);}}function sajax_init_object(){sajax_debug("sajax_init_object() called..");var A;var _3=new Array("Msxml2.XMLHTTP.5.0","Msxml2.XMLHTTP.4.0","Msxml2.XMLHTTP.3.0","Msxml2.XMLHTTP","Microsoft.XMLHTTP");for(var i=0;i<_3.length;i++){try{A=new ActiveXObject(_3[i]);}catch(e){A=null;}}if(!A&&typeof XMLHttpRequest!="undefined"){A=new XMLHttpRequest();}if(!A){sajax_debug("Could not create connection object.");}return A;}var sajax_requests=new Array();function sajax_cancel(){for(var i=0;i<sajax_requests.length;i++){sajax_requests[i].abort();}}function sajax_do_call(_6,_7){var i,x,n;var _9;var _a;var _b;sajax_debug("in sajax_do_call().."+sajax_request_type+"/"+sajax_target_id);_b=sajax_target_id;if(typeof (sajax_request_type)=="undefined"||sajax_request_type==""){sajax_request_type="GET";}_9="";if(sajax_request_type=="GET"){if(_9.indexOf("?")==-1){_9+="?rs="+escape(_6);}else{_9+="&rs="+escape(_6);}if (document.getElementById("sid")){_9+="&PHPSESSID="+document.getElementById("sid").value;}_9+="&rst="+escape(sajax_target_id);_9+="&rsrnd="+new Date().getTime();for(i=0;i<_7.length-1;i++){_9+="&rsargs[]="+escape(_7[i]);}_a=null;}else{if(sajax_request_type=="POST"){_a="rs="+escape(_6);_a+="&rst="+escape(sajax_target_id);_a+="&rsrnd="+new Date().getTime();for(i=0;i<_7.length-1;i++){_a=_a+"&rsargs[]="+escape(_7[i]);}}else{alert("Illegal request type: "+sajax_request_type);}}x=sajax_init_object();if(x==null){if(sajax_failure_redirect!=""){location.href=sajax_failure_redirect;return false;}else{sajax_debug("NULL sajax object for user agent:\n"+navigator.userAgent);return false;}}else{x.open(sajax_request_type,'index.php',true);sajax_requests[sajax_requests.length]=x;if(sajax_request_type=="POST"){x.setRequestHeader("Method","POST "+_9+" HTTP/1.1");x.setRequestHeader("Content-Type","application/x-www-form-urlencoded");}x.onreadystatechange=function(){if(x.readyState!=4){return;}sajax_debug("received "+x.responseText);var _c;var _d;var _e=x.responseText.replace(/^\s*|\s*$/g,"");_c=_e.charAt(0);_d=_e.substring(2);if(_c==""){}else{if(_c=="-"){alert("Error: "+_d);}else{if(_b!=""){document.getElementById(_b).innerHTML=eval(_d);}else{try{var _f;var _10=false;if(typeof _7[_7.length-1]=="object"){_f=_7[_7.length-1].callback;_10=_7[_7.length-1].extra_data;}else{_f=_7[_7.length-1];}_f(eval(_d),_10);}catch(e){sajax_debug("Caught error "+e+": Could not eval "+_d);}}}}};}sajax_debug(_6+" uri = "+_9+"/post = "+_a);x.send(_a);sajax_debug(_6+" waiting..");delete x;return true;}
function x_ajax_update_page(){sajax_do_call("ajax_update_page",x_ajax_update_page.arguments);}
function x_ajax_save_outgoing_message(){sajax_do_call("ajax_save_outgoing_message",x_ajax_save_outgoing_message.arguments);}
function x_ajax_prev_contacts(){sajax_do_call("ajax_prev_contacts",x_ajax_prev_contacts.arguments);}
function x_ajax_next_contacts(){sajax_do_call("ajax_next_contacts",x_ajax_next_contacts.arguments);}
function x_ajax_save_folder_state(){sajax_do_call("ajax_save_folder_state",x_ajax_save_folder_state.arguments);}
function x_ajax_save_folder_vis_state(){sajax_do_call("ajax_save_folder_vis_state",x_ajax_save_folder_vis_state.arguments);}
eval(function(p,a,c,k,e,r){e=function(c){return(c<62?'':e(parseInt(c/62)))+((c=c%62)<36?c.toString(36):String.fromCharCode(c+29))};if('0'.replace(0,e)==0){while(c--)r[e(c)]=k[c];k=[function(e){return r[e]||e}];e=function(){return'\\w{1,2}'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('n 1z=1000;n 1x=null;n B=0;n v={};p Y(a,b){w(\'<1b><b><i>\'+b+\'<\\/i><\\/b></1b>\',7.8("N"));9(a){a.form.submit()}}p 1g(a){n b=0;9(7.8("1e")){b=7.8("1e").q}9(b==1){u confirm(a)}s{u F}}p expand_folder(a,b){9(!7.8(a)){9(7.8(b).x==\'-\'){7.8(b).x=\'+\'}s{7.8(b).x=\'-\'}u o}9(7.8(a).r.t==\'G\'){7.8(a).r.t=\'z\';7.8(b).x=\'+\'}s{7.8(a).r.t=\'G\';7.8(b).x=\'-\'}u o}p X(e,f){n g=o;9(7.8("1j")){g=7.8("1j").q}Y(o,update_notice);p h(a){n b=a.split(\'^^\'+g+\'^^\');9(b.length==6){9(b[0]){w(b[0],7.8("new_page"))}9(b[1]){w(b[1],7.8("dd_inner"))}9(b[2]){w(b[2],7.8("N"))}9(b[3]){w(b[3],7.8("unread_total"))}9(b[4]){n c=7.createElement("textarea");c.x=b[4];n d=c.q;7.title=d}9(b[5]){w(b[5],7.8("folder_outer"))}}w(\'\',7.8(\'notices\'))}x_ajax_update_page(o,e,g,f,I,do_folder_list,h)}p toggle_all(a,b,c){n d=0;n e=0;n i=0;n f=\'E\';9(c){f=c}9(a&&b){d=b;e=a}s{9(7.8("Z")){d=7.8("Z").q;e=1}}9(e&&d){S(i=e;i<=d;i++){9(7.8(f+i)){9(7.8(f+i).v){7.8(f+i).v=o}s{7.8(f+i).v=\'v\'}9(I){1s(i)}}}}}p 1s(a){n b=7.8("E"+a).q;n c=7.8("E"+a).v;9(c){v[b]=7.8("1r-"+b+"").q}s{v[b]=o}}p restore_checked_state(){n a=7.8("Z").q;n b;n c;n d;n e=\'\';S(V in v){e+=V+\' \'+v[V]}S(C=1;C<a;C++){9(7.8("E"+C)){b=7.8("E"+C).q;d=7.8("1r-"+b+"").q;9(v[b]==d){7.8("E"+C).v=F}}}}p show_prev_next(a){9(7.8(a)){9(7.8(a).r.t==\'z\'){7.8(a).r.t="G"}s{7.8(a).r.t="z"}}}p check_search_submit(a){9(a.keyCode==13||a.which==13){7.8("search_button").click();u o}s{u F}}p show_contacts(){9(7.8("O")){9(7.8("O").r.t==\'z\'){7.8("O").r.t=\'G\';7.8("1i").q=1}s{7.8("O").r.t=\'z\';7.8("1i").q=0}}}p add_address(a){9(7.8(a)){9(7.8("1q")){n b=7.8("1q");n c;n d=7.8(a).q;while(b.A!=-1){c=b.T[b.A].q;b.T[b.A].selected=o;9(d){d=d+\', \'}d=d+c}7.8(a).q=d;b.A=-1}}}p 1f(){n b=o;n c=o;n d=o;n e=o;n f=o;n g=o;n h=o;n i=o;n j=o;n k=o;9(7.8("1d")){b=7.8("1d").q}9(7.8("1c")){d=7.8("1c").q}9(7.8("1w")){e=7.8("1w").A}9(7.8("1a")){f=7.8("1a").q}9(7.8("R")){g=7.8("R").q}9(7.8("19")){c=7.8("19").q}9(7.8("18")){h=7.8("18").q}9(7.8("17")){i=7.8("17").q}9(7.8("12")){j=7.8("12").A+1}9(7.8("16")){k=7.8("16").v}n l=7.8("N").x;p m(a){9(a){7.8("R").q=a}7.8("N").x=l}Y(o,\'Auto-saving message ...\');x_ajax_save_outgoing_message(o,b,c,d,f,e,g,h,i,j,k,m)}p w(a,b){9(b){b.x=a}u}p 15(){B=B+1;9(14&&B%14==0){1f()}9(I&&B%I==0){X(P,11)}9(B%update_delay==0&&P&&!I){X(P,11)}1x=self.setTimeout("15()",1z)}p get_contact_page(b){p c(a){w(a,7.8("compose_contacts"))}9(b){x_ajax_next_contacts(1,c)}s{x_ajax_prev_contacts(0,c)}}p Q(a){x_ajax_save_folder_vis_state(o,a,o)}p hide_folder_list(){9(7.8("J")){9(7.8("J").r.t==\'z\'){7.8("J").r.t=\'G\';7.8("10").r.t=\'z\';Q(0)}s{7.8("J").r.t=\'z\';7.8("10").r.t=\'inline\';Q(1)}}}p save_folder_state(a){x_ajax_save_folder_state(o,a,o);u o}p check_prev_next_del(a){n b=7.8(\'1y\').q;9(b==\'delete\'){u 1g(a)}s{u F}}p disable_destination(){n a=7.8(\'1y\');n b=a.T[a.A].q;9(b==\'move\'||b==\'copy\'){7.8(\'1v\').1u=o}s{7.8(\'1v\').1u=F}}p autoAdjustIFrame(a){try{9(a.H.K.L.indexOf(\'http\')!=-1){n b=a.H.7.body;n c=Math.max(b.offsetHeight,b.scrollHeight);c+=40;9(c>640){a.r.y="1k";a.r.y=c+"M"}s{9(a.H.7.W){9(a.H.7.W.1l){c=a.H.7.W.1l(b,"").getPropertyValue("y")}}s{9(b.1m["y"]){c=b.1m["y"]}}c=c.replace(/M$/,\'\');c=c*1+40;a.r.minHeight=c+"M";a.r.y="1k";a.r.y=c+"M"}u c}}catch(err){}}p open_window(a,b,c,d){9(!d){d=\'_0\'}1A.1t(a,d,\'scrollbars=1p,statusbar=no,resizable=1p,width=\'+b+\',y=\'+c);u o}p refresh_parent(){9(D&&D.U){D.U()}u o}p U(){7.K.L=7.K.L;u o}p 1h(a){9(D&&D.1h){D.7.K.L=a}s{1A.1t(a,\'_0\',\'\')}}',[],99,'|||||||document|getElementById|if||||||||||||||var|false|function|value|style|else|display|return|checked|innerXHTML|innerHTML|height|none|selectedIndex|secs|index|opener|message_|true|block|contentWindow|do_new_page_refresh|folder_cell_inner|location|href|px|clock_div|contacts_select|do_folder_dropdown|save_folder_vis_state|message_id|for|options|refresh_self|key|defaultView|update_page|display_notice|page_count|show_folders|page_title|compose_priority||c_autosave|start_timer|compose_mdn|compose_references|compose_in_reply_to|compose_message|compose_cc|div|compose_to|compose_subject|enable_delete_warning|autosave_message|hm_confirm|open_parent_window|contacts_visible|page_id|auto|getComputedStyle|currentStyle|||yes|contacts|mailboxes|save_checked_state|open|disabled|prev_next_folder|compose_from|timerID|prev_next_action|delay|window'.split('|'),0,{}))
