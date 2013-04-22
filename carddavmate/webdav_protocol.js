/*
CardDavMATE - CardDav Web Client
Copyright (C) 2011-2012 Jan Mate <jan.mate@inf-it.com>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// VersionCheck (check for new version)
function netVersionCheck()
{
	$.ajax({
		type: 'GET',
		url: globalVersionCheckURL,
		cache: false,
		crossDomain: false,
		timeout: 30000,
		error: function(objAJAXRequest, strError){
			console.log("Error: [netVersionCheck: '"+globalVersionCheckURL+"'] code: '"+objAJAXRequest.status+"'");
			return false;
		},
		beforeSend: function(req) {
			if(globalActiveApp=='CalDavZAP')
				req.setRequestHeader('X-client', 'CalDavZAP '+globalCalDavZAPVersion+' (INF-IT CalDAV Web Client)');
			else
				req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDAV Web Client)');
		},
		contentType: 'text/xml; charset=utf-8',
		processData: true,
		data: '',
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;

			var count=0;
			if(globalActiveApp=='CalDavZAP')
				var tmp=$(xml.responseXML).find('updates').find('caldavzap');
			else
				var tmp=$(xml.responseXML).find('updates').find('carddavmate');
			var type=tmp.attr('type');
			var home=tmp.attr('homeURL');
			var version_txt=tmp.attr('version');

			if(type==undefined || type=='' || home==undefined || home=='' || version_txt==undefined || version_txt=='')
				return false;

			var version=version_txt.match(RegExp('^([0-9]+)\.([0-9]+)\.([0-9]+)(?:\.([0-9]+))?$'));
			if(version==null)
				return false;

			if(version[4]==null)
				version[4]='0';
			var version_int=parseInt(parseInt(version[1],10).pad(2)+parseInt(version[2],10).pad(2)+parseInt(version[3],10).pad(2)+parseInt(version[4],10).pad(2),10);

			if(globalActiveApp=='CalDavZAP')
				var current_version=globalCalDavZAPVersion.match(RegExp('^([0-9]+)\.([0-9]+)\.([0-9]+)(?:\.([0-9]+))?'));
			else
				var current_version=globalCardDavMATEVersion.match(RegExp('^([0-9]+)\.([0-9]+)\.([0-9]+)(?:\.([0-9]+))?'));
			if(current_version[4]==null)
				current_version[4]='0';
			var current_version_int=parseInt(parseInt(current_version[1],10).pad(2)+parseInt(current_version[2],10).pad(2)+parseInt(current_version[3],10).pad(2)+parseInt(current_version[4],10).pad(2),10);

			if(current_version_int<version_int)
			{
				var showNofication=false;

				if(globalNewVersionNotifyUsers.length==0)
					showNofication=true;
				else
				{
					for(var i=0;i<globalAccountSettings.length;i++)
						if(globalNewVersionNotifyUsers.indexOf(globalAccountSettings[i].userAuth.userName)!=-1)
						{
							showNofication=true;
							break;
						}
				}

				if(showNofication==true)
				{
					if(globalActiveApp=='CalDavZAP')
					{
						var selector='#SystemCalDAV';
						$(selector).find('div.update_h').html(localization[globalInterfaceLanguage].updateNotificationCalDAV.replace('%new_ver%', '<span id="newversion" class="update_h"></span>').replace('%curr_ver%', '<span id="version" class="update_h"></span>').replace('%url%', '<span id="homeurl" class="update_h" onclick=""></span>'));
						$(selector).find('div.update_h').find('span#version').text(globalCalDavZAPVersion);
					}
					else
					{
						var selector='#SystemCardDAV';
						$(selector).find('div.update_h').html(localization[globalInterfaceLanguage].updateNotification.replace('%new_ver%','<span id="newversion" class="update_h"></span>').replace('%curr_ver%', '<span id="version" class="update_h"></span>').replace('%url%', '<span id="homeurl" class="update_h" onclick=""></span>'));
						$(selector).find('div.update_h').find('span#version').text(globalCardDavMATEVersion);
					}

					$(selector).find('div.update_h').find('span#newversion').text(version_txt);
					$(selector).find('div.update_h').find('span#homeurl').attr('onclick','window.open(\''+home+'\')');
					$(selector).find('div.update_h').find('span#homeurl').text(home);

					setTimeout(function(){
						var orig_width=$(selector).find('div.update_d').width();
						$(selector).find('div.update_d').css('width', '0px');
						$(selector).find('div.update_d').css('display','');
						$(selector).find('div.update_d').animate({width: '+='+orig_width+'px'}, 500);
					},5000);
				}
			}
		}
	});
}

// Load the configuration from XML file
function netCheckAndCreateConfiguration(configurationURL)
{
	$.ajax({
		type: 'PROPFIND',
		url: configurationURL.href,
		cache: false,
		crossDomain: (typeof configurationURL.crossDomain=='undefined' ? true : configurationURL.crossDomain),
		xhrFields: {
			withCredentials: (typeof configurationURL.withCredentials=='undefined' ? false : configurationURL.withCredentials)
		},
		timeout: configurationURL.timeOut,
		error: function(objAJAXRequest, strError){
			console.log("Error: [netCheckAndCreateConfiguration: '"+configurationURL.href+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' - see http://www.inf-it.com/carddavmate/misc/readme_network.txt' : ''));
			$('#LoginLoader').fadeOut(1200);
			return false;
		},
		beforeSend: function(req){
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && globalLoginUsername!='' && globalLoginPassword!='')
				req.setRequestHeader('Authorization', basicAuth(globalLoginUsername,globalLoginPassword));
			if(globalActiveApp=='CalDavZAP')
				req.setRequestHeader('X-client', 'CalDavZAP '+globalCalDavZAPVersion+' (INF-IT CalDAV Web Client)');
			else
				req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDAV Web Client)');
			req.setRequestHeader('Depth', '0');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? globalLoginUsername : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? globalLoginPassword : null),
		contentType: 'text/xml; charset=utf-8',
		processData: true,
		data: '<?xml version="1.0" encoding="utf-8"?><D:propfind xmlns:D="DAV:"><D:prop><D:current-user-principal/></D:prop></D:propfind>',
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;

			var count=0;
			if($(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode('response').children().filterNsNode('propstat').children().filterNsNode('status').text().match(RegExp('200 OK$')))
			{
				if(typeof globalAccountSettings=='undefined')
					globalAccountSettings=[];

				globalAccountSettings[globalAccountSettings.length]=$.extend({}, configurationURL);
				globalAccountSettings[globalAccountSettings.length-1].type='network';
				globalAccountSettings[globalAccountSettings.length-1].href=configurationURL.href+globalLoginUsername+'/';
				globalAccountSettings[globalAccountSettings.length-1].userAuth={userName: globalLoginUsername, userPassword: globalLoginPassword};
				count++;

				if(configurationURL.additionalResources!=undefined && configurationURL.additionalResources.length>0)
				{
					for(var i=0;i<configurationURL.additionalResources.length;i++)
					{
						if(globalLoginUsername!=configurationURL.additionalResources[i])
						{
							globalAccountSettings[globalAccountSettings.length]=$.extend({}, configurationURL);
							globalAccountSettings[globalAccountSettings.length-1].type='network';
							globalAccountSettings[globalAccountSettings.length-1].href=configurationURL.href+configurationURL.additionalResources[i]+'/';
							globalAccountSettings[globalAccountSettings.length-1].userAuth={userName: globalLoginUsername, userPassword: globalLoginPassword};
							count++;
						}
					}
				}
			}

			if(count)
			{
				for(var i=0; i<globalAccountSettings.length;i++)
					if((typeof globalAccountSettings[i].delegation =='boolean' && globalAccountSettings[i].delegation) || (globalAccountSettings[i].delegation instanceof Array && globalAccountSettings[i].delegation.length>0))
						DAVresourceDelegation(globalAccountSettings[i]);
				
				// show the logout button
				if(typeof globalAvailableAppsArray!='undefined' && globalAvailableAppsArray!=null && globalAvailableAppsArray.length>1)
					$('#intLogout').parent().css('display', 'table-cell');
				else
				{
					$('#Logout').css('display', 'block');
					$('#logoutShower').css('display', 'block');
				}

				// start the client
				if(globalActiveApp == 'CalDavZAP')
					runCalDAV();
				else
					runCardDAV();
			}
			else
				$('#LoginLoader').fadeOut(1200);
		}
	});
}

// Load the configuration from XML file
function netLoadConfiguration(configurationURL)
{
	$.ajax({
		type: 'GET',
		url: configurationURL.href,
		cache: false,
		crossDomain: (typeof configurationURL.crossDomain=='undefined' ? true : configurationURL.crossDomain),
		xhrFields: {
			withCredentials: (typeof configurationURL.withCredentials=='undefined' ? false : configurationURL.withCredentials)
		},
		timeout: configurationURL.timeOut,
		error: function(objAJAXRequest, strError){
			console.log("Error: [loadConfiguration: '"+configurationURL.href+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' - see http://www.inf-it.com/carddavmate/misc/readme_network.txt' : ''));
			$('#LoginLoader').fadeOut(1200);
			return false;
		},
		beforeSend: function(req) {
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && globalLoginUsername!='' && globalLoginPassword!='')
				req.setRequestHeader('Authorization', basicAuth(globalLoginUsername,globalLoginPassword));
			if(globalActiveApp=='CalDavZAP')
				req.setRequestHeader('X-client', 'CalDavZAP '+globalCalDavZAPVersion+' (INF-IT CalDAV Web Client)');
			else
				req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDAV Web Client)');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? globalLoginUsername : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? globalLoginPassword : null),
		contentType: 'text/xml; charset=utf-8',
		processData: true,
		data: '',
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;

			if(typeof globalAccountSettings=='undefined')
				globalAccountSettings=[];

			var count=0;
			$(xml.responseXML).children('resources').children('resource').each(
				function(index, element)
				{
					if($(element).children().filterNsNode('type').children().filterNsNode('addressbook').length==1 || $(element).children().filterNsNode('type').children().filterNsNode('calendar').length==1)
					{
						var href=$(element).children('href').text();
						var tmp=$(element).children('hreflabel').text();
						var hreflabel=(tmp!='' ? tmp : null);
						var username=$(element).children('userauth').children('username').text();
						var password=$(element).children('userauth').children('password').text();
						var updateinterval=$(element).children('syncinterval').text();
						var settingsaccount=$(element).find('settingsaccount').text();
						var timeout=$(element).children('timeout').text();
						var locktimeout=$(element).children('locktimeout').text();

						var tmp=$(element).children('showheader').text();
						var showHeader=((tmp=='false' || tmp=='no' || tmp=='0') ? false : true);
						var tmp=$(element).children('withcredentials').text();
						var withcredentials=((tmp=='true' || tmp=='yes' || tmp=='1') ? true : false);
						var tmp=$(element).children('crossdomain').text();
						var crossdomain=((tmp=='false' || tmp=='no' || tmp=='0') ? false : true);

						var forcereadonly=null;
						var tmp=$(element).children('forcereadonly');
						if(tmp.text()=='true')
							var forcereadonly=true;
						else
						{
							var tmp_ro=[];
							tmp.children('collection').each(
								function(index, element)
								{
									if((matched=$(element).text().match(RegExp('^re(\\|[^:]*|):(.+)$')))!=null && matched.length==3)
										tmp_ro[tmp_ro.length]=new RegExp(matched[2], matched[1].substring(matched[1].length>0 ? 1 : 0));
									else
										tmp_ro[tmp_ro.length]=$(element).text();
								}
							);
							if(tmp_ro.length>0)
								var forcereadonly=tmp_ro;
						}

						var delegation=false;
						var tmp=$(element).children('delegation');
						if(tmp.text()=='true')
							var delegation=true;
						else
						{
							var tmp_de=[];
							tmp.children('resource').each(
								function(index, element)
								{
									if((matched=$(element).text().match(RegExp('^re(\\|[^:]*|):(.+)$')))!=null && matched.length==3)
										tmp_de[tmp_de.length]=new RegExp(matched[2], matched[1].substring(matched[1].length>0 ? 1 : 0));
									else
										tmp_de[tmp_de.length]=$(element).text();
								}
							);
							if(tmp_de.length>0)
								var delegation=tmp_de;
						}

						var ignoreAlarms=false;
						var tmp=$(element).children('ignorealarms');
						if(tmp.text()=='true')
							var ignoreAlarms=true;
						else
						{
							var tmp_ia=[];
							tmp.children('collection').each(
								function(index, element)
								{
									if((matched=$(element).text().match(RegExp('^re(\\|[^:]*|):(.+)$')))!=null && matched.length==3)
										tmp_ia[tmp_ia.length]=new RegExp(matched[2], matched[1].substring(matched[1].length>0 ? 1 : 0));
									else
										tmp_ia[tmp_ia.length]=$(element).text();
								}
							);
							if(tmp_ia.length>0)
								var ignoreAlarms=tmp_ia;
						}

						var backgroundCalendars=[];
						var tmp=$(element).children('backgroundcalendars');
						if(tmp.text()!='')
						{
							tmp.children('collection').each(
								function(index, element)
								{
									if((matched=$(element).text().match(RegExp('^re(\\|[^:]*|):(.+)$')))!=null && matched.length==3)
										backgroundCalendars[backgroundCalendars.length]=new RegExp(matched[2], matched[1].substring(matched[1].length>0 ? 1 : 0));
									else
										backgroundCalendars[backgroundCalendars.length]=$(element).text();
								}
							);
						}

						globalAccountSettings[globalAccountSettings.length]={type: 'network', href: href, hrefLabel: hreflabel, crossDomain: crossdomain, showHeader: showHeader, settingsAccount: settingsaccount, forceReadOnly: forcereadonly, withCredentials: withcredentials, userAuth: {userName: username, userPassword: password}, syncInterval: updateinterval, timeOut: timeout, lockTimeOut: locktimeout, delegation: delegation, ignoreAlarms: ignoreAlarms, backgroundCalendars: backgroundCalendars};
						count++;
					}
				}
			);

			if(count)
			{
				for(var i=0; i<globalAccountSettings.length;i++)
					if((typeof globalAccountSettings[i].delegation =='boolean' && globalAccountSettings[i].delegation) || (globalAccountSettings[i].delegation instanceof Array && globalAccountSettings[i].delegation.length>0))
						DAVresourceDelegation(globalAccountSettings[i]);
				// show the logout button
				if(typeof globalAvailableAppsArray!='undefined' && globalAvailableAppsArray!=null && globalAvailableAppsArray.length>1)
					$('#intLogout').parent().css('display', 'table-cell');
				else
				{
					$('#Logout').css('display', 'block');
					$('#logoutShower').css('display', 'block');
				}

				// start the client
				if(globalActiveApp=='CalDavZAP')
					runCalDAV();
				else
					runCardDAV();
			}
			else
				$('#LoginLoader').fadeOut(1200);
		}
	});
}

// Load the client settings (stored as DAV property on server)
function netLoadSettings(inputResource)
{
	var re=new RegExp('^(https?://)([^/]+)', 'i');
	var tmp=inputResource.href.match(re);

	var baseHref=tmp[1]+tmp[2];
	var uidBase=tmp[1]+inputResource.userAuth.userName+'@'+tmp[2];

	$.ajax({
		type: 'PROPFIND',
		url: inputResource.href,
		cache: false,
		crossDomain: (typeof inputResource.crossDomain=='undefined' ? true: inputResource.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputResource.withCredentials=='undefined' ? false: inputResource.withCredentials)
		},
		timeout: inputResource.timeOut,
		error: function(objAJAXRequest, strError){
			console.log("Error: [netLoadSettings: '"+uidBase+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' (this error code usually means network connection error, or your browser is trying to make a cross domain query, but it is not allowed by the destination server or the browser itself)': ''));
			loadSettings(JSON.stringify(globalSettings));
			return false;
		},
		beforeSend: function(req){
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && inputResource.userAuth.userName!='' && inputResource.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(inputResource.userAuth.userName, inputResource.userAuth.userPassword));

			if(globalActiveApp=='CalDavZAP')
				req.setRequestHeader('X-client', 'CalDavZAP '+globalCalDavZAPVersion+' (INF-IT CalDAV Web Client)');
			else
				req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDAV Web Client)');

			req.setRequestHeader('Depth', '0');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userPassword : null),
		contentType: 'text/xml',
		processData: true,
		data: '<?xml version="1.0" encoding="utf-8"?><A:propfind xmlns:A="DAV:"><A:prop><E:cal-settings xmlns:E="http://inf-it.com/ns/cal/"/></A:prop></A:propfind>',
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
			{
				loadSettings(JSON.stringify(globalSettings));
				return false;
			}

			var settings=$(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode('response').children().filterNsNode('propstat').children().filterNsNode('prop').children().filterNsNode('cal-settings').text();
			if(settings!='')
			{
				loadSettings(settings);
				return true;
			}
			else
				loadSettings(JSON.stringify(globalSettings));
		}
	});
}

// Save the client settings (stored as DAV property on server)
function netSaveSettings(inputResource, inputSettings)
{
	var re=new RegExp('^(https?://)([^/]+)', 'i');
	var tmp=inputResource.href.match(re);

	var baseHref=tmp[1]+tmp[2];
	var uidBase=tmp[1]+inputResource.userAuth.userName+'@'+tmp[2];

	$.ajax({
		type: 'PROPPATCH',
		url: inputResource.href,
		cache: false,
		crossDomain: (typeof inputResource.crossDomain=='undefined' ? true: inputResource.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputResource.withCredentials=='undefined' ? false: inputResource.withCredentials)
		},
		timeout: inputResource.timeOut,
		error: function(objAJAXRequest, strError){
			console.log("Error: [netSaveSettings: '"+uidBase+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' (this error code usually means network connection error, or your browser is trying to make a cross domain query, but it is not allowed by the destination server or the browser itself)': ''));
			return false;
		},
		beforeSend: function(req){
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && inputResource.userAuth.userName!='' && inputResource.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(inputResource.userAuth.userName, inputResource.userAuth.userPassword));

			if(globalActiveApp=='CalDavZAP')
				req.setRequestHeader('X-client', 'CalDavZAP '+globalCalDavZAPVersion+' (INF-IT CalDAV Web Client)');
			else
				req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDAV Web Client)');

			req.setRequestHeader('Depth', '0');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userPassword : null),
		contentType: 'text/xml',
		processData: true,
		data: '<?xml version="1.0" encoding="utf-8"?><A:propertyupdate xmlns:A="DAV:"><A:set><A:prop><E:cal-settings xmlns:E="http://inf-it.com/ns/cal/">'+inputSettings+'</E:cal-settings></A:prop></A:set></A:propertyupdate>',
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;
		}
	});
	return true;
}

function DAVresourceDelegation(inputResource)
{
	globalResourceSync=false;

	var re=new RegExp('^(https?://)([^/]+)', 'i');
	var tmp=inputResource.href.match(re);
	
	var baseHref=tmp[1]+tmp[2];
	var uidBase=tmp[1]+inputResource.userAuth.userName+'@'+tmp[2];

	$.ajax({
		type: 'REPORT',
		url: inputResource.href,
		cache: false,
		crossDomain: (typeof inputResource.crossDomain=='undefined' ? true: inputResource.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputResource.withCredentials=='undefined' ? false: inputResource.withCredentials)
		},
		timeout: inputResource.timeOut,
		error: function(objAJAXRequest, strError)
		{
			console.log("Error: [netLoadResource: '"+uidBase+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' (this error code usually means network connection error, or your browser is trying to make a cross domain query, but it is not allowed by the destination server or the browser itself)': ''));
		},
		beforeSend: function(req){
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && inputResource.userAuth.userName!='' && inputResource.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(inputResource.userAuth.userName, inputResource.userAuth.userPassword));

			if(globalActiveApp=='CalDavZAP')
				req.setRequestHeader('X-client', 'CalDavZAP '+globalCalDavZAPVersion+' (INF-IT CalDAV Web Client)');
			else
				req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDAV Web Client)');


			req.setRequestHeader('Depth', '0');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userPassword : null),
		contentType: 'text/xml',
		processData: true,
		data: '<A:expand-property xmlns:A="DAV:"><A:property name="calendar-proxy-read-for" namespace="http://calendarserver.org/ns/"><A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>'+
				'<A:property name="displayname" namespace="DAV:"/><A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>'+
				'</A:property><A:property name="calendar-proxy-write-for" namespace="http://calendarserver.org/ns/"><A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>'+
				'<A:property name="displayname" namespace="DAV:"/><A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>'+
				'</A:property>'+
				'</A:expand-property>',
		dataType: 'xml',
		complete: function(xml, textStatus){
			if(textStatus!='success')
				return false;

			var hrefArray = new Array();
			if(typeof globalAccountSettings=='undefined')
				globalAccountSettings=[];
			var hostPart = tmp[0];		
			var propElement = $(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode('response').children().filterNsNode('propstat').
			children().filterNsNode('prop');

			var searchR = new Array();
			searchR[searchR.length] = $(propElement).children().filterNsNode('calendar-proxy-read-for');
			searchR[searchR.length] = $(propElement).children().filterNsNode('calendar-proxy-write-for');
			for(var m=0;m<searchR.length; m++)
			{
				searchR[m].children().filterNsNode('response').children().filterNsNode('propstat').
				children().filterNsNode('prop').children().filterNsNode('calendar-user-address-set').each(
				function(index, element)
				{
					var href = $(element).children().filterNsNode('href');
					if(href.length > 1)
						hrefArray[hrefArray.length] = $(href[1]).text();
					var found = false;
					for(var i=0;i<globalAccountSettings.length;i++)
						if(decodeURIComponent(globalAccountSettings[i].href) == (hostPart+$(href[1]).text()))
							found = true;
					if(!found)
					{
						var enabled = false;
						if(inputResource.delegation instanceof Array && inputResource.delegation.length>0)
						{
							for(var j=0; j<inputResource.delegation.length; j++)
								if(typeof inputResource.delegation[j]=='string')
								{
									var index = ($(href[1]).text()).indexOf(inputResource.delegation[j]);
									if(index!=-1)
										if(($(href[1]).text()).length == (index+inputResource.delegation[j].length))
											enabled=true;	
								}
								else if(typeof inputResource.delegation[j]=='object')
								{
									if($(href[1]).text().match(inputResource.delegation[j]) != null)
										enabled=true;
								}
						}
						else
							enabled=true;
							
						if(enabled)
						{
							globalAccountSettings[globalAccountSettings.length]=$.extend({}, inputResource);
							globalAccountSettings[globalAccountSettings.length-1].type=inputResource.type;
							globalAccountSettings[globalAccountSettings.length-1].href=decodeURIComponent(hostPart+$(href[1]).text());
							globalAccountSettings[globalAccountSettings.length-1].userAuth = { userName : inputResource.userAuth.userName, userPassword : inputResource.userAuth.userPassword};
						}
					}
				});
			}
		}
		/*
				
		*/
	});
}
function unlockCollection(inputContactObj)
{
	var tmp=inputContactObj.uid.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)([^/]+/)([^/]*)','i'));
	var collection_uid=tmp[1]+tmp[2]+'@'+tmp[3]+tmp[4]+tmp[5];

	var lockToken=globalResourceCardDAVList.getCollectionByUID(collection_uid).lockToken;

	// resource not locked, we cannot unlock it
	if(lockToken=='undefined' || lockToken==null)
		return false;

	var put_href=tmp[1]+tmp[3]+tmp[4]+tmp[5];
	var put_href_part=tmp[4]+tmp[5];
	var resourceSettings=null;

	// find the original settings for the resource and user
	var tmp=inputContactObj.accountUID.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)','i'));
	var resource_href=tmp[1]+tmp[3]+tmp[4];
	var resource_user=tmp[2];

	for(var i=0;i<globalAccountSettings.length;i++)
		if(globalAccountSettings[i].href==resource_href && globalAccountSettings[i].userAuth.userName==resource_user)
			resourceSettings=globalAccountSettings[i];

	if(resourceSettings==null)
		return false;

	// the begin of each error message
	var errBegin=localization[globalInterfaceLanguage].errUnableUnlockBegin;

	$.ajax({
		type: 'UNLOCK',
		url: put_href,
		cache: false,
		crossDomain: (typeof resourceSettings.crossDomain=='undefined' ? true : resourceSettings.crossDomain),
		xhrFields: {
			withCredentials: (typeof resourceSettings.withCredentials=='undefined' ? false : resourceSettings.withCredentials)
		},
		timeout: resourceSettings.timeOut,
		error: function(objAJAXRequest, strError){
			switch(objAJAXRequest.status)
			{
				case 401:
					show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp401),globalHideInfoMessageAfter);
					break;
				case 403:
					show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp403),globalHideInfoMessageAfter);
					break;
				case 405:
					show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp405),globalHideInfoMessageAfter);
					break;
				case 408:
					show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp408),globalHideInfoMessageAfter);
					break;
				case 500:
					show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp500),globalHideInfoMessageAfter);
					break;
				case 501:
					show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp501),globalHideInfoMessageAfter);
					break;
				default:
					show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttpCommon.replace('%%',objAJAXRequest.status)),globalHideInfoMessageAfter);
					break;
			}
			return false;
		},
		beforeSend: function(req) {
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && resourceSettings.userAuth.userName!='' && resourceSettings.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(resourceSettings.userAuth.userName,resourceSettings.userAuth.userPassword));
			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			// req.setRequestHeader('Depth', '0');
			if(lockToken!=null)
				req.setRequestHeader('Lock-Token', '<'+lockToken+'>');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userPassword : null),
		data: '',
		complete: function(xml, textStatus)
		{
			if(textStatus=='success')
			{
				globalResourceCardDAVList.setCollectionFlagByUID(collection_uid, 'lockToken', null);
				return true;
			}
		}
	});
}

