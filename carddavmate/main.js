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

var globalCardDavMATEVersion='0.10.1';
var globalVersionCheckURL=location.protocol+'//www.inf-it.com/versioncheck/CardDavMATE/?v='+globalCardDavMATEVersion;
var globalAddressbookList = new AddressbookList();
var globalResourceCardDAVList=new ResourceCardDAVList();
var globalAddressbookIntervalID=null;
var globalResourceCardDAVIntervalID=null;
var globalQs=null;
var globalKBNavigationPaddingRate=0.2;

var origResourceCardDAVListTemplate=null;
var origABListTemplate=null;
var origVcardTemplate=null;
var cleanResourceCardDAVListTemplate=null;
var cleanABListTemplate=null;
var cleanVcardTemplate=null;
var globalAddressElementOldData=new Object();
var isCardDAVLoaded=false;
var isCardDAVAvaible=true;
var cLcouny=0;

var globalWindowFocus=true;
var globalLoginUsername='';
var globalLoginPassword='';
var isUserLogged=false;
var globalRevertFunction='';
var globalActiveApp='';
var globalAvailableAppsArray=new Array();
var globalEnableAppSwitch=false;

// Timepicker hack (prevent IE to re-open the datepicker on date click + focus)
var globalTmpTimePickerHackTime=new Object();

function checkForApplication(inputApp)
{
	if(!globalEnableAppSwitch)
		return false;

	globalEnableAppSwitch=false;
	globalActiveApp=inputApp;
	if(inputApp=='CalDavZAP')
	{
		if($('#resourceCalDAV_h').width())//is visible
		{
			$('#intResourceShow').parent().css('display', 'none');
			$('#intResourceHide').parent().css('display', 'table-cell');
		}
		else
		{
			$('#intResourceHide').parent().css('display', 'none');
			$('#intResourceShow').parent().css('display', 'table-cell');
		}

		$('#SystemCardDAV, #SystemCalDAVTODO').animate({opacity : 0}, 1000, function(){
			$('#SystemCardDAV, #SystemCalDAVTODO').css('visibility','hidden');
			if(!isCalDAVLoaded)
				mainCalDAV();
		});
		$('#SystemCalDAV').css('visibility','visible');
		$('#SystemCalDAV').animate({opacity : 1}, 1000, function(){if(isCalDAVLoaded)globalEnableAppSwitch=true;});
	}
	else if(inputApp=='CalDavTODO')
	{
		if($('#resourceCalDAVTODO_h').width())//is visible
		{
			$('#intResourceShow').parent().css('display', 'none');
			$('#intResourceHide').parent().css('display', 'table-cell');
		}
		else
		{
			$('#intResourceHide').parent().css('display', 'none');
			$('#intResourceShow').parent().css('display', 'table-cell');
		}

		$('#SystemCardDAV, #SystemCalDAV').animate({opacity : 0}, 1000, function(){
			$('#SystemCardDAV, #SystemCalDAV').css('visibility','hidden');
			if(!isCalDAVLoaded)
				mainCalDAV();
		});
		$('#SystemCalDAVTODO').css('visibility','visible');
		$('#SystemCalDAVTODO').animate({opacity : 1}, 1000, function(){if(isCalDAVLoaded)globalEnableAppSwitch=true;});
	}
	else if(inputApp=='CardDavMATE')
	{
		if($('.resourcesCardDAV_d').width()) //is visible
		{
			$('#intResourceShow').parent().css('display', 'none');
			$('#intResourceHide').parent().css('display', 'table-cell');
		}
		else
		{
			$('#intResourceHide').parent().css('display', 'none');
			$('#intResourceShow').parent().css('display', 'table-cell');
		}

		if(!isCardDAVLoaded) {
			$('#MainLoader').css('left','0px');
			$('#MainLoader').css('display','block');
		}

		$('#SystemCalDAV, #SystemCalDAVTODO').animate({opacity : 0},1000,function(){
			$('#SystemCalDAV, #SystemCalDAVTODO').css('visibility','hidden');
			if(!isCardDAVLoaded)
				mainCardDAV();
		});
		$('#SystemCardDAV').css('visibility','visible');
		$('#SystemCardDAV').animate({opacity : 1}, 1000, function(){if(isCardDAVLoaded)globalEnableAppSwitch=true;});
	}
}

function login()
{
	$('#LoginLoader').fadeTo(1200, 1, function(){
		globalLoginUsername=$('#LoginPage').find('[data-type="system_username"]').val();
		globalLoginPassword=$('#LoginPage').find('[data-type="system_password"]').val();

		loadConfig();
	});
}

