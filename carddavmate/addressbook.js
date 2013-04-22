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

function loadAddressbook(inputCollection, forceLoad)
{
	if(forceLoad!=true && globalWindowFocus==false)
		return false;

	CardDAVnetLoadCollection(inputCollection, forceLoad, false, null);
}

// AddressbookList Class
function AddressbookList()
{
	this.contacts=new Array();
	this.contact_groups=new Object();
	this.contact_categories=new Object();
	this.contact_companies=new Object();
	this.contactLoaded=new Object();
	this.contactGroupLoaded=null;

	this.reset=function()
	{
		this.contacts.splice(0,this.contacts.length);
		this.contact_groups=new Object();	// these are not removed from the interface (it's OK)
		this.contact_categories=new Object();
		this.contact_companies=new Object();
		this.contactLoaded=new Object();
		this.contactGroupLoaded=null;
	}

	this.getNewUID=function()
	{
		var newUID=null;
		var found=true;

		while(found==true)
		{
			newUID=generateUID();
			found=false;
			for(i=0;i<this.contacts.length;i++)
				if(this.contacts[i].uid!=undefined && this.contacts[i].uid==newUID)	// undefined = contactlist "alpha header" values
					found=true;
		}
		return newUID;
	}

	this.getLoadedContactUID=function()
	{
		if(this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=undefined && this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=null)
			return this.contactLoaded[$('#AddContact').attr('data-filter-url')].uid;
		else
			return '';
	}

	this.getSortKey=function(vcard_clean,inputSettings)
	{
		/* backward compatibility for stupid users (remove it in future) */
		if(typeof inputSettings=='string')
			var tmp=inputSettings.replace(RegExp(',','g'),', ').split(',');
		else	/* new configuration options (arrays) */
			var tmp=inputSettings.slice();	// copy the configuration array

		if(typeof globalCollectionDisplayOrg=='undefined' || globalCollectionDisplayOrg!=false)
		{
			// check for company vCard
			var vcard_contact_type=('\r\n'+vcard_clean).match(vCard.pre['X-ABShowAs']);
			if(vcard_contact_type!=null && vcard_contact_type.length>0)	// if more than one X-ABShowAs is present, use the first one
			{
				// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
				var parsed=vcard_contact_type[0].match(vCard.pre['contentline_parse']);
				if(parsed[4].toLowerCase()=='company')	// company vCard
				{
					var vcard_orgname=('\r\n'+vcard_clean).match(vCard.pre['contentline_ORG']);
					if(vcard_orgname!=null && vcard_orgname.length>0)	// if more than one ORG is present, use the first one
					{
						// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
						var parsed=vcard_orgname[0].match(vCard.pre['contentline_parse']);
						var parsed_value=vcardSplitValue(parsed[4],';');

						if(parsed_value[0]!='')
							return parsed_value[0];
					}
				}
			}
		}

		var vcard_element=('\r\n'+vcard_clean).match(vCard.pre['contentline_N']);
		if(vcard_element!=null && vcard_element.length==1)	// if the N attribute is not present exactly once, vCard is considered invalid
		{
			// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
			var parsed=vcard_element[0].match(vCard.pre['contentline_parse']);
			// parsed_value = [0]->Family, [1]->Given, [2]->Middle, [3]->Prefix, [4]->Suffix
			var parsed_value=vcardSplitValue(parsed[4],';');

			var first_found=false;
			for(var i=0;i<tmp.length;i++)
			{
				var tmp_found=false;
				if(tmp[i].match(RegExp('surname|lastname|last|family','ig'))!=null)
				{
					if(parsed_value[0]==undefined || parsed_value[0]=='')
						tmp[i]='';
					else
					{
						tmp[i]=tmp[i].replace(RegExp((!first_found ? '.*' : '')+'(surname|lastname|last|family)','ig'),parsed_value[0]);
						first_found=true;
					}
				}
				else if(tmp[i].match(RegExp('firstname|first|given','ig'))!=null)
				{
					if(parsed_value[1]==undefined || parsed_value[1]=='')
						tmp[i]='';
					else
					{
						tmp[i]=tmp[i].replace(RegExp((!first_found ? '.*' : '')+'(firstname|first|given)','ig'),parsed_value[1]);
						first_found=true;
					}
				}
				else if(tmp[i].match(RegExp('middlename|middle','ig'))!=null)
				{
					if(parsed_value[2]==undefined || parsed_value[2]=='')
						tmp[i]='';
					else
					{
						tmp[i]=tmp[i].replace(RegExp((!first_found ? '.*' : '')+'(middlename|middle)','ig'),parsed_value[2]);
						first_found=true;
					}
				}
				else if(tmp[i].match(RegExp('prefix','ig'))!=null)
				{
					if(parsed_value[3]==undefined || parsed_value[3]=='')
						tmp[i]='';
					else
					{
						tmp[i]=tmp[i].replace(RegExp((!first_found ? '.*' : '')+'prefix','ig'),parsed_value[3]);
						first_found=true;
					}
				}
				else if(tmp[i].match(RegExp('suffix','ig'))!=null)
				{
					if(parsed_value[4]==undefined || parsed_value[4]=='')
						tmp[i]='';
					else
					{
						tmp[i]=tmp[i].replace(RegExp((!first_found ? '.*' : '')+'suffix','ig'),parsed_value[4]);
						first_found=true;
					}
				}
			}
			sort_value=tmp.join('');

			if(sort_value=='')	// if no N value present, we use the FN instead
			{
				var vcard_element2=('\r\n'+vcard_clean).match(vCard.pre['contentline_FN']);
				if(vcard_element2!=null && vcard_element2.length==1)	// if the FN attribute is not present exactly once, vCard is considered invalid
				{
					// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
					var parsed=vcard_element2[0].match(vCard.pre['contentline_parse']);
					var sort_value=parsed[4];
				}
			}
			return sort_value;
		}
		else
			return false;
	}

	this.isContactGroup=function(vcard_clean)
	{
		if(('\r\n'+vcard_clean).match(vCard.pre['X-ADDRESSBOOKSERVER-KIND'])!=null)
			return true;
		else
			return false;
	}

	this.getMyContactGroups=function(inputUid)
	{
		for(var i=0;i<this.contacts.length;i++)
			if(this.contacts[i].uid==inputUid)
			{
				var myContactGroups=new Array();

				if((vcard_element=this.contacts[i].vcard.match(vCard.pre['contentline_UID']))!=null)
				{
					// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
					parsed=vcard_element[0].match(vCard.pre['contentline_parse']);

					if(this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')]==undefined)
						this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')]=new Array();

					for(var j=0;j<this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')].length;j++)
					{
						if(this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][j].vcard.match(RegExp('\r\nX-ADDRESSBOOKSERVER-MEMBER:urn:uuid:'+parsed[4]+'\r\n','mi')))
							myContactGroups[myContactGroups.length]=this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][j].uid;
					}
				}
				return myContactGroups;
			}
		return null;
	}

	this.getRemoveMeFromContactGroups=function(inputUid, inputContactGroupsUidArr)
	{
		for(var i=0;i<this.contacts.length;i++)
			if(this.contacts[i].uid==inputUid)
			{
				var changedContactGroups=new Array();

				if((vcard_element=this.contacts[i].vcard.match(vCard.pre['contentline_UID']))!=null)
				{
					// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
					parsed=vcard_element[0].match(vCard.pre['contentline_parse']);

					if(this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')]==undefined)
						this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')]=new Array();

					for(var j=0;j<this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')].length;j++)
					{
						if(inputContactGroupsUidArr!=null)
						{
							var skipThis=true;
							for(var k=0;k<inputContactGroupsUidArr.length;k++)
								if(inputContactGroupsUidArr[k]==this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][j].uid)
								{
									skipThis=false;
									break;
								}

							if(skipThis==true)
								continue;
						}

						var vcard=this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][j].vcard;

						var changedVcard=null;
						if(vcard!=(changedVcard=vcard.replaceAll('\r\nX-ADDRESSBOOKSERVER-MEMBER:urn:uuid:'+parsed[4]+'\r\n','\r\n')))
						{
							// update the revision in the group vcard
							var d = new Date();
							utc=d.getUTCFullYear()+(d.getUTCMonth()+1<10 ? '0':'')+(d.getUTCMonth()+1)+(d.getUTCDate()<10 ? '0':'')+d.getUTCDate()+'T'+(d.getUTCHours()<10 ? '0':'')+d.getUTCHours()+(d.getUTCMinutes()<10 ? '0':'')+d.getUTCMinutes()+(d.getUTCSeconds()<10 ? '0':'')+d.getUTCSeconds()+'Z';
							changedVcard=changedVcard.replace(RegExp('\r\nREV:.*\r\n','mi'),'\r\nREV:'+utc+'\r\n');

							// "copy" of the original object
							changedContactGroups[changedContactGroups.length]=$.extend({},this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][j]);
							// new modified vcard group
							changedContactGroups[changedContactGroups.length-1].vcard=changedVcard;
						}
					}
				}
				return changedContactGroups;
			}
		return null;
	}

	this.getAddMeToContactGroups=function(inputContactObj, inputContactGroupsUidArr)
	{
		if(!(inputContactGroupsUidArr instanceof Array))
			inputContactGroupsUidArr=[inputContactGroupsUidArr];

		vcard_element=inputContactObj.vcard.match(vCard.pre['contentline_UID']);

		// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
		parsed=vcard_element[0].match(vCard.pre['contentline_parse']);

		var changedContactGroups=new Array();

		if(this.contact_groups[inputContactObj.uid.replace(RegExp('/[^/]*$',''),'/')]==undefined)
			this.contact_groups[inputContactObj.uid.replace(RegExp('/[^/]*$',''),'/')]=new Array();

		for(var j=0;j<this.contact_groups[inputContactObj.uid.replace(RegExp('/[^/]*$',''),'/')].length;j++)
			for(var k=0;k<inputContactGroupsUidArr.length;k++)
				if(this.contact_groups[inputContactObj.uid.replace(RegExp('/[^/]*$',''),'/')][j].uid==inputContactGroupsUidArr[k])
				{
					// if the uuid is already a member we remove it from contact-group to avoid duplicate membership
					var vcard=this.contact_groups[inputContactObj.uid.replace(RegExp('/[^/]*$',''),'/')][j].vcard.replaceAll('\r\nX-ADDRESSBOOKSERVER-MEMBER:urn:uuid:'+parsed[4]+'\r\n','\r\n');
					var tmp=vcard.split('\r\n');
					tmp.splice(tmp.length-2,0,'X-ADDRESSBOOKSERVER-MEMBER:urn:uuid:'+parsed[4]);
					var changedVcard=tmp.join('\r\n');

					var d = new Date();
					utc=d.getUTCFullYear()+(d.getUTCMonth()+1<10 ? '0':'')+(d.getUTCMonth()+1)+(d.getUTCDate()<10 ? '0':'')+d.getUTCDate()+'T'+(d.getUTCHours()<10 ? '0':'')+d.getUTCHours()+(d.getUTCMinutes()<10 ? '0':'')+d.getUTCMinutes()+(d.getUTCSeconds()<10 ? '0':'')+d.getUTCSeconds()+'Z';
					changedVcard=changedVcard.replace(RegExp('\r\nREV:.*\r\n','mi'),'\r\nREV:'+utc+'\r\n');

					// "copy" of the original object
					changedContactGroups[changedContactGroups.length]=$.extend({},this.contact_groups[inputContactObj.uid.replace(RegExp('/[^/]*$',''),'/')][j]);
					// new modified vcard group	(normalisation is added to fix basic errors in invalid vCard)
					changedContactGroups[changedContactGroups.length-1].vcard=normalizeVcard(changedVcard);
				}
		return changedContactGroups;
	}

	// Contact group list is not sorted, instead "insert sort" is performed
	this.insertContactGroup=function(inputContact, forceReload)
	{
		if((inputContact.sortkey=this.getSortKey(inputContact.vcard,['last']))===false || (inputContact.displayvalue=this.getSortKey(inputContact.vcard,['last']))===false)
			return false;	//invalid vcard

		var makeActive=null;

		// do not insert entry with duplicate UID
		if(this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')]!=undefined)
			for(var i=0;i<this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')].length;i++)
				if(this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')][i].uid==inputContact.uid)
				{
					if(this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')][i].displayvalue==inputContact.displayvalue)
					{
						this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')][i]=inputContact;
						return 0;
					}
					else
					{
						if(this.contactGroupLoaded!=null && this.contactGroupLoaded.uid==inputContact.uid)
							makeActive=inputContact.uid;

						// the contact group name is changed and must be moved to correct place (we first remove it and then reinsert)
						this.removeContactGroup(inputContact.uid,false);
						break;
					}
				}

		// find the index where to insert the new contact group
		if(this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')]==undefined)
			this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')]=new Array();

		var insertIndex=this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')].length;
		for(var i=0;i<this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')].length;i++)
			if(this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')][i].sortkey.customCompare(inputContact.sortkey,globalSortAlphabet,1,false)==1)
			{
				insertIndex=i;
				break;
			}

		// insert the contact group
		this.contact_groups[inputContact.uid.replace(RegExp('/[^/]*$',''),'/')].splice(insertIndex,0,inputContact);

		// insert the contact group to interface
		var newElement=$('#ResourceCardDAVListTemplate').find('.resourceCardDAV_item').find('.contact_group').find('.group').clone().wrap('<div>');
		// the onclick event is disabled until the last drag&drop operation is completed (the class*="r_" is a little bit weak but works)
		newElement=newElement.attr('onclick','if($(this).parents(\':eq(2)\').find(\'[class*="r_"]\').length>0) return false; else globalResourceCardDAVList.loadAddressbookByUID(this.getAttribute(\'data-id\'));');
		newElement=newElement.attr('data-id',inputContact.uid);
		newElement.text(vcardUnescapeValue(inputContact.displayvalue));
		newElement.css('display','');
		newElement=newElement.parent().html();
		if($('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContact.uid.replace(RegExp('/[^/]*$',''),'/'))+'"]').next('.contact_group').find('[data-id="'+jqueryEscapeSelector(inputContact.uid)+'"]').length==0)
			$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContact.uid.replace(RegExp('/[^/]*$',''),'/'))+'"]').next('.contact_group').children().eq(insertIndex).after(newElement);

		// make the area droppable if the collection is not read-only
		if(globalResourceCardDAVList.getCollectionPrivByUID(inputContact.uid.replace(RegExp('[^/]*$',''),''))==false)
			$('#ResourceCardDAVList').find('[data-id="'+jqueryEscapeSelector(inputContact.uid.replace(RegExp('[^/]*$',''),''))+'"]').parent().find('.contact_group').children().eq(insertIndex+1).droppable({
				accept: '.ablist_item',
				tolerance: 'pointer',
				hoverClass: 'group_dropped_to',
				drop: function(event, ui){
					// animate the clone of the dropped (draggable) element
					var tmp=ui.helper.clone();
					tmp.appendTo('body')
					.animate({opacity: 0, color: 'transparent', height: 0, width: 0, fontSize: 0, lineHeight: 0, paddingLeft: 0, paddingRight: 0},750,function(){tmp.remove()});

					// disallow to drag the original dropped element until the processing is finished
					ui.draggable.draggable('option', 'disabled', true);

					// animate the original dropped element
					ui.draggable.animate({opacity: 0.3}, 750);

					// disallow to drop any new element until the processing is finished
					$(this).droppable('option', 'disabled', true);

					// show the loader icon
					$(this).addClass('r_operate');

					var tmp2=globalAddressbookList.getContactByUID(ui.draggable.attr('data-id'));
					tmp2.addToContactGroupUID=$(this).attr('data-id');
					tmp2.uiObjects={contact: ui.draggable, resource: $(this).attr('data-id')};

					lockAndPerformToCollection(tmp2, $('#AddContact').attr('data-filter-url'), 'ADD_TO_GROUP');
				}
			});

		// if no new makeActive but forceReload is true then reload the current contact group
		if(makeActive==null && forceReload==true)
			makeActive=$('#AddContact').attr('data-filter-url');

		// load the contact group if it was selected
		if(makeActive!=null)
		{
			$('#ResourceCardDAVList').find('.resourceCardDAV_item').find('.resourceCardDAV_selected').removeClass('resourceCardDAV_selected');
			$('#ResourceCardDAVList').find('[data-id='+jqueryEscapeSelector(makeActive.replace(RegExp('[^/]*$',''),''))+']').addClass('resourceCardDAV_selected');
			$('#ResourceCardDAVList').find('[data-id='+jqueryEscapeSelector(makeActive)+']').addClass('resourceCardDAV_selected');

			this.applyABFilter(makeActive, false);
		}
	}

	this.removeContactGroup=function(inputUid, loadNext)
	{
		for(var i=this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')].length-1;i>=0;i--)
			if(this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][i].uid==inputUid)
			{
				var uidRemoved=this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][i].uid;
				var item=$('#ResourceCardDAVList').find('[data-id^="'+jqueryEscapeSelector(this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][i].uid)+'"]');

				// remove the item
				item.remove();
				this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')].splice(i,1);

				if(loadNext && this.contactGroupLoaded!=null && this.contactGroupLoaded.uid==inputUid)
				{
					this.contactGroupLoaded=null;

					// set the whole collection as active
					var tmp=uidRemoved.match(RegExp('(^.*/)'),'');
					globalResourceCardDAVList.loadAddressbookByUID(tmp[1]);
				}
				break;
			}
	}

	// hide/show contacts in the interface according to contactGroupOrResourceUid or search filter in the interface (contactGroupOrResourceUid==false)
	this.applyABFilter=function(contactGroupOrResourceUid, inputForceLoadNext)
	{
		var vcardGroupOrCollection=null;
		// remember the loaded contact group

		if(contactGroupOrResourceUid===false)
		{
			if(this.contactGroupLoaded!=null)
				vcardGroupOrCollection=this.contactGroupLoaded;
		}
		else
		{
			this.contactGroupLoaded=null;
			if(contactGroupOrResourceUid[contactGroupOrResourceUid.length-1]=='/')
				vcardGroupOrCollection={uid: contactGroupOrResourceUid};
			else		// remember the loaded contact group
			{
				// required only if we want so support collection unloading
				if(this.contact_groups[contactGroupOrResourceUid.replace(RegExp('/[^/]*$',''),'/')]==undefined)
					this.contact_groups[contactGroupOrResourceUid.replace(RegExp('/[^/]*$',''),'/')]=new Array();

				for(var i=0;i<this.contact_groups[contactGroupOrResourceUid.replace(RegExp('/[^/]*$',''),'/')].length;i++)
					if(this.contact_groups[contactGroupOrResourceUid.replace(RegExp('/[^/]*$',''),'/')][i].uid==contactGroupOrResourceUid)
					{
						vcardGroupOrCollection=this.contactGroupLoaded=this.contact_groups[contactGroupOrResourceUid.replace(RegExp('/[^/]*$',''),'/')][i];
						break;
					}
			}
		}

		// no contactGroup filter specified (or invalid)
		if(vcardGroupOrCollection==null)	// never happens because one addressbook is always selected (no support for multiple addressbook selected at once)
		{
			// set all (except the hidden) contacts as active
			for(var i=0;i<this.contacts.length;i++)
				if(this.contacts[i].headerOnly==undefined)
				{
					if($('#ABList div[data-id="'+jqueryEscapeSelector(this.contacts[i].uid)+'"]').hasClass('search_hide')==false)
						this.contacts[i].show=true;
					else
						this.contacts[i].show=false;
				}
		}
		else
		{
			var previousActiveIndex=null;	// used to find the nearest contact and set it as selected

			if(this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=undefined && this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=null)
				var previousActiveUID=this.contactLoaded[$('#AddContact').attr('data-filter-url')].uid;

			// set all contacts as inactive
			for(var i=0;i<this.contacts.length;i++)
				if(this.contacts[i].headerOnly==undefined)
				{
					if(this.contacts[i].uid==previousActiveUID)
						previousActiveIndex=i;

					this.contacts[i].show=false;
				}

			if((vcard=vcardGroupOrCollection.vcard)==undefined)	// collection
			{
				for(var j=0;j<this.contacts.length;j++)
					if(this.contacts[j].headerOnly==undefined)
					{
						if(this.contacts[j].uid.indexOf(vcardGroupOrCollection.uid)==0 && $('#ABList div[data-id="'+jqueryEscapeSelector(this.contacts[j].uid)+'"]').hasClass('search_hide')==false)
							this.contacts[j].show=true;
					}
			}
			else	// vcard group
			{
				var vcardUIDList=new Array();
				// get the members of the array group
				while((vcard_element=vcard.match(vCard.pre['X-ADDRESSBOOKSERVER-MEMBER']))!=null)
				{
					// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
					parsed=vcard_element[0].match(vCard.pre['contentline_parse']);
					vcardUIDList[vcardUIDList.length]=parsed[4].replace('urn:uuid:','');
					// remove the processed parameter
					vcard=vcard.replace(vcard_element[0],'\r\n');
				}

				// update the contacts' "show" attribute
				for(var i=0;i<vcardUIDList.length;i++)
					for(var j=0;j<this.contacts.length;j++)
						if(this.contacts[j].headerOnly==undefined)
						{
							vcard_element=this.contacts[j].vcard.match(vCard.pre['contentline_UID']);

							if(vcard_element!=null)	// only for contacts with UID (non-RFC contacts not contains UID)
							{
								// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
								parsed=vcard_element[0].match(vCard.pre['contentline_parse']);

								if(vcardUIDList[i]==parsed[4] && $('#ABList div[data-id="'+jqueryEscapeSelector(this.contacts[j].uid)+'"]').hasClass('search_hide')==false)
									this.contacts[j].show=true;
							}
						}
			}
		}

		var lastActive=null;
		var prevHeader=null;
		var lastContactForHeader=this.contacts.length-1;
		// the show attribute is now set, we can make changes in the interface
		for(var i=this.contacts.length-1;i>=0;i--)
		{
			if(this.contacts[i].headerOnly==undefined)
			{
				// find the previous header index
				for(var j=i-1;j>=0;j--)
					if(this.contacts[j].headerOnly!=undefined && this.contacts[j].headerOnly==true)
					{
						prevHeader=j;
						break;
					}

				switch(this.contacts[i].show)
				{
					case false:
						$('#ABList').children().eq(i+1).css('display','none');
						if($('#ABList').children().eq(i+1).hasClass('ablist_item_selected'))
							lastActive=i;

						var hideHeader=true;
						for(j=prevHeader+1;j<=lastContactForHeader;j++)
							if(this.contacts[j].show==true)
							{
								hideHeader=false;
								break;
							}

						if(hideHeader)
							$('#ABList').children().eq(prevHeader+1).css('display','none');

						break;
					case true:
						// set the contact header to visible
						$('#ABList').children().eq(prevHeader+1).css('display','');

						// set the contact to visible
						$('#ABList').children().eq(i+1).css('display','');
						break;
				}
			}
			else
				lastContactForHeader=i-1;
		}

		// the previously loaded contact is hidden or not exists we need to select a new one
		if(inputForceLoadNext==true || $('[id=vcard_editor]').attr('data-editor-state')!='edit' && (lastActive!=null || $('#ABList').find('.ablist_item_selected').length==0))
		{
			var nextCandidateToLoad=null;
			// get the nearest candidate to load
			//  if we can go forward
			for(j=(previousActiveIndex==null ? 0 : previousActiveIndex);j<this.contacts.length;j++)
				if((this.contacts[j].headerOnly==undefined || this.contacts[j].headerOnly==false) && this.contacts[j].show==true)
				{
					nextCandidateToLoad=this.contacts[j];
					break;
				}
			//  we must go backwards
			if(nextCandidateToLoad==null && previousActiveIndex!=null)
			{
				for(j=previousActiveIndex-1;j>=0;j--)
					if((this.contacts[j].headerOnly==undefined || this.contacts[j].headerOnly==false) && this.contacts[j].show==true)
					{
						nextCandidateToLoad=this.contacts[j];
						break;
					}
			}

			// make the contact active
			$('#ABList').find('.ablist_item').removeClass('ablist_item_selected');
			if(nextCandidateToLoad!=null)
			{
				// prevent re-loading the contact if it is already loaded
				if($('#vcard_editor').attr('data-url')!=nextCandidateToLoad.uid)
					this.loadContactByUID(nextCandidateToLoad.uid);
				else	// because the collection click unselects the active contact we need to re-select it
				{
					// Make the selected contact active
					$('#ABList').find('.ablist_item').removeClass('ablist_item_selected');
					$('#ABList').find('[data-id='+jqueryEscapeSelector(nextCandidateToLoad.uid)+']').addClass('ablist_item_selected');
				}
				// move scrollbar to ensure that the contact is visible in the interface
				if((selected_contact=$('#ABList').find('.ablist_item_selected')).length==1)
						$('#ABList').scrollTop($('#ABList').scrollTop()+selected_contact.offset().top-$('#ABList').offset().top-$('#ABList').height()*globalKBNavigationPaddingRate);
			}
			else
			{
				this.contactLoaded[$('#AddContact').attr('data-filter-url')]=null;
				$('#ABContact').html('');
			}
		}
	}

	this.getABCategories=function()
	{
		var categoriesArr=[];

		for(var category in this.contact_categories)
			categoriesArr.push(category);

		return categoriesArr.sort(function(x,y){return x.customCompare(y,globalSortAlphabet,1,false)});
	}

	this.getABCompanies=function()
	{
		var companiesArr=[];

		for(var company in this.contact_companies)
			companiesArr.push(company);

		return companiesArr.sort(function(x,y){return x.customCompare(y,globalSortAlphabet,1,false)});
	}

	this.getABCompanyDepartments=function(inputCompany)
	{
		var departmentsArr=[];

		if(this.contact_companies[inputCompany]!=undefined)
			departmentsArr=this.contact_companies[inputCompany].departments.slice();

		return departmentsArr.sort(function(x,y){return x.customCompare(y,globalSortAlphabet,1,false)});
	}

	// Contact list is not sorted, instead "insert sort" is performed
	this.insertContact=function(inputContact, forceReload)
	{
		// Apple "group" vCards
		if(this.isContactGroup(inputContact.vcard))
			return this.insertContactGroup(inputContact, forceReload);

		if((inputContact.sortkey=this.getSortKey(inputContact.vcard,globalCollectionSort))===false || (inputContact.displayvalue=this.getSortKey(inputContact.vcard,globalCollectionDisplay))===false)
			return false;	//invalid vcard

		// CATEGORIES suggestion
		var categoriesArr=(inputContact.categories=='' ? [] : vcardSplitValue(inputContact.categories,','));
		var allCategoriesArr=this.getABCategories();

		// The search funcionality uses this ASCII value (you can add additional data here)
		// ORG attribute
		var tmp=inputContact.vcard;
		var orgArr=[];
		var depArr=[];
		while((vcard_element=tmp.match(vCard.pre['contentline_ORG']))!=null)
		{
			// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
			var parsed=vcard_element[0].match(vCard.pre['contentline_parse']);
			var parsed_valArr=vcardSplitValue(parsed[4],';');

			if(parsed_valArr[0]!=undefined && parsed_valArr[0]!='')
				orgArr[orgArr.length]=vcardUnescapeValue(parsed_valArr[0]);

			if(parsed_valArr[1]!=undefined && parsed_valArr[1]!='')
				depArr[depArr.length]=vcardUnescapeValue(parsed_valArr[1]);

			// remove the processed parameter
			tmp=tmp.replace(vcard_element[0],'\r\n');
		}
		var allOrgArr=this.getABCompanies();

		// N attribute
		var nArr=[];
		while((vcard_element=tmp.match(vCard.pre['contentline_N']))!=null)
		{
			// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
			var parsed=vcard_element[0].match(vCard.pre['contentline_parse']);
			var parsed_valArr=vcardSplitValue(parsed[4],';');

			for(var i=0;i<parsed_valArr.length;i++)
				if(parsed_valArr[i]!=undefined && parsed_valArr[i]!='')
					nArr[i]=(nArr[i]==undefined ? '' : nArr[i]+' ')+vcardUnescapeValue(parsed_valArr[i]);

			// remove the processed parameter
			tmp=tmp.replace(vcard_element[0],'\r\n');
		}

		// NICKNAME attribute
		var nicknameArr=[];
		while((vcard_element=tmp.match(vCard.pre['contentline_NICKNAME']))!=null)
		{
			// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
			parsed=vcard_element[0].match(vCard.pre['contentline_parse']);

			nicknameArr[nicknameArr.length]=parsed[4];

			// remove the processed parameter
			tmp=tmp.replace(vcard_element[0],'\r\n');
		}

		// TEL attribute
		var telArr=[];
		while((vcard_element=tmp.match(vCard.pre['contentline_TEL']))!=null)
		{
			// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
			parsed=vcard_element[0].match(vCard.pre['contentline_parse']);

			telArr[telArr.length]=(parsed[4].charAt(0)=='+' ? '+' : '')+parsed[4].replace(RegExp('[^0-9]','g'),'');

			// remove the processed parameter
			tmp=tmp.replace(vcard_element[0],'\r\n');
		}

		// EMAIL attribute
		var emailArr=[];
		while((vcard_element=tmp.match(vCard.pre['contentline_EMAIL']))!=null)
		{
			// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
			parsed=vcard_element[0].match(vCard.pre['contentline_parse']);

			emailArr[emailArr.length]=parsed[4];

			// remove the processed parameter
			tmp=tmp.replace(vcard_element[0],'\r\n');
		}

		// Search data (displayvalue+categories+orgs+emails)
		inputContact.searchvalue=(nArr.join(' ')+' '+nicknameArr.join(' ')+' '+categoriesArr.join(' ')+' '+orgArr.join(' ')+' '+telArr.join(' ')+' '+emailArr.join(' ')).multiReplace(globalSearchTransformAlphabet);

		// update search data here because contact is re-added only if displayvalue or isorg is changed
		//  note: simple .text(value) causes problems in Webkit based browsers (adds additional closing tag :-( ...)
		var tmp_searchdiv=$('#ABList').find('.ablist_item[data-id='+jqueryEscapeSelector(inputContact.uid)+']').find('div[data-type="searchable_data"]').contents();
		if(tmp_searchdiv.length>0){tmp_searchdiv=tmp_searchdiv.get(0); if(tmp_searchdiv.nodeType==Node.TEXT_NODE) tmp_searchdiv.nodeValue=inputContact.searchvalue;}

		// CATEGORIES suggestion
		for(var i=0;i<allCategoriesArr.length;i++)	// if a contact is changed remove it from previous categories
			if(categoriesArr.indexOf(allCategoriesArr[i])==-1)
			{
				var index=this.contact_categories[allCategoriesArr[i]].indexOf(inputContact.uid);
				if(index!=-1)
				{
					this.contact_categories[allCategoriesArr[i]].splice(index,1);

					if(this.contact_categories[allCategoriesArr[i]].length==0)
						delete this.contact_categories[allCategoriesArr[i]];
				}
			}
		for(var i=0;i<categoriesArr.length;i++)	// add contact to it's categories
			this.contact_categories[categoriesArr[i]]=(this.contact_categories[categoriesArr[i]]==undefined ? [] : this.contact_categories[categoriesArr[i]]).concat(inputContact.uid).sort().unique();

		// ORG suggestion
		for(var i=0;i<allOrgArr.length;i++)	// if a contact is changed remove it from previous companies
			if(orgArr.indexOf(allOrgArr[i])==-1)
			{
				var index=this.contact_companies[allOrgArr[i]].uids.indexOf(inputContact.uid);
				if(index!=-1)
				{
					this.contact_companies[allOrgArr[i]].uids.splice(index,1);

					if(this.contact_companies[allOrgArr[i]].uids.length==0)
						delete this.contact_companies[allOrgArr[i]];
				}
			}

		for(var i=0;i<orgArr.length;i++)	// add contact to it's companies
		{
			this.contact_companies[orgArr[i]]={uids: (this.contact_companies[orgArr[i]]==undefined ? [] : this.contact_companies[orgArr[i]].uids).concat(inputContact.uid).sort().unique(), departments: (this.contact_companies[orgArr[i]]==undefined ? [] : this.contact_companies[orgArr[i]].departments).concat(depArr).sort().unique()};
		}

		// check for company contact
		inputContact.isCompany=false;
		var vcard_element=inputContact.vcard.match(vCard.pre['X-ABShowAs']);
		if(vcard_element!=null)
		{
			// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
			parsed=vcard_element[0].match(vCard.pre['contentline_parse']);
			if(vcardUnescapeValue(parsed[4]).match(RegExp('^company$','i')))
				inputContact.isCompany=true;
		}

		var makeActive=null;

		// do not insert entry with duplicate UID
		for(var i=0;i<this.contacts.length;i++)
			if(this.contacts[i].uid!=undefined && this.contacts[i].uid==inputContact.uid)
			{
				if(this.contacts[i].displayvalue==inputContact.displayvalue && this.contacts[i].isCompany==inputContact.isCompany)
				{
					// we perform the normalization here, because we need to check whether the vCard is changed or not
					//  normalize the vCard when it's loaded first time
					if(inputContact.normalized==false)
					{
						inputContact.normalized=true;
						inputContact.vcard=normalizeVcard(additionalRFCFixes(inputContact.vcard));
					}
					this.contacts[i]=inputContact;

					// if the contact is loaded and the editor is in 'show' state, reload it
					if(this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=undefined && this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=null && this.contactLoaded[$('#AddContact').attr('data-filter-url')].uid==inputContact.uid && this.contactLoaded[$('#AddContact').attr('data-filter-url')].vcard!=inputContact.vcard &&  $('[id=vcard_editor]').attr('data-editor-state')=='show')
					{
						this.loadContactByUID(inputContact.uid);
						show_editor_message('in','message_success',localization[globalInterfaceLanguage].contactConcurrentChange,globalHideInfoMessageAfter);
						return 0;
					}
					else	// we are editing the contact or it is not active
						return -1;
				}
				else
				{
					if(this.contactLoaded[$('#AddContact').attr('data-filter-url')].uid==inputContact.uid)
					{
						makeActive=inputContact.uid;
						// if the contact is selected, we are editing it and forceReload mode is not set
					 	if($('[id=vcard_editor]').attr('data-editor-state')=='edit' && forceReload!=true)
							return -2;
					}

					// the contact name is changed and must be moved to correct place (we first remove it and then reinsert)
					this.removeContact(inputContact.uid,false);
					break;
				}
			}

		var headerChar='';
		// key value for most common non-alphabet characters is defined as '#'
		if(inputContact.sortkey[0]!=undefined)
		{
			var unicodeValue=inputContact.sortkey.charCodeAt(0);
			if(unicodeValue<65 || (unicodeValue>90 && unicodeValue<97) || (unicodeValue>122 && unicodeValue<127))
				headerChar='#';
			else
				headerChar=inputContact.sortkey.charAt(0).toUpperCase();
		}
		else
			headerChar='#';

		// create the header
		var headerObject={headerOnly: true, sortkey: headerChar, displayvalue: headerChar};

		// find the index where to insert the new contact O(n*log(n))
		insertIndex=0;
		low=0;
		high=this.contacts.length-1;
		if(this.contacts.length>0)
			while(low<high)
			{
				insertIndex=low+Math.round((high-low)/2);
				result=(this.contacts[insertIndex].headerOnly!=undefined ? cmp_str=this.contacts[insertIndex].displayvalue : cmp_str=this.contacts[insertIndex].sortkey).customCompare(inputContact.sortkey,globalSortAlphabet,1,false);
				if(result==-1)
				{
					if(insertIndex+1==this.contacts.length-1 && typeof this.contacts[insertIndex+1]!='undefined' && (cmp_str=this.contacts[insertIndex+1].sortkey).customCompare(inputContact.sortkey,globalSortAlphabet,1,false)==-1)
					{
						insertIndex+=2;
						break;
					}
					else
						low=++insertIndex;
				}
				else if(result==1)
				{
					if((cmp_str=this.contacts[insertIndex-1].sortkey).customCompare(inputContact.sortkey,globalSortAlphabet,1,false)==-1)
						break;
					else
						high=--insertIndex;
				}
			}

		// check for header existence
		var headerMiss=1;
		for(var i=0;i<this.contacts.length;i++)
			if(this.contacts[i].headerOnly!=undefined && this.contacts[i].headerOnly==true && this.contacts[i].displayvalue==headerObject.displayvalue)
				{headerMiss=0; break;}

		// insert the header if not exists
		if(headerMiss)
			this.contacts.splice(insertIndex,0,headerObject);
		// insert the contact
		this.contacts.splice(insertIndex+headerMiss,0,inputContact);

		// insert header to interface if not exists
		if(headerMiss)
		{
			var newElement=$('#ABListTemplate').find('.ablist_header').clone().wrap('<div>');
			newElement=newElement.text(headerObject.displayvalue);
			newElement=newElement.parent().html();
			$('#ABList').children().eq(insertIndex).after(newElement);
		}
		// insert the contact to interface
		var newElement=$('#ABListTemplate').find('.ablist_item').clone().wrap('<div>');
		newElement.attr('onclick','if($(this).hasClass(\'ablist_item_selected\')) return false; else globalAddressbookList.loadContactByUID(this.getAttribute(\'data-id\'));');
		newElement.attr('data-id',inputContact.uid);

		newElement.find('.ablist_item_data').text(vcardUnescapeValue(inputContact.displayvalue));
		newElement.find('div[data-type="searchable_data"]').text(vcardUnescapeValue(inputContact.searchvalue));

		// set the company icon
		if(inputContact.isCompany==true)
			newElement.addClass('company');

		newElement=newElement.parent().html();
		$('#ABList').children().eq(insertIndex+headerMiss).after(newElement);

		// if the collection is not read-only the element is draggable
		if(globalResourceCardDAVList.getCollectionPrivByUID(inputContact.uid.replace(RegExp('[^/]*$'),''))==false)
			$('#ABList').children().eq(insertIndex+headerMiss+1).draggable({
				delay: 250,
				revert: 'invalid',
				scroll: false,
				opacity: 0.8,
				stack: '#SystemCardDAV',
				containment: '#SystemCardDAV',
				appendTo: 'body',
				helper: function(){
					$('#ResourceCardDAVList').find('.resourceCardDAV.ui-droppable').droppable( 'option', 'accept', false);
					$('#ResourceCardDAVList').find('.group.ui-droppable').droppable( 'option', 'accept', false);

					$('#ResourceCardDAVList').find('.resourceCardDAV[data-id!='+jqueryEscapeSelector($(this).attr('data-id').replace(RegExp('[^/]+$'),''))+'].ui-droppable').droppable( 'option', 'accept', '.ablist_item');
					var myContactGroups=globalAddressbookList.getMyContactGroups(inputContact.uid);
					$('#ResourceCardDAVList').find('.group[data-id^='+jqueryEscapeSelector($(this).attr('data-id').replace(RegExp('[^/]+$'),''))+'].ui-droppable').not('.resourceCardDAV_selected').each(function(index, element){
						if(myContactGroups.indexOf($(element).attr('data-id'))==-1)
							$(element).droppable( 'option', 'accept', '.ablist_item');
					});

					var tmp=$(this).clone();
					tmp.addClass('ablist_item_dragged');
					// we cannot use .css() here, because we need to add !important (problem with Gecko based browsers)
					var tmp_style='max-width: '+$(this).outerWidth()+'px;';
					if($(this).css('background-image')!='none')
						tmp_style+='background-image: url(images/company_s_w.svg) !important;';
					tmp.attr('style', tmp_style);

					return tmp;
				}
			});

		// load the updated contact (because we first deleted it, we need to set it active)
		if(makeActive!=null)
		{
			// make the contact active
			$('#ABList').find('.ablist_item').removeClass('ablist_item_selected');
			$('#ABList').children().eq(insertIndex+headerMiss+1).addClass('ablist_item_selected');

			this.loadContactByUID(makeActive);
		}
	}

/*
	this.removeCollectionContacts=function(inputUid)
	{
		for(var i=this.contacts.length-1;i>=0;i--)
			if(this.contacts[i].uid!=undefined && this.contacts[i].uid.replace(RegExp('[^/]+$',''),'')==inputUid)
				this.removeContact(this.contacts[i].uid,true);
	}
*/

	this.removeContact=function(inputUid, loadNext)
	{
		if(this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')]==undefined)
			this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')]=new Array();

		// Apple "group" vCards
		for(var i=this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')].length-1;i>=0;i--)
			if(this.contact_groups[inputUid.replace(RegExp('/[^/]*$',''),'/')][i].uid==inputUid)
				return this.removeContactGroup(inputUid, loadNext);

		for(var i=this.contacts.length-1;i>=0;i--)
			if(this.contacts[i].uid==inputUid)
			{
				// CATEGORIES suggestion
				var categoriesArr=vcardSplitValue(this.contacts[i].categories,',');
				for(var j=0;j<categoriesArr.length;j++)
					if(this.contact_categories[categoriesArr[j]]!=undefined)
					{
						var index=this.contact_categories[categoriesArr[j]].indexOf(this.contacts[i].uid);
						if(index!=-1)
						{
							this.contact_categories[categoriesArr[j]].splice(index,1);

							if(this.contact_categories[categoriesArr[j]].length==0)
								delete this.contact_categories[categoriesArr[j]];
						}
					}

				// ORG suggestion
				var tmp=this.contacts[i].vcard;
				var orgArr=[];
				while((vcard_element=tmp.match(vCard.pre['contentline_ORG']))!=null)
				{
					// parsed (contentline_parse) = [1]->"group.", [2]->"name", [3]->";param;param", [4]->"value"
					parsed=vcard_element[0].match(vCard.pre['contentline_parse']);
					orgArr[orgArr.length]=vcardUnescapeValue(vcardSplitValue(parsed[4],';')[0]);

					// remove the processed parameter
					tmp=tmp.replace(vcard_element[0],'\r\n');
				}
				for(var j=0;j<orgArr.length;j++)
					if(this.contact_companies[orgArr[j]]!=undefined /* there is no suggestion for '' company */ && this.contact_companies[orgArr[j]].uids!=undefined)
					{
						var index=this.contact_companies[orgArr[j]].uids.indexOf(this.contacts[i].uid);
						if(index!=-1)
						{
							this.contact_companies[orgArr[j]].uids.splice(index,1);

							if(this.contact_companies[orgArr[j]].uids.length==0)
								delete this.contact_companies[orgArr[j]];
						}
					}

				var nextCandidateToLoad=null;
				var item=$('#ABList').find('[data-id^="'+jqueryEscapeSelector(this.contacts[i].uid)+'"]');

				// get the nearest candidate to load
				//  if we can go forward
				for(j=i+1;j<this.contacts.length;j++)
					if(this.contacts[j].headerOnly!=true && this.contacts[j].show==true)
					{
						nextCandidateToLoad=this.contacts[j];
						break;
					}
				//  we must go backwards
				if(nextCandidateToLoad==null)
				{
					for(j=i-1;j>=0;j--)
						if(this.contacts[j].headerOnly!=true && this.contacts[j].show==true)
						{
							nextCandidateToLoad=this.contacts[j];
							break;
						}
				}

				// remove the item
				item.remove();
				this.contacts.splice(i,1);

				// remove the header if there is no more contact
				var removeHeader=true;
				var prevHeader=null;
				// find the previous header index
				for(var j=i-1;j>=0;j--)
					if(this.contacts[j].headerOnly!=undefined && this.contacts[j].headerOnly==true)
					{
						prevHeader=j;
						break;
					}

				// check for contact existence for the found header
				if((prevHeader+1)<this.contacts.length && (this.contacts[prevHeader+1].headerOnly==undefined || this.contacts[prevHeader+1].headerOnly!=true))
					removeHeader=false;

				// remove the header
				if(removeHeader==true)
				{
					$('#ABList').children().eq(prevHeader+1).remove();
					this.contacts.splice(prevHeader,1);
				}

				// hide header if there is no more visible contacts
				var hideHeader=true;
				for(j=prevHeader+1;j<this.contacts.length && (this.contacts[j].headerOnly==undefined || this.contacts[j].headerOnly!=true);j++)
					if(this.contacts[j].show==true)
					{
						hideHeader=false;
						break;
					}

				if(hideHeader)
					$('#ABList').children().eq(prevHeader+1).css('display','none');

				// load next contact
				if(loadNext && this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=undefined && this.contactLoaded[$('#AddContact').attr('data-filter-url')]!=null && this.contactLoaded[$('#AddContact').attr('data-filter-url')].uid==inputUid)
				{
					if(nextCandidateToLoad!=null)
						this.loadContactByUID(nextCandidateToLoad.uid);
					else
					{
						this.contactLoaded[$('#AddContact').attr('data-filter-url')]=null;
						$('#ABContact').html('');
					}
				}
				break;
			}
	}

	this.checkAndTouchIfExists=function(inputUID,inputEtag,inputTimestamp)
	{
		for(var i=0;i<this.contacts.length;i++)
			if(this.contacts[i].timestamp!=undefined && this.contacts[i].uid==inputUID)
			{
				this.contacts[i].timestamp=inputTimestamp;

				if(this.contacts[i].etag==inputEtag)
					return true;
				else
					return false;
			}
		return false;
	}

	this.removeOldContacts=function(inputUidBase, inputTimestamp)
	{
		for(var i=this.contacts.length-1;i>=0;i--)
			if(this.contacts[i]!=undefined /* because the header can be deleted with the contact */ && this.contacts[i].timestamp!=undefined && this.contacts[i].uid.indexOf(inputUidBase)==0 && this.contacts[i].timestamp<inputTimestamp)
				this.removeContact(this.contacts[i].uid, true);
	}

	this.loadContactByUID=function(inputUID)
	{
		CardDAVeditor_cleanup(false);		// Editor initialization

		// find the inputUID contact
		for(var i=0;i<this.contacts.length;i++)
			if(this.contacts[i].uid!=undefined && this.contacts[i].uid==inputUID)
			{
				// normalize the vCard when it's loaded first time
				if(this.contacts[i].normalized==false)
				{
					this.contacts[i].normalized=true;
					this.contacts[i].vcard=normalizeVcard(additionalRFCFixes(this.contacts[i].vcard));
				}

				var is_readonly=globalResourceCardDAVList.getCollectionPrivByUID(this.contacts[i].uid.replace(RegExp('[^/]*$'),''));
				var loadContact=this.contactLoaded[$('#AddContact').attr('data-filter-url')]=this.contacts[i];

				if(vcardToData(loadContact,is_readonly))
					$('#EditorBox').fadeTo(100,1);
				else
					show_editor_message('out','message_error',localization[globalInterfaceLanguage].contactRfcNotCompliant,globalHideInfoMessageAfter);

				// Make the selected contact active
				$('#ABList').find('.ablist_item').removeClass('ablist_item_selected');
				$('#ABList').find('[data-id='+jqueryEscapeSelector(this.contacts[i].uid)+']').addClass('ablist_item_selected');

				break;
			}
	}

	this.getContactByUID=function(inputUID)
	{
		// find the inputUID contact
		for(var i=0;i<this.contacts.length;i++)
			if(this.contacts[i].uid==inputUID)
				return this.contacts[i];

		return null;
	}
}