function operationPerform(inputPerformOperation, inputContactObj, inputFilterUID)
{
	if(inputPerformOperation=='PUT')
	{
		var tmp=globalAddressbookList.getAddMeToContactGroups(inputContactObj, inputFilterUID);
		var inputContactObjArr=new Array(inputContactObj);
		inputContactObjArr=inputContactObjArr.concat(tmp);

		putVcardToCollection(inputContactObjArr, inputFilterUID, 'PUT_ALL', null);
	}
	else if(inputPerformOperation=='DELETE')
	{
		var tmp=globalAddressbookList.getRemoveMeFromContactGroups(inputContactObj.uid, null);
		var inputContactObjArr=new Array(inputContactObj);
		inputContactObjArr=tmp.concat(inputContactObjArr);

		if(inputContactObjArr.length==1)
			deleteVcardFromCollection(inputContactObjArr[0], inputFilterUID, 'DELETE_LAST');
		else
			putVcardToCollection(inputContactObjArr, inputFilterUID, 'DELETE_LAST', null);
	}
	else if(inputPerformOperation=='ADD_TO_GROUP')
	{
		var tmp=globalAddressbookList.getAddMeToContactGroups(inputContactObj, [inputContactObj.addToContactGroupUID]);
		tmp[0].uiObjects=inputContactObj.uiObjects
		var inputContactObjArr=tmp;

		putVcardToCollection(inputContactObjArr, inputFilterUID, 'ADD_TO_GROUP_LAST', null);
	}
	else if(inputPerformOperation=='DELETE_FROM_GROUP')
	{
		var inputContactObjArr=globalAddressbookList.getRemoveMeFromContactGroups(inputContactObj.uid, [inputFilterUID]);
		putVcardToCollection(inputContactObjArr, inputFilterUID, 'DELETE_FROM_GROUP_LAST', null);
	}
	else if(inputPerformOperation=='IRM_DELETE')
	{
		var tmp=globalAddressbookList.getRemoveMeFromContactGroups(inputContactObj.uid, null);
		var inputContactObjArr=new Array($.extend({withoutLockTocken: true}, inputContactObj), inputContactObj);	// first is used for PUT to destination resource (without lock token) and the second for the DELETE
		inputContactObjArr=tmp.concat(inputContactObjArr);

		putVcardToCollection(inputContactObjArr, inputFilterUID, 'IRM_DELETE_LAST', null);
	}
	else if(inputPerformOperation=='MOVE')
	{
		var tmp=globalAddressbookList.getRemoveMeFromContactGroups(inputContactObj.uid, null);
		var inputContactObjArr=new Array(inputContactObj);
		inputContactObjArr=tmp.concat(inputContactObjArr);

		if(inputContactObjArr.length==1)
			moveVcardToCollection(inputContactObjArr[0], inputFilterUID);
		else
			putVcardToCollection(inputContactObjArr, inputFilterUID, 'MOVE_LAST', null);
	}
}