function logout()
{
	globalLoginUsername='';
	globalLoginPassword='';

	$('#LoginPage').fadeTo(2000, 1, function(){
		if(typeof isCalDAVLoaded!='undefined' && isCalDAVLoaded)
		{
			logoutCalDAV();
			isCalDAVLoaded=false;
		}

		if(typeof isCardDAVLoaded!='undefined' && isCardDAVLoaded)
		{
			logoutCardDAV();
			isCardDAVLoaded=false;
		}

		for(var i=globalAccountSettings.length-1;i>=0;i--)
			if(globalAccountSettings[i].type=='network')
				globalAccountSettings.splice(i, 1);

		if(typeof globalDemoMode=='undefined')
		{
			$('[data-type="system_username"]').val('').change();
			$('[data-type="system_password"]').val('').change();
		}

		$('.integration_d').hide();

		isUserLogged=false;

		if(typeof globalDefaultActiveApp=='undefined' || globalDefaultActiveApp==null)
		{
			if(typeof isCalDAVAvaible!='undefined' && isCalDAVAvaible!=null)
				globalActiveApp='CalDavZAP';
			else if(typeof isCardDAVAvaible!='undefined' && isCardDAVAvaible!=null)
				globalActiveApp='CardDavMATE';
		}
		else
			globalActiveApp=globalDefaultActiveApp;
			
		if(globalActiveApp=='CalDavZAP')
			mainCalDAV();
		else
			mainCardDAV();
	});
}

function init()
{
	// browser check
	if(($.browser.msie && parseInt($.browser.version, 10)<9) || $.browser.opera)
		$('#login_message').css('display','').find('td').text(localization[globalInterfaceLanguage].unsupportedBrowser);

	if(typeof globalDemoMode!='undefined')
	{
		if(typeof globalDemoMode.userName!=undefined)
			$('[data-type="system_username"]').val(globalDemoMode.userName).change();
		if(typeof globalDemoMode.userPassword!=undefined)
			$('[data-type="system_password"]').val(globalDemoMode.userPassword).change();
	}

	if(typeof globalAvailableAppsArray!='undefined' && globalAvailableAppsArray!=null && globalAvailableAppsArray.length == 1 && globalAvailableAppsArray.indexOf('CalDavZAP')!=-1) {
		setLogoCalDAV();
	}
	loadConfig();
}

function run()
{
	isUserLogged=true;
	window.onfocus=function(){globalWindowFocus=true;}
	window.onblur=function(){if(typeof globalBackgroundSync!='undefined' && globalBackgroundSync==false) globalWindowFocus=false;}

	$('#LoginPage').fadeOut(2000);

	if(typeof globalAccountSettings=='undefined')
	{
		console.log('Error: \'no account configured\': see config.js!');
		return false;
	}

	if(typeof globalNewVersionNotifyUsers=='undefined' || globalNewVersionNotifyUsers!=null)
		netVersionCheck();

	// Automatically detect crossDomain settings
	var detectedHref=location.protocol+'//'+location.hostname+(location.port ? ':'+location.port : '');
	for(var i=0;i<globalAccountSettings.length;i++)
	{
		if(globalAccountSettings[i].crossDomain==undefined || typeof globalAccountSettings[i].crossDomain!='boolean')
		{
			if(globalAccountSettings[i].href.indexOf(detectedHref)==0)
				globalAccountSettings[i].crossDomain=false;
			else
				globalAccountSettings[i].crossDomain=true;

			console.log("Info: [account: '"+globalAccountSettings[i].href.replace('\/\/', '//'+globalAccountSettings[i].userAuth.userName+'@')+"'] crossDomain set to: '"+(globalAccountSettings[i].crossDomain==true ? 'true' : 'false')+"'");
		}
	}

	if(typeof globalAvailableAppsArray!='undefined' && globalAvailableAppsArray!=null && globalAvailableAppsArray.length>1)
	{
		$(".integration_d").css('display', 'block');
		if(globalAvailableAppsArray.indexOf('CalDavZAP')!=-1) {
			$("#intCaldav b").text(localization[globalInterfaceLanguage].txtCalendar);
			$("#intCaldav").parent().css('display', 'table-cell');
		}
		if(globalAvailableAppsArray.indexOf('CalDavTODO')!=-1) {
			$("#intCaldavTodo b").text(localization[globalInterfaceLanguage].txtTodo);
			$("#intCaldavTodo").parent().css('display', 'table-cell');
		}
		if(globalAvailableAppsArray.indexOf('CardDavMATE')!=-1) {
			$("#intCarddav b").text(localization[globalInterfaceLanguage].txtAddressbook);
			$("#intCarddav").parent().css('display', 'table-cell');
		}

		var iw = $('.integration_inner').width();
		// tu su 2 konstanty od oka (13 a 11) ... nad nimi sa este zamyslite ci sa nedaju vypocitat ...
		$('.integration_h').css({'top': (iw/2-13)+'px', left: '-'+(iw/2-11)+'px'});
	}
}