function operationPerformed(inputPerformOperation, inputContactObj, loadContactObj)
{
	if(inputPerformOperation=='ADD_TO_GROUP_LAST')
	{
		// success icon
		setTimeout(function(){
			var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
			resource.addClass('r_success');
			resource.removeClass('r_operate');
			setTimeout(function(){
				inputContactObj.uiObjects.contact.animate({opacity: 1}, 750);
				inputContactObj.uiObjects.contact.draggable('option', 'disabled', false);
				resource.removeClass('r_success');
				resource.droppable('option', 'disabled', false);
			},1200);
		},1000);
	}
	// contact group operation (only one contact group is changed at once)
	else if(inputPerformOperation=='DELETE_FROM_GROUP_LAST')
	{
		// success message
		var duration=show_editor_message('out','message_success',localization[globalInterfaceLanguage].succContactDeletedFromGroup,globalHideInfoMessageAfter);

		// after the success message show the next automatically selected contact
		setTimeout(function(){
			$('#ResourceCardDAVListOverlay').fadeOut(globalEditorFadeAnimation);
			$('#ABListOverlay').fadeOut(globalEditorFadeAnimation,function(){});
			$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});
		},duration);
	}
	// contact is added but it is hidden due to search filter
	else if($('#ABList div[data-id="'+jqueryEscapeSelector(inputContactObj.uid)+'"]').hasClass('search_hide'))
	{
		// load the modified contact
		globalAddressbookList.loadContactByUID(loadContactObj.uid);

		// success message
		var duration=show_editor_message('out','message_success',localization[globalInterfaceLanguage].succContactSaved,globalHideInfoMessageAfter);

		// after the success message show the next automatically selected contact
		setTimeout(function(){
			$('#ResourceCardDAVListOverlay').fadeOut(globalEditorFadeAnimation);
			$('#ABListOverlay').fadeOut(globalEditorFadeAnimation,function(){});
			$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});
		},duration+globalHideInfoMessageAfter);
	}
	else
	{
		// load the modified contact
		globalAddressbookList.loadContactByUID(loadContactObj.uid);

		// success message
		show_editor_message('in','message_success',localization[globalInterfaceLanguage].succContactSaved,globalHideInfoMessageAfter);

		// presunut do jednej funkcie s tym co je vyssie
		$('#ResourceCardDAVListOverlay').fadeOut(globalEditorFadeAnimation);
		$('#ABListOverlay').fadeOut(globalEditorFadeAnimation);
		$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});
	}

	unlockCollection(inputContactObj);
}

function lockAndPerformToCollection(inputContactObj, inputFilterUID, inputPerformOperation)
{
	var tmp=inputContactObj.uid.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)([^/]+/)([^/]*)','i'));
	var collection_uid=tmp[1]+tmp[2]+'@'+tmp[3]+tmp[4]+tmp[5];

	// If locking is unsupported or disabled we don't try to LOCK the collection
	if(globalResourceCardDAVList.getCollectionByUID(collection_uid).disableLocking)
	{
		// perform the operation without locking
		operationPerform(inputPerformOperation, inputContactObj, inputFilterUID);
		return true;
	}

	var put_href=tmp[1]+tmp[3]+tmp[4]+tmp[5];
	var put_href_part=tmp[4]+tmp[5];
	var resourceSettings=null;

	// find the original settings for the resource and user
	var tmp=inputContactObj.accountUID.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)','i'));
	var resource_href=tmp[1]+tmp[3]+tmp[4];
	var resource_user=tmp[2];

	for(var i=0;i<globalAccountSettings.length;i++)
		if(globalAccountSettings[i].href==resource_href && globalAccountSettings[i].userAuth.userName==resource_user)
			resourceSettings=globalAccountSettings[i];

	if(resourceSettings==null)
		return false;

	// the begin of each error message
	var errBegin=localization[globalInterfaceLanguage].errUnableLockBegin;

	$.ajax({
		type: 'LOCK',
		url: put_href,
		cache: false,
		crossDomain: (typeof resourceSettings.crossDomain=='undefined' ? true : resourceSettings.crossDomain),
		xhrFields: {
			withCredentials: (typeof resourceSettings.withCredentials=='undefined' ? false : resourceSettings.withCredentials)
		},
		timeout: resourceSettings.timeOut,
		error: function(objAJAXRequest, strError)
		{
			// if we tried to LOCK the collection but the server not supports this request we perform
			//  the operation without LOCK (even if it is dangerous and can cause data integrity errors)
			if(objAJAXRequest.status==501)
				operationPerform(inputPerformOperation, inputContactObj, inputFilterUID);
			// if the operation type is 'MOVE' we cannot show error messages, error icon is used instead
			else if(inputPerformOperation!='MOVE')
			{
				switch(objAJAXRequest.status)
				{
					case 401:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp401),globalHideInfoMessageAfter);
						break;
					case 403:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp403),globalHideInfoMessageAfter);
						break;
					case 405:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp405),globalHideInfoMessageAfter);
						break;
					case 408:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp408),globalHideInfoMessageAfter);
						break;
					case 500:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp500),globalHideInfoMessageAfter);
						break;
					default:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttpCommon.replace('%%',objAJAXRequest.status)),globalHideInfoMessageAfter);
						break;
				}

				// error icon
				setTimeout(function(){
					var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
					resource.addClass('r_error');
					resource.removeClass('r_operate');
					setTimeout(function(){
						inputContactObj.uiObjects.contact.animate({opacity: 1}, 1000);
						inputContactObj.uiObjects.contact.draggable('option', 'disabled', false);
						resource.removeClass('r_error');
						resource.droppable('option', 'disabled', false);
					},globalHideInfoMessageAfter);
				},globalHideInfoMessageAfter/10);
			}
			$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});

			return false;
		},
		beforeSend: function(req)
		{
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && resourceSettings.userAuth.userName!='' && resourceSettings.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(resourceSettings.userAuth.userName,resourceSettings.userAuth.userPassword));
			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			req.setRequestHeader('Depth', '0');
			// we support only one contact group at once + the contact + reserve :)
			req.setRequestHeader('Timeout', 'Second-'+Math.ceil((resourceSettings.lockTimeOut!=undefined ? resourceSettings.lockTimeOut : 10000)/1000));
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userPassword : null),
		contentType: 'text/xml; charset=utf-8',
		processData: false,
		data: '<?xml version="1.0" encoding="utf-8"?><D:lockinfo xmlns:D=\'DAV:\'><D:lockscope><D:exclusive/></D:lockscope><D:locktype><D:write/></D:locktype><D:owner><D:href>'+escape(collection_uid)+'</D:href></D:owner></D:lockinfo>',
		dataType: 'text',
		complete: function(xml, textStatus)
		{
			if(textStatus=='success')
			{
				var lockToken=$(xml.responseXML).children().filterNsNode('prop').children().filterNsNode('lockdiscovery').children().filterNsNode('activelock').children().filterNsNode('locktoken').children().filterNsNode('href').text();
				globalResourceCardDAVList.setCollectionFlagByUID(collection_uid, 'lockToken', (lockToken=='' ? null : lockToken));

				// We have a lock!
				if(lockToken!='')
				{
					// synchronously reload the contact changes (get the latest version of contact group vcards)
					var collection=globalResourceCardDAVList.getCollectionByUID(collection_uid);
					collection.filterUID=inputFilterUID;

					CardDAVnetLoadCollection(collection, false, false, {call: 'operationPerform', args: {performOperation: inputPerformOperation, contactObj: inputContactObj, filterUID: inputFilterUID}});
					return true;
				}
				else
				{
					// We assume that empty lockToken means 423 Resource Locked error
					show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errResourceLocked),globalHideInfoMessageAfter);

					// error icon
					setTimeout(function(){
						var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
						resource.addClass('r_error');
						resource.removeClass('r_operate');
						setTimeout(function(){
							inputContactObj.uiObjects.contact.animate({opacity: 1}, 1000);
							inputContactObj.uiObjects.contact.draggable('option', 'disabled', false);
							resource.removeClass('r_error');
							resource.droppable('option', 'disabled', false);
						},globalHideInfoMessageAfter);
					},globalHideInfoMessageAfter/10);

					$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});
				}
			}
			return false;
		}
	});
}

function putVcardToCollectionMain(inputContactObj, inputFilterUID)
{
	if(inputContactObj.etag=='')
	{
		if(inputFilterUID[inputFilterUID.length-1]!='/')	// new contact with vCard group (we must use locking)
		{
			lockAndPerformToCollection(inputContactObj, inputFilterUID, 'PUT');
		}
		else	// new contact without vCard group (no locking required)
			putVcardToCollection(inputContactObj, inputFilterUID, 'PUT_ALL', null);
	}
	else	// existing contact modification (there is no support for contact group modification -> no locking required)
		putVcardToCollection(inputContactObj, inputFilterUID, 'PUT_ALL', null);
}