function loadConfig()
{
	if(isUserLogged)
	{
		if(globalActiveApp=='CalDavZAP')
			runCalDAV();
		else
			runCardDAV();

		return false;
	}

	var configLoaded=true;
	// Automatically detect crossDomain settings
	var detectedHref=location.protocol+'//'+location.hostname+(location.port ? ':'+location.port : '');

	// check username and password against the server and create config from globalNetworkCheckSettings
	if(typeof globalNetworkCheckSettings!='undefined' && globalNetworkCheckSettings!=null)
	{
		if(globalLoginUsername=='' || globalLoginPassword=='')
		{
			$('#LoginPage').fadeTo(500, 1, function(){if(typeof globalDemoMode=='undefined') $('[data-type="system_username"]').focus()});
			$('#LoginLoader').fadeOut(1200);
			return false;
		}
		else
		{
			if(globalNetworkCheckSettings.crossDomain==undefined || typeof globalNetworkCheckSettings.crossDomain!='boolean')
			{
				if(globalNetworkCheckSettings.href.indexOf(detectedHref)==0)
					globalNetworkCheckSettings.crossDomain=false;
				else
					globalNetworkCheckSettings.crossDomain=true;

				console.log("Info: [globalNetworkCheckSettings: '"+globalNetworkCheckSettings.href+"'] crossDomain set to: '"+(globalNetworkCheckSettings.crossDomain==true ? 'true' : 'false')+"'");
			}
			netCheckAndCreateConfiguration(globalNetworkCheckSettings);
			return true;
		}
	}

	// load the configuration XML(s) from the network
	if(typeof globalNetworkAccountSettings!='undefined' && globalNetworkAccountSettings!=null)
	{
		if(globalLoginUsername=='' || globalLoginPassword=='')
		{
			$('#LoginPage').fadeTo(500, 1, function(){if(typeof globalDemoMode=='undefined') $('[data-type="system_username"]').focus()});
			$('#LoginLoader').fadeOut(1200);
			return false;
		}
		else
		{
			if(globalNetworkAccountSettings.crossDomain==undefined || typeof globalNetworkAccountSettings.crossDomain!='boolean')
			{
				if(globalNetworkAccountSettings.href.indexOf(detectedHref)==0)
					globalNetworkAccountSettings.crossDomain=false;
				else
					globalNetworkAccountSettings.crossDomain=true;

				console.log("Info: [globalNetworkAccountSettings: '"+globalNetworkAccountSettings.href+"'] crossDomain set to: '"+(globalNetworkAccountSettings.crossDomain==true ? 'true' : 'false')+"'");
			}
			netLoadConfiguration(globalNetworkAccountSettings);
			return true;
		}
	}
	if((typeof globalNetworkAccountSettings=='undefined' || globalNetworkAccountSettings==null) && (typeof globalNetworkCheckSettings=='undefined' || globalNetworkCheckSettings==null) && 
 		(typeof globalAccountSettings!='undefined' && globalAccountSettings!=null) && globalAccountSettings.length>0)
 		for(var i=0; i<globalAccountSettings.length;i++)
 			if((typeof globalAccountSettings[i].delegation =='boolean' && globalAccountSettings[i].delegation) || (globalAccountSettings[i].delegation instanceof Array && globalAccountSettings[i].delegation.length>0))
				DAVresourceDelegation(globalAccountSettings[i]);
		
	if(globalActiveApp=='CalDavZAP')
		runCalDAV();
	else
		runCardDAV();
}