function putVcardToCollection(inputContactObjArr, inputFilterUID, recursiveMode, loadContactWithUID)
{
	if(!(inputContactObjArr instanceof Array))
		inputContactObjArr=[inputContactObjArr];

	var inputContactObj=inputContactObjArr.splice(0,1);
	inputContactObj=inputContactObj[0];

	// drag & drop inter-resoruce move (we need to change the object parameters)
	if(inputContactObj.newAccountUID!=undefined && inputContactObj.newUid!=undefined)
	{
		inputContactObj.accountUID=inputContactObj.newAccountUID;
		inputContactObj.uid=inputContactObj.newUid;
		inputContactObj.etag='';
	}

	var tmp=inputContactObj.uid.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)([^/]+/)([^/]*)','i'));

	var collection_uid=tmp[1]+tmp[2]+'@'+tmp[3]+tmp[4]+tmp[5];
	var lockToken=globalResourceCardDAVList.getCollectionByUID(collection_uid).lockToken;

	// if inputContactObj.etag is empty, we have a newly created contact and need to create a .vcf file name for it
	if(inputContactObj.etag!='')	// existing contact
	{
		var put_href=tmp[1]+tmp[3]+tmp[4]+tmp[5]+tmp[6];
		var put_href_part=tmp[4]+tmp[5]+tmp[6];
	}
	else	// new contact
	{
		var vcardFile=hex_sha256(inputContactObj.vcard+(new Date().getTime()))+'.vcf';
		var put_href=tmp[1]+tmp[3]+tmp[4]+tmp[5]+vcardFile;
		var put_href_part=tmp[4]+tmp[5]+vcardFile;
		inputContactObj.uid+=vcardFile;
	}

	if(loadContactWithUID==null)	// store the first contact (it will be reloaded and marked as active)
		loadContactWithUID=inputContactObj;

	var resourceSettings=null;

	// find the original settings for the resource and user
	var tmp=inputContactObj.accountUID.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)','i'));
	var resource_href=tmp[1]+tmp[3]+tmp[4];
	var resource_user=tmp[2];

	for(var i=0;i<globalAccountSettings.length;i++)
		if(globalAccountSettings[i].href==resource_href && globalAccountSettings[i].userAuth.userName==resource_user)
			resourceSettings=globalAccountSettings[i];

	if(resourceSettings==null)
		return false;

	// the begin of each error message
	var errBegin=localization[globalInterfaceLanguage].errUnableSaveBegin;

	var vcardList= new Array();
	$.ajax({
		type: 'PUT',
		url: put_href,
		cache: false,
		crossDomain: (typeof resourceSettings.crossDomain=='undefined' ? true : resourceSettings.crossDomain),
		xhrFields: {
			withCredentials: (typeof resourceSettings.withCredentials=='undefined' ? false : resourceSettings.withCredentials)
		},
		timeout: resourceSettings.timeOut,
		error: function(objAJAXRequest, strError)
		{
			if(recursiveMode=='MOVE_LAST' || recursiveMode=='IRM_DELETE_LAST' || recursiveMode=='ADD_TO_GROUP_LAST')
			{
				// error icon
				setTimeout(function(){
					var moveContactObj=inputContactObjArr[inputContactObjArr.length-1];
					var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(moveContactObj.uiObjects.resource)+'"]');
					resource.addClass('r_error');
					resource.removeClass('r_operate');
					setTimeout(function(){
						moveContactObj.uiObjects.contact.animate({opacity: 1}, 1000);
						moveContactObj.uiObjects.contact.draggable('option', 'disabled', false);
						resource.removeClass('r_error');
						resource.droppable('option', 'disabled', false);
					},1200);
				},1000);
			}
			else
			{
				switch(objAJAXRequest.status)
				{
					case 401:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp401),globalHideInfoMessageAfter);
						break;
					case 403:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp403),globalHideInfoMessageAfter);
						break;
					case 405:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp405),globalHideInfoMessageAfter);
						break;
					case 408:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp408),globalHideInfoMessageAfter);
						break;
					case 412:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp412),globalHideInfoMessageAfter);
						break;
					case 500:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp500),globalHideInfoMessageAfter);
						break;
					default:
						show_editor_message('in','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttpCommon.replace('%%',objAJAXRequest.status)),globalHideInfoMessageAfter);
						break;
				}
			}

			// presunut do jednej funkcie s tym co je nizsie pri success
			//$('#ResourceCardDAVListOverlay').fadeOut(1200);
			//$('#ABListOverlay').fadeOut(1200);
			$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});

			unlockCollection(inputContactObj);
			return false;
		},
		beforeSend: function(req)
		{
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && resourceSettings.userAuth.userName!='' && resourceSettings.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(resourceSettings.userAuth.userName,resourceSettings.userAuth.userPassword));
			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			if(lockToken!=null && inputContactObj.withoutLockTocken!=true)
				req.setRequestHeader('Lock-Token', '<'+lockToken+'>');
			if(inputContactObj.etag!='')
				req.setRequestHeader('If-Match', inputContactObj.etag);
			else	// adding new contact
				req.setRequestHeader('If-None-Match', '*');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userPassword : null),
		contentType: 'text/vcard; charset=utf-8',
		processData: true,
		data: inputContactObj.vcard,
		dataType: 'text',
		complete: function(xml, textStatus)
		{
			if(textStatus=='success')
			{
				if(inputContactObjArr.length==1 && (recursiveMode=='DELETE_LAST' || recursiveMode=='IRM_DELETE_LAST'))
				{
					deleteVcardFromCollection(inputContactObjArr[0], inputFilterUID, recursiveMode);
					return true;
				}
				else if(inputContactObjArr.length==1 && recursiveMode=='MOVE_LAST')
				{
					moveVcardToCollection(inputContactObjArr[0], inputFilterUID);
					return true;
				}
				var newEtag=xml.getResponseHeader('Etag');
				// We get the Etag from the PUT response header instead of new collection sync (if the server supports this feature)
				if(newEtag!=undefined && newEtag!=null && newEtag!='')
				{
					// do not remove the contact group from the interface (if removed there are many GUI animation inconsistencies)
					if(!globalAddressbookList.isContactGroup(inputContactObj.vcard))
						globalAddressbookList.removeContact(inputContactObj.uid,false);

					var vcard=normalizeVcard(inputContactObj.vcard);
					var categories='';
					if((vcard_element=vcard.match(vCard.pre['contentline_CATEGORIES']))!=null)
					{
						// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
						parsed=vcard_element[0].match(vCard.pre['contentline_parse']);
						categories=parsed[4];
					}

					globalAddressbookList.insertContact({timestamp: new Date().getTime(), accountUID: inputContactObj.accountUID, uid: inputContactObj.uid, etag: newEtag, vcard: vcard, categories: categories, normalized: true}, true);
					globalQs.cache();	// update the active search

					globalAddressbookList.applyABFilter(inputFilterUID, recursiveMode=='DELETE_FROM_GROUP_LAST' || $('#ABList div[data-id="'+jqueryEscapeSelector(inputContactObj.uid)+'"]').hasClass('search_hide') ? true : false);
				}
				else	// otherwise mark collection for full sync
					globalResourceCardDAVList.setCollectionFlagByUID(collection_uid, 'forceSync', true);

				if(inputContactObjArr.length>0)
					putVcardToCollection(inputContactObjArr, inputFilterUID, recursiveMode, loadContactWithUID);
				else
				{
					var collection=globalResourceCardDAVList.getCollectionByUID(collection_uid);
					if(collection.forceSync===true)
					{
						globalResourceCardDAVList.setCollectionFlagByUID(collection_uid, 'forceSync', false);
						collection.filterUID=inputFilterUID;

						// for DELETE_FROM_GROUP_LAST we need to force reload the contact (because the editor is in "edit" state = the contact is not loaded automatically)
						CardDAVnetLoadCollection(collection, false, recursiveMode=='DELETE_FROM_GROUP_LAST' ? true : false, {call: 'operationPerformed', args: {mode: recursiveMode, contactObj: inputContactObj, loadContact: loadContactWithUID}});
						return true;
					}
					operationPerformed(recursiveMode, inputContactObj, loadContactWithUID);
				}
				return true;
			}
			else
				unlockCollection(inputContactObj);
		}
	});
}

function moveVcardToCollection(inputContactObj, inputFilterUID)
{
	var tmp=inputContactObj.uid.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)([^/]+/)([^/]*)','i'));
	var collection_uid=tmp[1]+tmp[2]+'@'+tmp[3]+tmp[4]+tmp[5];
	var lockToken=globalResourceCardDAVList.getCollectionByUID(collection_uid).lockToken;

	var put_href=tmp[1]+tmp[3]+tmp[4]+tmp[5]+tmp[6];
	var put_href_part=tmp[4]+tmp[5]+tmp[6];

	var resourceSettings=null;

	// find the original settings for the resource and user
	var tmp=inputContactObj.accountUID.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)','i'));
	var resource_href=tmp[1]+tmp[3]+tmp[4];
	var resource_user=tmp[2];

	for(var i=0;i<globalAccountSettings.length;i++)
		if(globalAccountSettings[i].href==resource_href && globalAccountSettings[i].userAuth.userName==resource_user)
			resourceSettings=globalAccountSettings[i];

	if(resourceSettings==null)
		return false;

	var vcardList= new Array();

	$.ajax({
		type: 'MOVE',
		url: put_href,
		cache: false,
		crossDomain: (typeof resourceSettings.crossDomain=='undefined' ? true : resourceSettings.crossDomain),
		xhrFields: {
			withCredentials: (typeof resourceSettings.withCredentials=='undefined' ? false : resourceSettings.withCredentials)
		},
		timeout: resourceSettings.timeOut,
		error: function(objAJAXRequest, strError)
		{
			// error icon
			setTimeout(function(){
				var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
				resource.addClass('r_error');
				resource.removeClass('r_operate');
				setTimeout(function(){
					inputContactObj.uiObjects.contact.animate({opacity: 1}, 1000);
					inputContactObj.uiObjects.contact.draggable('option', 'disabled', false);
					resource.removeClass('r_error');
					resource.droppable('option', 'disabled', false);
				},1200);
			},1000);

			unlockCollection(inputContactObj);
		},
		beforeSend: function(req)
		{
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && resourceSettings.userAuth.userName!='' && resourceSettings.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(resourceSettings.userAuth.userName,resourceSettings.userAuth.userPassword));
			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			if(lockToken!=null)
				req.setRequestHeader('Lock-Token', '<'+lockToken+'>');
			req.setRequestHeader('Destination', inputContactObj.moveDest);
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userPassword : null),
		data: '',
		complete: function(text,textStatus)
		{
			if(textStatus=='success')
			{
				// success icon
				setTimeout(function(){
					// move is successfull we can remove the contact (no sync required)
					globalAddressbookList.removeContact(inputContactObj.uid,true);
					globalAddressbookList.applyABFilter(inputFilterUID, $('#ABList div[data-id="'+jqueryEscapeSelector(inputContactObj.uid)+'"]').hasClass('search_hide') ? true : false);

					var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
					resource.addClass('r_success');
					resource.removeClass('r_operate');
					setTimeout(function(){
						resource.removeClass('r_success');
						resource.droppable('option', 'disabled', false);
					},1200);
				},1000);
			}

			unlockCollection(inputContactObj);

			// if the destination addressbook is already loaded re-sync it (to get the moved contact immediately)
			var collection=globalResourceCardDAVList.getCollectionByUID(inputContactObj.moveDestUID);
			if(collection.loaded==true)
				CardDAVnetLoadCollection(collection, false, false, null);
		}
	});
}

function deleteVcardFromCollection(inputContactObj, inputFilterUID, recursiveMode)
{
	var tmp=inputContactObj.uid.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)([^/]+/)([^/]*)','i'));

	var collection_uid=tmp[1]+tmp[2]+'@'+tmp[3]+tmp[4]+tmp[5];
	var lockToken=globalResourceCardDAVList.getCollectionByUID(collection_uid).lockToken;
	var put_href=tmp[1]+tmp[3]+tmp[4]+tmp[5]+tmp[6];
	var resourceSettings=null;

	// find the original settings for the resource and user
	var tmp=inputContactObj.accountUID.match(RegExp('^(https?://)([^@/]+(?:@[^@/]+)?)@([^/]+)(.*/)','i'));
	var resource_href=tmp[1]+tmp[3]+tmp[4];
	var resource_user=tmp[2];

	for(var i=0;i<globalAccountSettings.length;i++)
		if(globalAccountSettings[i].href==resource_href && globalAccountSettings[i].userAuth.userName==resource_user)
			resourceSettings=globalAccountSettings[i];

	if(resourceSettings==null)
		return false;

	// the begin of each error message
	var errBegin=localization[globalInterfaceLanguage].errUnableDeleteBegin;

	$.ajax({
		type: 'DELETE',
		url: put_href,
		cache: false,
		crossDomain: (typeof resourceSettings.crossDomain=='undefined' ? true : resourceSettings.crossDomain),
		xhrFields: {
			withCredentials: (typeof resourceSettings.withCredentials=='undefined' ? false : resourceSettings.withCredentials)
		},
		timeout: resourceSettings.timeOut,
		error: function(objAJAXRequest, strError)
		{
			// if the DELETE is performed as a part of inter-resource move operation (drag&drop)
			if(recursiveMode=='IRM_DELETE_LAST')
			{
				// error icon
				setTimeout(function(){
					var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
					resource.addClass('r_error');
					resource.removeClass('r_operate');
					setTimeout(function(){
						inputContactObj.uiObjects.contact.animate({opacity: 1}, 1000);
						inputContactObj.uiObjects.contact.draggable('option', 'disabled', false);
						resource.removeClass('r_error');
						resource.droppable('option', 'disabled', false);
					},1200);
				},1000);
			}
			else
			{
				switch(objAJAXRequest.status)
				{
					case 401:
						show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp401),globalHideInfoMessageAfter);
						break;
					case 403:
						show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp403),globalHideInfoMessageAfter);
						break;
					case 405:
						show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp405),globalHideInfoMessageAfter);
						break;
					case 408:
						show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp408),globalHideInfoMessageAfter);
						break;
					case 410:
						show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp410),globalHideInfoMessageAfter);
						break;
					case 500:
						show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttp500),globalHideInfoMessageAfter);
						break;
					default:
						show_editor_message('out','message_error',errBegin.replace('%%',localization[globalInterfaceLanguage].errHttpCommon.replace('%%',objAJAXRequest.status)),globalHideInfoMessageAfter);
						break;
				}
			}

			// presunut do jednej funkcie s tym co je nizsie pri success
			$('#ResourceCardDAVListOverlay').fadeOut(globalEditorFadeAnimation);
			$('#ABListOverlay').fadeOut(globalEditorFadeAnimation);
			$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});

			unlockCollection(inputContactObj);
		},
		beforeSend: function(req) {
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && resourceSettings.userAuth.userName!='' && resourceSettings.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(resourceSettings.userAuth.userName,resourceSettings.userAuth.userPassword));
			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			if(lockToken!=null)
				req.setRequestHeader('Lock-Token', '<'+lockToken+'>');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? resourceSettings.userAuth.userPassword : null),
		data: '',
		complete: function(text,textStatus)
		{
			if(textStatus=='success')
			{
				if(recursiveMode=='IRM_DELETE_LAST')
				{
					// success icon
					setTimeout(function(){
						// move is successfull we can remove the contact (no sync required)
						globalAddressbookList.removeContact(inputContactObj.uid,true);

						var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
						resource.addClass('r_success');
						resource.removeClass('r_operate');
						setTimeout(function(){
							resource.removeClass('r_success');
							resource.droppable('option', 'disabled', false);
						},1200);
					},1000);
				}
				else
				{
					// success message
					var duration=show_editor_message('out','message_success',localization[globalInterfaceLanguage].succContactDeleted,globalHideInfoMessageAfter);
					globalAddressbookList.removeContact(inputContactObj.uid,true);
					globalAddressbookList.applyABFilter(inputFilterUID, $('#ABList div[data-id="'+jqueryEscapeSelector(inputContactObj.uid)+'"]').hasClass('search_hide') ? true : false);

					// after the success message show the next automatically selected contact
					setTimeout(function(){
						// presunut do jednej funkcie s tym co je vyssie
						$('#ResourceCardDAVListOverlay').fadeOut(globalEditorFadeAnimation);
						$('#ABListOverlay').fadeOut(globalEditorFadeAnimation);
						$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});
					},duration);
				}
			}
			unlockCollection(inputContactObj);

			// if the destination addressbook is already loaded re-sync it (to get the moved contact immediately)
			if(recursiveMode=='IRM_DELETE_LAST')
			{
				var collection=globalResourceCardDAVList.getCollectionByUID(inputContactObj.newUid);
				if(collection.loaded==true)
					CardDAVnetLoadCollection(collection, false, false, null);
			}
		}
	});
}

/*
iCloud auth (without this we have no access to iCloud photos)

function netiCloudAuth(inputResource)
{
	var re=new RegExp('^(https?://)([^/]+)','i');
	var tmp=inputResource.href.match(re);

	var uidBase=tmp[1]+inputResource.userAuth.userName+'@'+tmp[2];

	$.ajax({
		type: 'POST',
		url: 'https://setup.icloud.com/setup/ws/1/login',
		cache: false,
		crossDomain: (typeof inputResource.crossDomain=='undefined' ? true : inputResource.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputResource.withCredentials=='undefined' ? false : inputResource.withCredentials)
		},
		timeout: inputResource.timeOut,
		error: function(objAJAXRequest, strError){
			console.log("Error: [netiCloudAuth: '"+uidBase+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' - see http://www.inf-it.com/carddavmate/misc/readme_network.txt' : ''));
			return false;
		},
		beforeSend: function(req) {
			req.setRequestHeader('Origin', 'https://www.icloud.com');
		},
		contentType: 'text/plain',
		processData: false,
		data: '{"apple_id":"'+inputResource.userAuth.userName+'","password":"'+inputResource.userAuth.userPassword+'","extended_login":false}',
		complete: function(xml, textStatus)
		{
			// iCloud cookie not set (no photo access)
			if(textStatus!='success')
				return false;
		}
	});
}
*/

/*
Permissions (from the DAViCal wiki):
	all - aggregate of all permissions
	read - grants basic read access to the principal or collection.
	unlock - grants access to write content (i.e. update data) to the collection, or collections of the principal.
	read-acl - grants access to read ACLs on the collection, or collections of the principal.
	read-current-user-privilege-set - grants access to read the current user's privileges on the collection, or collections of the write-acl - grants access to writing ACLs on the collection, or collections of the principal.
	write - aggregate of write-properties, write-content, bind & unbind
	write-properties - Grants access to update properties of the principal or collection. In DAViCal, when granted to a user principal, this will only grant access to update properties of the principal's collections and not the user principal itself. When granted to a group or resource principal this will grant access to update the principal properties.
	write-content - grants access to write content (i.e. update data) to the collection, or collections of the principal.
	bind - grants access to creating resources in the collection, or in collections of the principal. Created resources may be new collections, although it is an error to create collections within calendar collections.
	unbind - grants access to deleting resources (including collections) from the collection, or from collections of the principal.
*/