function globalMain()
{
	/*************************** BAD HACKS SECTION ***************************/
	// here we fix the cross OS/cross broser problems (unfixable in pure CSS)
	if($.browser.webkit && !!window.chrome)	/* Chrome */
	{
		if(navigator.platform.toLowerCase().indexOf('win')==0)	/* Windows version */
		{
			$('#LoginPage, #vCardTemplate, #event_details_template, #todo_details_template').find('input').css('text-indent', '2px');
			$('#LoginPage, #vCardTemplate, #event_details_template, #todo_details_template').find('select').css({'padding-left': '0px', 'padding-right': '13px'});
		}
		else	/* non-Windows version */
			$('#LoginPage, #vCardTemplate, #event_details_template, #todo_details_template').find('input').css('text-indent', '1px');
	}
	else if($.browser.msie)	/* IE */
	{
		if(parseInt($.browser.version, 10)==10)	/* IE 10 (because there are no more conditional comments) */
		{
			$('select').css({'padding-top': '1px', 'padding-left': '0px', 'padding-right': '0px'});
			$('textarea').css('padding-top', '3px');

			$('input[type=button]').css('padding-top', '2px');
		}

		// ADD SVG to login screen
		var newSVG=$(SVG_select).attr('data-type', 'select_icon').css({'pointer-events': 'none', 'z-index': '1', 'display': 'inline', 'margin-left': '-19px', 'vertical-align': 'top', 'background-color': '#ffffff'});	// background-color = stupid IE9 bug
		$('#Login').find('select[data-type="language"]').after($($('<div>').append($(newSVG).clone()).html()));
	}
	else if($.browser.mozilla)
	{
		// ADD SVG to login screen
		var newSVG=$(SVG_select).attr('data-type', 'select_icon').css({'pointer-events': 'none', 'z-index': '1', 'display': 'inline', 'margin-left': '-19px', 'vertical-align': 'top', 'background-color': '#ffffff'});	// background-color = stupid IE9 bug
		$('#Login').find('select[data-type="language"]').after($($('<div>').append($(newSVG).clone()).html()));
	}
	/*************************** END OF BAD HACKS SECTION ***************************/

	/* language selector */
	var lang_num=0;
	var language_option=$('#Login').find('[data-type="language"]').find('option');
	$('#Login').find('[data-type="language"]').html('');

	if(typeof globalInterfaceCustomLanguages!='undefined' && globalInterfaceCustomLanguages.length!=undefined && globalInterfaceCustomLanguages.length>0)
	{
		for(var i=0;i<globalInterfaceCustomLanguages.length;i++)
			if(localization[globalInterfaceCustomLanguages[i]]!=undefined)
			{
				var tmp=language_option;
				tmp.attr('data-type',globalInterfaceCustomLanguages[i]);
				tmp.text(localization[globalInterfaceCustomLanguages[i]]['_name_']);
				$('#Login').find('[data-type="language"]').append(tmp.clone());
				lang_num++;
			}
	}
	if(lang_num==0)	// no language option, use the default (all languages from localization.js)
	{
		for(var loc in localization)
		{
			var tmp=language_option;
			tmp.attr('data-type',loc);
			tmp.text(localization[loc]['_name_']);	// translation
			$('#Login').find('[data-type="language"]').append(tmp.clone());
		}
	}

	if(typeof globalEnabledApps=='undefined' || globalEnabledApps==null)
	{
		if(typeof isCalDAVAvaible!='undefined' && isCalDAVAvaible!=null)
		{
			globalAvailableAppsArray[globalAvailableAppsArray.length]='CalDavZAP';
			globalAvailableAppsArray[globalAvailableAppsArray.length]='CalDavTODO';
		}
		if(typeof isCardDAVAvaible!='undefined' && isCardDAVAvaible!=null)
			globalAvailableAppsArray[globalAvailableAppsArray.length]='CardDavMATE';
	}
	else
		globalAvailableAppsArray=globalEnabledApps;

	if(typeof globalDefaultActiveApp=='undefined' || globalDefaultActiveApp==null)
	{
		if(typeof isCalDAVAvaible!='undefined' && isCalDAVAvaible!=null)
			globalActiveApp='CalDavZAP';
		else if(typeof isCardDAVAvaible!='undefined' && isCardDAVAvaible!=null)
			globalActiveApp='CardDavMATE';
	}
	else
		globalActiveApp=globalDefaultActiveApp;

	// create backup from the original editor objects (needed for localization switching)
	if(typeof globalAvailableAppsArray!='undefined' && globalAvailableAppsArray!=null && globalAvailableAppsArray.indexOf('CardDavMATE')!=-1)
	{
		globalMainCardDAV();
	}

	if(typeof globalAvailableAppsArray!='undefined' && globalAvailableAppsArray!=null && globalAvailableAppsArray.indexOf('CalDavZAP')!=-1)
	{
		globalMainCalDAV();
	}

	// select the globalInterfaceLanguage in the interface
	$('[data-type="language"]').find('[data-type='+globalInterfaceLanguage+']').prop('selected',true);

	if(globalActiveApp=='CalDavZAP')
		mainCalDAV();
	else
		mainCardDAV();
}