function CardDAVnetFindResource(inputResource, inputResourceIndex)
{
	var re=new RegExp('^(https?://)([^/]+)','i');
	var tmp=inputResource.href.match(re);

	var baseHref=tmp[1]+tmp[2];
	var uidBase=tmp[1]+inputResource.userAuth.userName+'@'+tmp[2];

	$.ajax({
		type: 'PROPFIND',
		url: inputResource.href,
		cache: false,
		crossDomain: (typeof inputResource.crossDomain=='undefined' ? true : inputResource.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputResource.withCredentials=='undefined' ? false : inputResource.withCredentials)
		},
		timeout: inputResource.timeOut,
		error: function(objAJAXRequest, strError){
			console.log("Error: [CardDAVnetFindResource: '"+uidBase+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' - see http://www.inf-it.com/carddavmate/misc/readme_network.txt' : ''));
			return false;
		},
		beforeSend: function(req) {
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && inputResource.userAuth.userName!='' && inputResource.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(inputResource.userAuth.userName,inputResource.userAuth.userPassword));

			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			req.setRequestHeader('Depth', '0');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userPassword : null),
		contentType: 'text/xml; charset=utf-8',
		processData: true,
		data: '<?xml version="1.0" encoding="utf-8"?><A:propfind xmlns:A="DAV:"><A:prop><B:addressbook-home-set xmlns:B="urn:ietf:params:xml:ns:carddav"/></A:prop></A:propfind>',
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;

// enable for iCloud cookie (to get "remote" photos from the iCloud server)
//			netiCloudAuth(inputResource);

			var response=$(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode('response');

			var addressbook_home=response.children().filterNsNode('propstat').children().filterNsNode('prop').children().filterNsNode('addressbook-home-set').children().filterNsNode('href').text();

			if(addressbook_home=='')	// addressbook-home-set has no 'href' value -> SabreDav
				addressbook_home=response.children().filterNsNode('href').text().replace('/principals/users/caldav.php','/caldav.php');

			if(addressbook_home.match(RegExp('^https?://','i'))!=null)	// absolute URL returned
				inputResource.abhref=addressbook_home;
			else	// relative URL returned
				inputResource.abhref=baseHref+addressbook_home;

			CardDAVnetLoadABResource(inputResource, inputResourceIndex);
		}
	});
}

function CardDAVnetLoadABResource(inputResource, inputResourceIndex)
{
	var re=new RegExp('^(https?://)([^/]+)','i');

	var tmp=inputResource.abhref.match(re);
	var baseHref=tmp[1]+tmp[2];
	var uidBase=tmp[1]+inputResource.userAuth.userName+'@'+tmp[2];

	var tmp=inputResource.href.match(RegExp('^(https?://)(.*)','i'));
	var origUID=tmp[1]+inputResource.userAuth.userName+'@'+tmp[2];

	$.ajax({
		type: 'PROPFIND',
		url: inputResource.abhref,
		cache: false,
		crossDomain: (typeof inputResource.crossDomain=='undefined' ? true : inputResource.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputResource.withCredentials=='undefined' ? false : inputResource.withCredentials)
		},
		timeout: inputResource.timeOut,
		error: function(objAJAXRequest, strError){
			console.log("Error: [CardDAVnetLoadABResource: '"+uidBase+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' - see http://www.inf-it.com/carddavmate/misc/readme_network.txt' : ''));
			return false;
		},
		beforeSend: function(req) {
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && inputResource.userAuth.userName!='' && inputResource.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(inputResource.userAuth.userName,inputResource.userAuth.userPassword));

			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			req.setRequestHeader('Depth', '1');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputResource.userAuth.userPassword : null),
		contentType: 'text/xml; charset=utf-8',
		processData: true,
		data: '<?xml version="1.0" encoding="utf-8"?><A:propfind xmlns:A="DAV:"><A:prop><A:current-user-privilege-set/><A:displayname/><A:resourcetype/><A:sync-token/><B:max-image-size xmlns:B="urn:ietf:params:xml:ns:carddav"/></A:prop></A:propfind>',
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;

			var resultTimestamp = new Date().getTime();

			// Check for DAV compliance classes (note: we do not make separate OPTIONS request)
			//  if DAV header is returned and there is no "2" then we completely disable locking (otherwise we enable it)
			//  if DAV header not present we TRY to use locking and if it fails we silently continue without it
			var disableLocking=false;
			var davHeader=xml.getResponseHeader('DAV');
			if(davHeader!=undefined && davHeader!='')
			{
				var davHeaderArr=davHeader.replace(RegExp(' ','g'),'').split(',');
				if(davHeaderArr.indexOf('1')!=-1 && davHeaderArr.indexOf('2')==-1)
					disableLocking=true;
			}

			$(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode('response').each(
				function(index, element)
				{
					$(element).children().filterNsNode('propstat').each(
						function(pindex, pelement)
						{
							var resources=$(pelement).children().filterNsNode('prop');
							// if resource is addressbook and collection (RFC requirement for CardDav)
							if(resources.children().filterNsNode('resourcetype').children().filterNsNode('addressbook').length==1 && resources.children().filterNsNode('resourcetype').children().filterNsNode('collection').length==1)
							{
								var permissions=new Array();
								resources.children().filterNsNode('current-user-privilege-set').children().filterNsNode('privilege').each(
									function(index, element)
									{
										$(element).children().each(
											function(index, element)
											{
												permissions[permissions.length]=$(element).prop('tagName').replace(/^[^:]+:/,'');
											}
										);
									}
								);

								var href=$(element).children().filterNsNode('href').text();
								var tmp_cn=href.match(RegExp('/([^/]+)/?$'));	// collection name

								var read_only=false;
								if((permissions.length>0 && permissions.indexOf('all')==-1 && permissions.indexOf('write')==-1 &&  permissions.indexOf('write-content')==-1) || (inputResource.forceReadOnly!=undefined && (inputResource.forceReadOnly==true || inputResource.forceReadOnly instanceof Array && inputResource.forceReadOnly.indexOf(tmp_cn[1])!=-1)))
									read_only=true;

								var displayvalue=resources.children().filterNsNode('displayname').text();
								var synctoken=resources.children().filterNsNode('sync-token').text();

								var tmp_dv=href.match(RegExp('.*/([^/]+)/$','i'));
								if(displayvalue=='')	// OS X Server
									displayvalue=tmp_dv[1];

								var checkContentType=(inputResource.checkContentType==undefined ? true : inputResource.checkContentType);

								// insert the resource
								globalResourceCardDAVList.insertResource({timestamp: resultTimestamp, uid: uidBase+href, timeOut: inputResource.timeOut, displayvalue: displayvalue, userAuth: inputResource.userAuth, url: baseHref, accountUID: origUID, href: href, hrefLabel: inputResource.hrefLabel, permissions: {full: permissions, read_only: read_only}, crossDomain: inputResource.crossDomain, withCredentials: inputResource.withCredentials, checkContentType: checkContentType, disableLocking: disableLocking, syncToken: synctoken}, inputResourceIndex);
							}
						}
					);
				}
			);

			// remove deleted resources
			globalResourceCardDAVList.removeOldResources(inputResource.href, resultTimestamp);

			// by default load the first resource
			globalResourceCardDAVList.loadFirstAddressbook();
		}
	});
}

function CardDAVnetLoadCollection(inputCollection, forceLoad, forceLoadNextContact, innerOperationData)
{
	if(inputCollection.forceSyncPROPFIND!=undefined && inputCollection.forceSyncPROPFIND==true)
		var requestText='<?xml version="1.0" encoding="utf-8"?><A:propfind xmlns:A="DAV:"><A:prop><A:getetag/><A:getcontenttype/></A:prop></A:propfind>';
	else	// if inputCollection.forceSyncPROPFIND is undefined or false
		var requestText='<?xml version="1.0" encoding="utf-8"?><A:sync-collection xmlns:A="DAV:">'+(forceLoad==true || inputCollection.syncToken==undefined || inputCollection.syncToken=='' ? '<A:sync-token/>' : '<A:sync-token>'+inputCollection.syncToken+'</A:sync-token>')+ '<A:sync-level>1</A:sync-level><A:prop><A:getetag/><A:getcontenttype/></A:prop></A:sync-collection>';

	$.ajax({
		type: (inputCollection.forceSyncPROPFIND!=undefined && inputCollection.forceSyncPROPFIND==true ? 'PROPFIND' : 'REPORT'),
		url: inputCollection.url+inputCollection.href,
		cache: false,
		crossDomain: (typeof inputCollection.crossDomain=='undefined' ? true : inputCollection.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputCollection.withCredentials=='undefined' ? false : inputCollection.withCredentials)
		},
		timeout: inputCollection.timeOut,
		error: function(objAJAXRequest, strError){
			if((objAJAXRequest.status==400 /* bad request */ || objAJAXRequest.status==403 /* forbidden (for stupid servers) */ || objAJAXRequest.status==501 /* unimplemented */) && inputCollection.forceSyncPROPFIND!=true /* prevent recursion */)
			{
				CardDAVnetLoadCollection(globalResourceCardDAVList.setCollectionFlagByUID(inputCollection.uid, 'forceSyncPROPFIND', true), forceLoad, forceLoadNextContact, innerOperationData);
				return true;
			}
			else
			{
				console.log("Error: [CardDAVnetLoadCollection: '"+inputCollection.url+inputCollection.href+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' - see http://www.inf-it.com/carddavmate/misc/readme_network.txt' : ''));
				return false;
			}
		},
		beforeSend: function(req) {
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && inputCollection.userAuth.userName!='' && inputCollection.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(inputCollection.userAuth.userName,inputCollection.userAuth.userPassword));
			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
			req.setRequestHeader('Depth', '1');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputCollection.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputCollection.userAuth.userPassword : null),
		contentType: 'text/xml; charset=utf-8',
		processData: true,
		data: requestText,
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;

			var vcardList=new Array();
			$(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode(new RegExp('^(sync-)?response$')).each(
				function(index, element)
				{
					var hrefVal=$(element).children().filterNsNode('href').text();
					var etagVal=$(element).children().filterNsNode('propstat').children().filterNsNode('prop').children().filterNsNode('getetag').text();

					var allowContent=false;
					// checkContentType is undocumented but useful if somebody needs to disable it (wrong server response, etc.) 
					if(inputCollection.checkContentType!=false)
					{
						var contenttypeVal=$(element).children().filterNsNode('propstat').children().filterNsNode('prop').children().filterNsNode('getcontenttype').text();
						if(contenttypeVal!=undefined)
						{
							contenttypeValArr=contenttypeVal.toLowerCase().replace(RegExp(' ','g'),'').split(';');
							if(contenttypeValArr.indexOf('text/vcard')!=-1 || contenttypeValArr.indexOf('text/x-vcard')!=-1)
								allowContent=true;
						}
					}
					else
						allowContent=true;

					if(allowContent==true && hrefVal[hrefVal.length-1]!='/')	/* Google CardDav problem with resource URL if content type checking is disabled */
					{
						var result=$(element).find('*').filterNsNode('status').text();	// note for 404 there is no propstat!
						if(result.match(RegExp('200 OK$')))	// HTTP OK
							vcardList[vcardList.length]={etag: etagVal, href: hrefVal};
						else if(result.match(RegExp('404 Not Found$')))	// HTTP Not Found
							vcardList[vcardList.length]={deleted: true, etag: etagVal, href: hrefVal};
					}
				}
			);

			// store the syncToken
			if(inputCollection.forceSyncPROPFIND==undefined || inputCollection.forceSyncPROPFIND==false)
				inputCollection.syncToken=$(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode('sync-token').text();

			// we must call the netLoadAddressbook even if we get empty vcardList
			netLoadAddressbook(inputCollection, vcardList, (inputCollection.forceSyncPROPFIND==undefined || inputCollection.forceSyncPROPFIND==false ? true : false), forceLoadNextContact, innerOperationData);
		}
	});
}