window.onload=globalMain;

function logoutCardDAV()
{
	globalAddressbookList.reset();
	globalResourceCardDAVList.reset();

	if(globalAddressbookIntervalID!=null)
		clearInterval(globalAddressbookIntervalID);

	if(globalResourceCardDAVIntervalID!=null)
		clearInterval(globalResourceCardDAVIntervalID);

	// hide update notification
	$('#SystemCardDAV').find('div.update_d').hide();

	// if the editor is in "edit" state during the logout,
	//  we need to remove all overlays (for next login)
	$('#ResourceCardDAVListOverlay').fadeOut(2000);
	$('#ABListOverlay').fadeOut(2000);

	$('#SystemCardDAV').animate({opacity : 0},200,function(){
		$('#SystemCardDAV').css('visibility','hidden');
	});
}

function globalMainCardDAV()
{
	origResourceCardDAVListTemplate = $('#ResourceCardDAVListTemplate').clone().wrap('<div>').parent().html();
	origABListTemplate = $('#ABListTemplate').clone().wrap('<div>').parent().html();
	origVcardTemplate = $('#vCardTemplate').clone().wrap('<div>').parent().html();

	if(typeof globalDatepickerFormat!='undefined' && globalDatepickerFormat!=null)
		globalSessionDatepickerFormat=globalDatepickerFormat;
}

function mainCardDAV()
{
	localizeCardDAV();
	init();
}

function localizeCardDAV()
{
	$('#ResourceCardDAVList').html(origResourceCardDAVListTemplate);
	$('#ABList').html(origABListTemplate);
	$('#ABContact').html(origVcardTemplate);

	localizeAddressTypes();
	var country_option=$('[data-type="\\%address"]').find('[data-type="country_type"]').find('option').clone();

	$('[data-type="\\%address"]').find('[data-type="country_type"]').html('');

	// we need a copy of the object because of the next "delete" operation
	var addressTypesTmp=jQuery.extend({}, addressTypes);

	// delete custom ordered element before the sort (then we will add them back)
	if(typeof globalAddressCountryFavorites!='undefined' && globalAddressCountryFavorites.length>0)
		for(var i=globalAddressCountryFavorites.length-1;i>=0;i--)
			delete addressTypesTmp[globalAddressCountryFavorites[i]];

	// reorder countries according to localization (returns array becaouse object are unsorted according to ECMA)
	function sortObject(obj)
	{
		var arr=[];
		for(var prop in obj)
			if(obj.hasOwnProperty(prop))
				arr.push({'key': prop, 'value': obj[prop], 'translated_value': localization[globalInterfaceLanguage]['txtAddressCountry'+prop.toUpperCase()]});

		return arr.sort(function(a, b){return a.translated_value.customCompare(b.translated_value, globalSortAlphabet, 1, false)});
	}

	var addressTypesArr=sortObject(addressTypesTmp);

	// re-add custom ordered elements from the original addressTypes (where all elements are still present)
	if(typeof globalAddressCountryFavorites!='undefined' && globalAddressCountryFavorites.length>0)
		for(var i=globalAddressCountryFavorites.length-1;i>=0;i--)
			addressTypesArr.unshift({'key': globalAddressCountryFavorites[i], 'value': addressTypes[globalAddressCountryFavorites[i]], 'translated_value': localization[globalInterfaceLanguage]['txtAddressCountry'+globalAddressCountryFavorites[i].toUpperCase()]});

	for(var i=0;i<addressTypesArr.length;i++)
	{
		var tmp=country_option;
		tmp.attr('data-type',addressTypesArr[i].key);
		tmp.attr('data-full-name',addressTypesArr[i].value[0]);
		tmp.text(addressTypesArr[i].translated_value);	// translation
		$('[data-type="\\%address"]').find('[data-type="country_type"]').append(tmp.clone());
	}

	$('[data-type="\\%address"]').find('[data-type="country_type"]').attr('data-autoselect',globalDefaultAddressCountry);

	// locale-specific date format
	if(typeof globalDatepickerFormat=='undefined' || globalDatepickerFormat==null)
		globalSessionDatepickerFormat=localization[globalInterfaceLanguage]._default_datepicker_format_;

	// interface translation
	$('[data-type="system_logo"]').attr('alt',localization[globalInterfaceLanguage].altLogo);
	$('[data-type="system_username"]').attr('placeholder',localization[globalInterfaceLanguage].pholderUsername);
	$('[data-type="system_password"]').attr('placeholder',localization[globalInterfaceLanguage].pholderPassword);
	$('[data-type="system_login"]').val(localization[globalInterfaceLanguage].buttonLogin);

	$('[data-type="resourcesCardDAV_txt"]').text(localization[globalInterfaceLanguage].txtResources);
	$('[data-type="addressbook_txt"]').text(localization[globalInterfaceLanguage].txtAddressbook);
	$('[data-type="contact_txt"]').text(localization[globalInterfaceLanguage].txtContact);
	$('[data-type="search"]').attr('placeholder',localization[globalInterfaceLanguage].txtSearch);
	$('#AddContact').attr('alt',localization[globalInterfaceLanguage].altAddContact);
	$('#AddContact').attr('title',localization[globalInterfaceLanguage].altAddContact);
	$('#Logout').attr('alt',localization[globalInterfaceLanguage].altLogout);
	$('#Logout').attr('title',localization[globalInterfaceLanguage].altLogout);
	$('[data-type="photo"]').attr('alt',localization[globalInterfaceLanguage].altPhoto);

	$('[data-type="given"]').attr('placeholder',localization[globalInterfaceLanguage].pholderGiven);
	$('[data-type="family"]').attr('placeholder',localization[globalInterfaceLanguage].pholderFamily);
	$('[data-type="middle"]').attr('placeholder',localization[globalInterfaceLanguage].pholderMiddle);
	$('[data-type="nickname"]').attr('placeholder',localization[globalInterfaceLanguage].pholderNickname);
	$('[data-type="ph_firstname"]').attr('placeholder',localization[globalInterfaceLanguage].pholderPhGiven);
	$('[data-type="ph_lastname"]').attr('placeholder',localization[globalInterfaceLanguage].pholderPhFamily);
	$('[data-type="prefix"]').attr('placeholder',localization[globalInterfaceLanguage].pholderPrefix);
	$('[data-type="suffix"]').attr('placeholder',localization[globalInterfaceLanguage].pholderSuffix);
	$('[data-type="date_bday"]').attr('placeholder',localization[globalInterfaceLanguage].pholderBday);
	$('[data-type="date_anniversary"]').attr('placeholder',localization[globalInterfaceLanguage].pholderAnniversary);
	$('[data-type="title"]').attr('placeholder',localization[globalInterfaceLanguage].pholderTitle);
	$('[data-type="org"]').attr('placeholder',localization[globalInterfaceLanguage].pholderOrg);
	$('[data-type="department"]').attr('placeholder',localization[globalInterfaceLanguage].pholderDepartment);
	$('span[data-type="company_contact"]').text(localization[globalInterfaceLanguage].txtCompanyContact);

	$('[data-type="\\%del"]').attr('alt',localization[globalInterfaceLanguage].altDel);
	$('[data-type="\\%add"]').attr('alt',localization[globalInterfaceLanguage].altAdd);
	$('[data-type="value_handler"]').attr('alt',localization[globalInterfaceLanguage].altValueHandler);

	$('[data-type=":custom"]').text(localization[globalInterfaceLanguage].txtCustom);
	$('[data-type="custom_value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderCustomVal);

	$('[data-type="phone_txt"]').text(localization[globalInterfaceLanguage].txtPhone);
	$('[data-type="\\%phone"]').find('input[data-type="value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderPhoneVal);
	$('[data-type="\\%phone"]').find('[data-type="work"]').text(localization[globalInterfaceLanguage].txtPhoneWork);
	$('[data-type="\\%phone"]').find('[data-type="home"]').text(localization[globalInterfaceLanguage].txtPhoneHome);
	$('[data-type="\\%phone"]').find('[data-type="cell"]').text(localization[globalInterfaceLanguage].txtPhoneCell);
	$('[data-type="\\%phone"]').find('[data-type="cell,work"]').text(localization[globalInterfaceLanguage].txtPhoneCellWork);
	$('[data-type="\\%phone"]').find('[data-type="cell,home"]').text(localization[globalInterfaceLanguage].txtPhoneCellHome);
	$('[data-type="\\%phone"]').find('[data-type="main"]').text(localization[globalInterfaceLanguage].txtPhoneMain);
	$('[data-type="\\%phone"]').find('[data-type="pager"]').text(localization[globalInterfaceLanguage].txtPhonePager);
	$('[data-type="\\%phone"]').find('[data-type="fax"]').text(localization[globalInterfaceLanguage].txtPhoneFax);
	$('[data-type="\\%phone"]').find('[data-type="fax,work"]').text(localization[globalInterfaceLanguage].txtPhoneFaxWork);
	$('[data-type="\\%phone"]').find('[data-type="fax,home"]').text(localization[globalInterfaceLanguage].txtPhoneFaxHome);
	$('[data-type="\\%phone"]').find('[data-type="iphone"]').text(localization[globalInterfaceLanguage].txtPhoneIphone);
	$('[data-type="\\%phone"]').find('[data-type="other"]').text(localization[globalInterfaceLanguage].txtPhoneOther);

	$('[data-type="email_txt"]').text(localization[globalInterfaceLanguage].txtEmail);
	$('[data-type="\\%email"]').find('input[data-type="value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderEmailVal);
	$('[data-type="\\%email"]').find('[data-type="internet,work"]').text(localization[globalInterfaceLanguage].txtEmailWork);
	$('[data-type="\\%email"]').find('[data-type="home,internet"]').text(localization[globalInterfaceLanguage].txtEmailHome);
	$('[data-type="\\%email"]').find('[data-type=":mobileme:,internet"]').text(localization[globalInterfaceLanguage].txtEmailMobileme);
	$('[data-type="\\%email"]').find('[data-type=":_$!<other>!$_:,internet"]').text(localization[globalInterfaceLanguage].txtEmailOther);

	$('[data-type="url_txt"]').text(localization[globalInterfaceLanguage].txtUrl);
	$('[data-type="\\%url"]').find('input[data-type="value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderUrlVal);
	$('[data-type="\\%url"]').find('[data-type="work"]').text(localization[globalInterfaceLanguage].txtUrlWork);
	$('[data-type="\\%url"]').find('[data-type="home"]').text(localization[globalInterfaceLanguage].txtUrlHome);
	$('[data-type="\\%url"]').find('[data-type=":_$!<homepage>!$_:"]').text(localization[globalInterfaceLanguage].txtUrlHomepage);
	$('[data-type="\\%url"]').find('[data-type=":_$!<other>!$_:"]').text(localization[globalInterfaceLanguage].txtUrlOther);

	$('[data-type="related_txt"]').text(localization[globalInterfaceLanguage].txtRelated);
	$('[data-type="\\%person"]').find('input[data-type="value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderRelatedVal);
	$('[data-type="\\%person"]').find('[data-type=":_$!<manager>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedManager);
	$('[data-type="\\%person"]').find('[data-type=":_$!<assistant>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedAssistant);
	$('[data-type="\\%person"]').find('[data-type=":_$!<father>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedFather);
	$('[data-type="\\%person"]').find('[data-type=":_$!<mother>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedMother);
	$('[data-type="\\%person"]').find('[data-type=":_$!<parent>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedParent);
	$('[data-type="\\%person"]').find('[data-type=":_$!<brother>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedBrother);
	$('[data-type="\\%person"]').find('[data-type=":_$!<sister>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedSister);
	$('[data-type="\\%person"]').find('[data-type=":_$!<child>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedChild);
	$('[data-type="\\%person"]').find('[data-type=":_$!<friend>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedFriend);
	$('[data-type="\\%person"]').find('[data-type=":_$!<spouse>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedSpouse);
	$('[data-type="\\%person"]').find('[data-type=":_$!<partner>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedPartner);
	$('[data-type="\\%person"]').find('[data-type=":_$!<other>!$_:"]').text(localization[globalInterfaceLanguage].txtRelatedOther);

	$('[data-type="im_txt"]').text(localization[globalInterfaceLanguage].txtIm);
	$('[data-type="\\%im"]').find('input[data-type="value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderImVal);
	$('[data-type="\\%im"]').find('[data-type="work"]').text(localization[globalInterfaceLanguage].txtImWork);
	$('[data-type="\\%im"]').find('[data-type="home"]').text(localization[globalInterfaceLanguage].txtImHome);
	$('[data-type="\\%im"]').find('[data-type=":mobileme:"]').text(localization[globalInterfaceLanguage].txtImMobileme);
	$('[data-type="\\%im"]').find('[data-type=":_$!<other>!$_:"]').text(localization[globalInterfaceLanguage].txtImOther);
	$('[data-type="\\%im"]').find('[data-type="aim"]').text(localization[globalInterfaceLanguage].txtImProtAim);
	$('[data-type="\\%im"]').find('[data-type="icq"]').text(localization[globalInterfaceLanguage].txtImProtIcq);
	$('[data-type="\\%im"]').find('[data-type="irc"]').text(localization[globalInterfaceLanguage].txtImProtIrc);
	$('[data-type="\\%im"]').find('[data-type="jabber"]').text(localization[globalInterfaceLanguage].txtImProtJabber);
	$('[data-type="\\%im"]').find('[data-type="msn"]').text(localization[globalInterfaceLanguage].txtImProtMsn);
	$('[data-type="\\%im"]').find('[data-type="yahoo"]').text(localization[globalInterfaceLanguage].txtImProtYahoo);
	$('[data-type="\\%im"]').find('[data-type="facebook"]').text(localization[globalInterfaceLanguage].txtImProtFacebook);
	$('[data-type="\\%im"]').find('[data-type="gadugadu"]').text(localization[globalInterfaceLanguage].txtImProtGadugadu);
	$('[data-type="\\%im"]').find('[data-type="googletalk"]').text(localization[globalInterfaceLanguage].txtImProtGoogletalk);
	$('[data-type="\\%im"]').find('[data-type="qq"]').text(localization[globalInterfaceLanguage].txtImProtQq);
	$('[data-type="\\%im"]').find('[data-type="skype"]').text(localization[globalInterfaceLanguage].txtImProtSkype);

	$('[data-type="profile_txt"]').text(localization[globalInterfaceLanguage].txtProfile);
	$('[data-type="\\%profile"]').find('input[data-type="value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderProfileVal);
	$('[data-type="\\%profile"]').find('[data-type="twitter"]').text(localization[globalInterfaceLanguage].txtProfileTwitter);
	$('[data-type="\\%profile"]').find('[data-type="facebook"]').text(localization[globalInterfaceLanguage].txtProfileFacebook);
	$('[data-type="\\%profile"]').find('[data-type="flickr"]').text(localization[globalInterfaceLanguage].txtProfileFlickr);
	$('[data-type="\\%profile"]').find('[data-type="linkedin"]').text(localization[globalInterfaceLanguage].txtProfileLinkedin);
	$('[data-type="\\%profile"]').find('[data-type="myspace"]').text(localization[globalInterfaceLanguage].txtProfileMyspace);
	$('[data-type="\\%profile"]').find('[data-type="sinaweibo"]').text(localization[globalInterfaceLanguage].txtProfileSinaweibo);

	$('[data-type="address_txt"]').text(localization[globalInterfaceLanguage].txtAddress);
	$('[data-type="\\%address"]').find('[data-type="work"]').text(localization[globalInterfaceLanguage].txtAddressWork);
	$('[data-type="\\%address"]').find('[data-type="home"]').text(localization[globalInterfaceLanguage].txtAddressHome);
	$('[data-type="\\%address"]').find('[data-type=":_$!<other>!$_:"]').text(localization[globalInterfaceLanguage].txtAddressOther);

	$('[data-type="categories_txt"]').text(localization[globalInterfaceLanguage].txtCategories);

	$('[data-type="note_txt"]').text(localization[globalInterfaceLanguage].txtNote);
	$('[data-type="\\%note"]').find('textarea[data-type="value"]').attr('placeholder',localization[globalInterfaceLanguage].pholderNoteVal);

	$('[data-type="edit"]').val(localization[globalInterfaceLanguage].buttonEdit);
	$('[data-type="save"]').val(localization[globalInterfaceLanguage].buttonSave);
	$('[data-type="cancel"]').val(localization[globalInterfaceLanguage].buttonCancel);
	$('[data-type="delete_from_group"]').val(localization[globalInterfaceLanguage].buttonDeleteFromGroup);
	$('[data-type="delete"]').val(localization[globalInterfaceLanguage].buttonDelete);

	cleanResourceCardDAVListTemplate = $('#ResourceCardDAVListTemplate').clone().wrap('<div>').parent().html();
	cleanABListTemplate = $('#ABListTemplate').clone().wrap('<div>').parent().html();
	cleanVcardTemplate = $('#vCardTemplate').clone().wrap('<div>').parent().html();

	// CUSTOM PLACEHOLDER (initialization for the whole page)
	$('input[placeholder],textarea[placeholder]').placeholder();
}

function runCardDAV()
{
	if(!isUserLogged)
		run();

	isCardDAVLoaded=true;
	$('#SystemCardDAV').animate({opacity : 1},200,function(){
		$('#SystemCardDAV').css('visibility','visible');
		$('#MainLoader').css('display','none');
		$('#MainLoader').css('left','25px');
		globalEnableAppSwitch=true;
	});

	CardDAVloadResources(globalAccountSettings, true);

	// automatically reload resources
	function CardDAVreloadResources() {CardDAVloadResources(globalAccountSettings, false)}
	globalResourceCardDAVIntervalID=setInterval(CardDAVreloadResources, globalSyncResourcesInterval);
}