function netLoadAddressbook(inputCollection, vcardList, syncReportSupport, forceLoadNext, innerOperationData)
{
	var vcardChangedList = new Array();
	var resultTimestamp = new Date().getTime();

	if(syncReportSupport==true)
	{
		for(var i=0;i<vcardList.length;i++)
			if(vcardList[i].deleted!=undefined && vcardList[i].deleted==true)
				globalAddressbookList.removeContact(inputCollection.uid+vcardList[i].href.replace(RegExp('.*/',''),''),true);
			else
				vcardChangedList[vcardChangedList.length]=vcardList[i].href;
	}
	else	// no sync-collection REPORT supported (we need to delete contacts by timestamp comparison)
	{
		for(var i=0;i<vcardList.length;i++)
		{
			var uid=inputCollection.uid+vcardList[i].href.replace(RegExp('.*/',''),'');
			if(!globalAddressbookList.checkAndTouchIfExists(uid,vcardList[i].etag,resultTimestamp))
				vcardChangedList[vcardChangedList.length]=vcardList[i].href;
		}
		globalAddressbookList.removeOldContacts(inputCollection.uid, resultTimestamp);
	}

	// not loaded vCards from the last multiget (if any)
	if(inputCollection.pastUnloaded!=undefined && inputCollection.pastUnloaded.length>0)
		vcardChangedList=vcardChangedList.concat(inputCollection.pastUnloaded).sort().unique();

	// if nothing is changed on the server return
	if(vcardChangedList.length==0)
	{
		$('#AddContact').prop('disabled',false);
		$('#ABListLoader').css('display','none');

		if(innerOperationData!=null)
		{
			if(innerOperationData.call=='operationPerform')
				operationPerform(innerOperationData.args.performOperation, innerOperationData.args.contactObj, innerOperationData.args.filterUID);
			else if(innerOperationData.call=='operationPerformed')
				operationPerformed(innerOperationData.args.mode, innerOperationData.args.contactObj, innerOperationData.args.loadContact);
		}
		return true;
	}

	var multigetData='<?xml version="1.0" encoding="utf-8"?><E:addressbook-multiget xmlns:E="urn:ietf:params:xml:ns:carddav"><A:prop xmlns:A="DAV:"><A:getetag/><E:address-data/></A:prop><A:href xmlns:A="DAV:">'+vcardChangedList.join('</A:href><A:href xmlns:A="DAV:">')+'</A:href></E:addressbook-multiget>';

	$.ajax({
		type: 'REPORT',
		url: inputCollection.url+inputCollection.href,
		cache: false,
		crossDomain: (typeof inputCollection.crossDomain=='undefined' ? true : inputCollection.crossDomain),
		xhrFields: {
			withCredentials: (typeof inputCollection.withCredentials=='undefined' ? false : inputCollection.withCredentials)
		},
		timeout: inputCollection.timeOut,
		error: function(objAJAXRequest, strError){
			// unable to load vCards, try to load them next time
			inputCollection.pastUnloaded=vcardChangedList;

			console.log("Error: [netLoadAddressbook: '"+inputCollection.url+inputCollection.href+"'] code: '"+objAJAXRequest.status+"'"+(objAJAXRequest.status==0 ? ' - see http://www.inf-it.com/carddavmate/misc/readme_network.txt' : ''));

			if(innerOperationData!=null && innerOperationData.call=='operationPerform')
			{
				show_editor_message('out','message_error',localization[globalInterfaceLanguage].errUnableSync,globalHideInfoMessageAfter);

				// error icon
				setTimeout(function(){
					var resource=$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContactObj.uiObjects.resource)+'"]');
					resource.addClass('r_error');
					resource.removeClass('r_operate');
					setTimeout(function(){
						inputContactObj.uiObjects.contact.animate({opacity: 1}, 1000);
						inputContactObj.uiObjects.contact.draggable('option', 'disabled', false);
						resource.removeClass('r_error');
						resource.droppable('option', 'disabled', false);
					},globalHideInfoMessageAfter);
				},globalHideInfoMessageAfter/10);

				$('#ABContactOverlay').fadeOut(globalEditorFadeAnimation,function(){$('#AddContact').prop('disabled',false);});
			}
			return false;
		},
		beforeSend: function(req) {
			if((typeof globalUseJqueryAuth=='undefined' || globalUseJqueryAuth!=true) && inputCollection.userAuth.userName!='' && inputCollection.userAuth.userPassword!='')
				req.setRequestHeader('Authorization', basicAuth(inputCollection.userAuth.userName,inputCollection.userAuth.userPassword));
			req.setRequestHeader('X-client', 'CardDavMATE '+globalCardDavMATEVersion+' (INF-IT CardDav Web Client)');
		},
		username: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputCollection.userAuth.userName : null),
		password: (typeof globalUseJqueryAuth!='undefined' && globalUseJqueryAuth==true ? inputCollection.userAuth.userPassword : null),
		contentType: 'text/xml; charset=utf-8',
		processData: true,
		data: multigetData,
		dataType: 'xml',
		complete: function(xml, textStatus)
		{
			if(textStatus!='success')
				return false;

			inputCollection.pastUnloaded=[];	// all vCards loaded

			$(xml.responseXML).children().filterNsNode('multistatus').children().filterNsNode('response').each(
				function(index, element)
				{
					if($(element).children().filterNsNode('propstat').children().filterNsNode('status').text().match(RegExp('200 OK$')))	// HTTP OK
					{
						var etag=$(element).children().filterNsNode('propstat').children().filterNsNode('prop').children().filterNsNode('getetag').text();
						var uid=inputCollection.uid+$(element).children().filterNsNode('href').text().replace(RegExp('.*/',''),'');

						var vcard_raw=$(element).children().filterNsNode('propstat').children().filterNsNode('prop').children().filterNsNode('address-data').text();

						if(vcard_raw!='')
							var result=basicRFCFixesAndCleanup(vcard_raw);
						else
							return true;	// continue for jQuery

						// check the vCard validity here
						// ...
						// ...
						globalAddressbookList.insertContact({timestamp: resultTimestamp, accountUID: inputCollection.accountUID, uid: uid, etag: etag, vcard: result.vcard, categories: result.categories, normalized: false}, (innerOperationData!=null && innerOperationData.call=='operationPerformed' && innerOperationData.args.mode=='DELETE_FROM_GROUP_LAST'));	// if inner operation is DELETE_FROM_GROUP_LAST we force reload the contact
					}
				}
			);

			// update the active search
			globalQs.cache();

			// if no "concurrent" write in progress we need to update the group filter
			if($('#AddContact').attr('data-url')==inputCollection.uid)
				globalAddressbookList.applyABFilter(inputCollection.filterUID, forceLoadNext);

			$('#AddContact').prop('disabled',false);
			$('#ABListLoader').css('display','none');

			if(innerOperationData!=null)
			{
				if(innerOperationData.call=='operationPerform')
				{
					operationPerform(innerOperationData.args.performOperation, innerOperationData.args.contactObj, innerOperationData.args.filterUID);
				}
				else if(innerOperationData.call=='operationPerformed')
					operationPerformed(innerOperationData.args.mode, innerOperationData.args.contactObj, innerOperationData.args.loadContact);
			}

			return true;
		}
	});
}
