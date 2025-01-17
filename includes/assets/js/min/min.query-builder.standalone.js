/*!
 * jQuery.extendext 0.1.2
 *
 * Copyright 2014-2016 Damien "Mistic" Sorel (http://www.strangeplanet.fr)
 * Licensed under MIT (http://opensource.org/licenses/MIT)
 * 
 * Based on jQuery.extend by jQuery Foundation, Inc. and other contributors
 */
(function(root,factory){if(typeof define==='function'&&define.amd){define('jQuery.extendext',['jquery'],factory);}
else if(typeof module==='object'&&module.exports){module.exports=factory(require('jquery'));}
else{factory(root.jQuery);}}(this,function($){"use strict";$.extendext=function(){var options,name,src,copy,copyIsArray,clone,target=arguments[0]||{},i=1,length=arguments.length,deep=false,arrayMode='default';if(typeof target==="boolean"){deep=target;target=arguments[i++]||{};}
if(typeof target==="string"){arrayMode=target.toLowerCase();if(arrayMode!=='concat'&&arrayMode!=='replace'&&arrayMode!=='extend'){arrayMode='default';}
target=arguments[i++]||{};}
if(typeof target!=="object"&&!$.isFunction(target)){target={};}
if(i===length){target=this;i--;}
for(;i<length;i++){if((options=arguments[i])!==null){if($.isArray(options)&&arrayMode!=='default'){clone=target&&$.isArray(target)?target:[];switch(arrayMode){case'concat':target=clone.concat($.extend(deep,[],options));break;case'replace':target=$.extend(deep,[],options);break;case'extend':options.forEach(function(e,i){if(typeof e==='object'){var type=$.isArray(e)?[]:{};clone[i]=$.extendext(deep,arrayMode,clone[i]||type,e);}else if(clone.indexOf(e)===-1){clone.push(e);}});target=clone;break;}}else{for(name in options){src=target[name];copy=options[name];if(target===copy){continue;}
if(deep&&copy&&($.isPlainObject(copy)||(copyIsArray=$.isArray(copy)))){if(copyIsArray){copyIsArray=false;clone=src&&$.isArray(src)?src:[];}else{clone=src&&$.isPlainObject(src)?src:{};}
target[name]=$.extendext(deep,arrayMode,clone,copy);}else if(copy!==undefined){target[name]=copy;}}}}}
return target;};}));(function(){"use strict";var doT={name:"doT",version:"1.1.1",templateSettings:{evaluate:/\{\{([\s\S]+?(\}?)+)\}\}/g,interpolate:/\{\{=([\s\S]+?)\}\}/g,encode:/\{\{!([\s\S]+?)\}\}/g,use:/\{\{#([\s\S]+?)\}\}/g,useParams:/(^|[^\w$])def(?:\.|\[[\'\"])([\w$\.]+)(?:[\'\"]\])?\s*\:\s*([\w$\.]+|\"[^\"]+\"|\'[^\']+\'|\{[^\}]+\})/g,define:/\{\{##\s*([\w\.$]+)\s*(\:|=)([\s\S]+?)#\}\}/g,defineParams:/^\s*([\w$]+):([\s\S]+)/,conditional:/\{\{\?(\?)?\s*([\s\S]*?)\s*\}\}/g,iterate:/\{\{~\s*(?:\}\}|([\s\S]+?)\s*\:\s*([\w$]+)\s*(?:\:\s*([\w$]+))?\s*\}\})/g,varname:"it",strip:true,append:true,selfcontained:false,doNotSkipEncoded:false},template:undefined,compile:undefined,log:true},_globals;doT.encodeHTMLSource=function(doNotSkipEncoded){var encodeHTMLRules={"&":"&#38;","<":"&#60;",">":"&#62;",'"':"&#34;","'":"&#39;","/":"&#47;"},matchHTML=doNotSkipEncoded?/[&<>"'\/]/g:/&(?!#?\w+;)|<|>|"|'|\//g;return function(code){return code?code.toString().replace(matchHTML,function(m){return encodeHTMLRules[m]||m;}):"";};};_globals=(function(){return this||(0,eval)("this");}());if(typeof module!=="undefined"&&module.exports){module.exports=doT;}else if(typeof define==="function"&&define.amd){define('doT',function(){return doT;});}else{_globals.doT=doT;}
var startend={append:{start:"'+(",end:")+'",startencode:"'+encodeHTML("},split:{start:"';out+=(",end:");out+='",startencode:"';out+=encodeHTML("}},skip=/$^/;function resolveDefs(c,block,def){return((typeof block==="string")?block:block.toString()).replace(c.define||skip,function(m,code,assign,value){if(code.indexOf("def.")===0){code=code.substring(4);}
if(!(code in def)){if(assign===":"){if(c.defineParams)value.replace(c.defineParams,function(m,param,v){def[code]={arg:param,text:v};});if(!(code in def))def[code]=value;}else{new Function("def","def['"+code+"']="+value)(def);}}
return"";}).replace(c.use||skip,function(m,code){if(c.useParams)code=code.replace(c.useParams,function(m,s,d,param){if(def[d]&&def[d].arg&&param){var rw=(d+":"+param).replace(/'|\\/g,"_");def.__exp=def.__exp||{};def.__exp[rw]=def[d].text.replace(new RegExp("(^|[^\\w$])"+def[d].arg+"([^\\w$])","g"),"$1"+param+"$2");return s+"def.__exp['"+rw+"']";}});var v=new Function("def","return "+code)(def);return v?resolveDefs(c,v,def):v;});}
function unescape(code){return code.replace(/\\('|\\)/g,"$1").replace(/[\r\t\n]/g," ");}
doT.template=function(tmpl,c,def){c=c||doT.templateSettings;var cse=c.append?startend.append:startend.split,needhtmlencode,sid=0,indv,str=(c.use||c.define)?resolveDefs(c,tmpl,def||{}):tmpl;str=("var out='"+(c.strip?str.replace(/(^|\r|\n)\t* +| +\t*(\r|\n|$)/g," ").replace(/\r|\n|\t|\/\*[\s\S]*?\*\//g,""):str).replace(/'|\\/g,"\\$&").replace(c.interpolate||skip,function(m,code){return cse.start+unescape(code)+cse.end;}).replace(c.encode||skip,function(m,code){needhtmlencode=true;return cse.startencode+unescape(code)+cse.end;}).replace(c.conditional||skip,function(m,elsecase,code){return elsecase?(code?"';}else if("+unescape(code)+"){out+='":"';}else{out+='"):(code?"';if("+unescape(code)+"){out+='":"';}out+='");}).replace(c.iterate||skip,function(m,iterate,vname,iname){if(!iterate)return"';} } out+='";sid+=1;indv=iname||"i"+sid;iterate=unescape(iterate);return"';var arr"+sid+"="+iterate+";if(arr"+sid+"){var "+vname+","+indv+"=-1,l"+sid+"=arr"+sid+".length-1;while("+indv+"<l"+sid+"){"+vname+"=arr"+sid+"["+indv+"+=1];out+='";}).replace(c.evaluate||skip,function(m,code){return"';"+unescape(code)+"out+='";})
+"';return out;").replace(/\n/g,"\\n").replace(/\t/g,'\\t').replace(/\r/g,"\\r").replace(/(\s|;|\}|^|\{)out\+='';/g,'$1').replace(/\+''/g,"");if(needhtmlencode){if(!c.selfcontained&&_globals&&!_globals._encodeHTML)_globals._encodeHTML=doT.encodeHTMLSource(c.doNotSkipEncoded);str="var encodeHTML = typeof _encodeHTML !== 'undefined' ? _encodeHTML : ("+doT.encodeHTMLSource.toString()+"("+(c.doNotSkipEncoded||'')+"));"+str;}
try{return new Function(c.varname,str);}catch(e){if(typeof console!=="undefined")console.log("Could not create a template function: "+str);throw e;}};doT.compile=function(tmpl,def){return doT.template(tmpl,null,def);};}());
/*!
 * jQuery QueryBuilder 2.5.1
 * Copyright 2014-2018 Damien "Mistic" Sorel (http://www.strangeplanet.fr)
 * Licensed under MIT (http://opensource.org/licenses/MIT)
 */
(function(root,factory){if(typeof define=='function'&&define.amd){define('query-builder',['jquery','dot/doT','jquery-extendext'],factory);}
else if(typeof module==='object'&&module.exports){module.exports=factory(require('jquery'),require('dot/doT'),require('jquery-extendext'));}
else{factory(root.jQuery,root.doT);}}(this,function($,doT){"use strict";var QueryBuilder=function($el,options){$el[0].queryBuilder=this;this.$el=$el;this.settings=$.extendext(true,'replace',{},QueryBuilder.DEFAULTS,options);this.model=new Model();this.status={id:null,generated_id:false,group_id:0,rule_id:0,has_optgroup:false,has_operator_optgroup:false};this.filters=this.settings.filters;this.icons=this.settings.icons;this.operators=this.settings.operators;this.templates=this.settings.templates;this.plugins=this.settings.plugins;this.lang=null;if(QueryBuilder.regional['en']===undefined){Utils.error('Config','"i18n/en.js" not loaded.');}
this.lang=$.extendext(true,'replace',{},QueryBuilder.regional['en'],QueryBuilder.regional[this.settings.lang_code],this.settings.lang);if(this.settings.allow_groups===false){this.settings.allow_groups=0;}
else if(this.settings.allow_groups===true){this.settings.allow_groups=-1;}
Object.keys(this.templates).forEach(function(tpl){if(!this.templates[tpl]){this.templates[tpl]=QueryBuilder.templates[tpl];}
if(typeof this.templates[tpl]=='string'){this.templates[tpl]=doT.template(this.templates[tpl]);}},this);if(!this.$el.attr('id')){this.$el.attr('id','qb_'+Math.floor(Math.random()*99999));this.status.generated_id=true;}
this.status.id=this.$el.attr('id');this.$el.addClass('query-builder form-inline');this.filters=this.checkFilters(this.filters);this.operators=this.checkOperators(this.operators);this.bindEvents();this.initPlugins();};$.extend(QueryBuilder.prototype,{trigger:function(type){var event=new $.Event(this._tojQueryEvent(type),{builder:this});this.$el.triggerHandler(event,Array.prototype.slice.call(arguments,1));return event;},change:function(type,value){var event=new $.Event(this._tojQueryEvent(type,true),{builder:this,value:value});this.$el.triggerHandler(event,Array.prototype.slice.call(arguments,2));return event.value;},on:function(type,cb){this.$el.on(this._tojQueryEvent(type),cb);return this;},off:function(type,cb){this.$el.off(this._tojQueryEvent(type),cb);return this;},once:function(type,cb){this.$el.one(this._tojQueryEvent(type),cb);return this;},_tojQueryEvent:function(name,filter){return name.split(' ').map(function(type){return type+'.queryBuilder'+(filter?'.filter':'');}).join(' ');}});QueryBuilder.types={'string':'string','integer':'number','double':'number','date':'datetime','time':'datetime','datetime':'datetime','boolean':'boolean'};QueryBuilder.inputs=['text','number','textarea','radio','checkbox','select'];QueryBuilder.modifiable_options=['display_errors','allow_groups','allow_empty','default_condition','default_filter'];QueryBuilder.selectors={group_container:'.rules-group-container',rule_container:'.rule-container',filter_container:'.rule-filter-container',operator_container:'.rule-operator-container',value_container:'.rule-value-container',error_container:'.error-container',condition_container:'.rules-group-header .group-conditions',rule_header:'.rule-header',group_header:'.rules-group-header',group_actions:'.group-actions',rule_actions:'.rule-actions',rules_list:'.rules-group-body>.rules-list',group_condition:'.rules-group-header [name$=_cond]',rule_filter:'.rule-filter-container [name$=_filter]',rule_operator:'.rule-operator-container [name$=_operator]',rule_value:'.rule-value-container [name*=_value_]',add_rule:'[data-add=rule]',delete_rule:'[data-delete=rule]',add_group:'[data-add=group]',delete_group:'[data-delete=group]'};QueryBuilder.templates={};QueryBuilder.regional={};QueryBuilder.OPERATORS={equal:{type:'equal',nb_inputs:1,multiple:false,apply_to:['string','number','datetime','boolean']},not_equal:{type:'not_equal',nb_inputs:1,multiple:false,apply_to:['string','number','datetime','boolean']},in:{type:'in',nb_inputs:1,multiple:true,apply_to:['string','number','datetime']},not_in:{type:'not_in',nb_inputs:1,multiple:true,apply_to:['string','number','datetime']},less:{type:'less',nb_inputs:1,multiple:false,apply_to:['number','datetime']},less_or_equal:{type:'less_or_equal',nb_inputs:1,multiple:false,apply_to:['number','datetime']},greater:{type:'greater',nb_inputs:1,multiple:false,apply_to:['number','datetime']},greater_or_equal:{type:'greater_or_equal',nb_inputs:1,multiple:false,apply_to:['number','datetime']},between:{type:'between',nb_inputs:2,multiple:false,apply_to:['number','datetime']},not_between:{type:'not_between',nb_inputs:2,multiple:false,apply_to:['number','datetime']},begins_with:{type:'begins_with',nb_inputs:1,multiple:false,apply_to:['string']},not_begins_with:{type:'not_begins_with',nb_inputs:1,multiple:false,apply_to:['string']},contains:{type:'contains',nb_inputs:1,multiple:false,apply_to:['string']},not_contains:{type:'not_contains',nb_inputs:1,multiple:false,apply_to:['string']},ends_with:{type:'ends_with',nb_inputs:1,multiple:false,apply_to:['string']},not_ends_with:{type:'not_ends_with',nb_inputs:1,multiple:false,apply_to:['string']},is_empty:{type:'is_empty',nb_inputs:0,multiple:false,apply_to:['string']},is_not_empty:{type:'is_not_empty',nb_inputs:0,multiple:false,apply_to:['string']},is_null:{type:'is_null',nb_inputs:0,multiple:false,apply_to:['string','number','datetime','boolean']},is_not_null:{type:'is_not_null',nb_inputs:0,multiple:false,apply_to:['string','number','datetime','boolean']}};QueryBuilder.DEFAULTS={filters:[],plugins:[],sort_filters:false,display_errors:true,allow_groups:-1,allow_empty:false,conditions:['AND','OR'],default_condition:'AND',inputs_separator:' , ',select_placeholder:'------',display_empty_filter:true,default_filter:null,optgroups:{},default_rule_flags:{filter_readonly:false,operator_readonly:false,value_readonly:false,no_delete:false},default_group_flags:{condition_readonly:false,no_add_rule:false,no_add_group:false,no_delete:false},templates:{group:null,rule:null,filterSelect:null,operatorSelect:null,ruleValueSelect:null},lang_code:'en',lang:{},operators:['equal','not_equal','in','not_in','less','less_or_equal','greater','greater_or_equal','between','not_between','begins_with','not_begins_with','contains','not_contains','ends_with','not_ends_with','is_empty','is_not_empty','is_null','is_not_null'],icons:{add_group:'glyphicon glyphicon-plus-sign',add_rule:'glyphicon glyphicon-plus',remove_group:'glyphicon glyphicon-remove',remove_rule:'glyphicon glyphicon-remove',error:'glyphicon glyphicon-warning-sign'}};QueryBuilder.plugins={};QueryBuilder.defaults=function(options){if(typeof options=='object'){$.extendext(true,'replace',QueryBuilder.DEFAULTS,options);}
else if(typeof options=='string'){if(typeof QueryBuilder.DEFAULTS[options]=='object'){return $.extend(true,{},QueryBuilder.DEFAULTS[options]);}
else{return QueryBuilder.DEFAULTS[options];}}
else{return $.extend(true,{},QueryBuilder.DEFAULTS);}};QueryBuilder.define=function(name,fct,def){QueryBuilder.plugins[name]={fct:fct,def:def||{}};};QueryBuilder.extend=function(methods){$.extend(QueryBuilder.prototype,methods);};QueryBuilder.prototype.initPlugins=function(){if(!this.plugins){return;}
if($.isArray(this.plugins)){var tmp={};this.plugins.forEach(function(plugin){tmp[plugin]=null;});this.plugins=tmp;}
Object.keys(this.plugins).forEach(function(plugin){if(plugin in QueryBuilder.plugins){this.plugins[plugin]=$.extend(true,{},QueryBuilder.plugins[plugin].def,this.plugins[plugin]||{});QueryBuilder.plugins[plugin].fct.call(this,this.plugins[plugin]);}
else{Utils.error('Config','Unable to find plugin "{0}"',plugin);}},this);};QueryBuilder.prototype.getPluginOptions=function(name,property){var plugin;if(this.plugins&&this.plugins[name]){plugin=this.plugins[name];}
else if(QueryBuilder.plugins[name]){plugin=QueryBuilder.plugins[name].def;}
if(plugin){if(property){return plugin[property];}
else{return plugin;}}
else{Utils.error('Config','Unable to find plugin "{0}"',name);}};QueryBuilder.prototype.init=function(rules){this.trigger('afterInit');if(rules){this.setRules(rules);delete this.settings.rules;}
else{this.setRoot(true);}};QueryBuilder.prototype.checkFilters=function(filters){var definedFilters=[];if(!filters||filters.length===0){Utils.error('Config','Missing filters list');}
filters.forEach(function(filter,i){if(!filter.id){Utils.error('Config','Missing filter {0} id',i);}
if(definedFilters.indexOf(filter.id)!=-1){Utils.error('Config','Filter "{0}" already defined',filter.id);}
definedFilters.push(filter.id);if(!filter.type){filter.type='string';}
else if(!QueryBuilder.types[filter.type]){Utils.error('Config','Invalid type "{0}"',filter.type);}
if(!filter.input){filter.input=QueryBuilder.types[filter.type]==='number'?'number':'text';}
else if(typeof filter.input!='function'&&QueryBuilder.inputs.indexOf(filter.input)==-1){Utils.error('Config','Invalid input "{0}"',filter.input);}
if(filter.operators){filter.operators.forEach(function(operator){if(typeof operator!='string'){Utils.error('Config','Filter operators must be global operators types (string)');}});}
if(!filter.field){filter.field=filter.id;}
if(!filter.label){filter.label=filter.field;}
if(!filter.optgroup){filter.optgroup=null;}
else{this.status.has_optgroup=true;if(!this.settings.optgroups[filter.optgroup]){this.settings.optgroups[filter.optgroup]=filter.optgroup;}}
switch(filter.input){case'radio':case'checkbox':if(!filter.values||filter.values.length<1){Utils.error('Config','Missing filter "{0}" values',filter.id);}
break;case'select':var cleanValues=[];filter.has_optgroup=false;Utils.iterateOptions(filter.values,function(value,label,optgroup){cleanValues.push({value:value,label:label,optgroup:optgroup||null});if(optgroup){filter.has_optgroup=true;if(!this.settings.optgroups[optgroup]){this.settings.optgroups[optgroup]=optgroup;}}}.bind(this));if(filter.has_optgroup){filter.values=Utils.groupSort(cleanValues,'optgroup');}
else{filter.values=cleanValues;}
if(filter.placeholder){if(filter.placeholder_value===undefined){filter.placeholder_value=-1;}
filter.values.forEach(function(entry){if(entry.value==filter.placeholder_value){Utils.error('Config','Placeholder of filter "{0}" overlaps with one of its values',filter.id);}});}
break;}},this);if(this.settings.sort_filters){if(typeof this.settings.sort_filters=='function'){filters.sort(this.settings.sort_filters);}
else{var self=this;filters.sort(function(a,b){return self.translate(a.label).localeCompare(self.translate(b.label));});}}
if(this.status.has_optgroup){filters=Utils.groupSort(filters,'optgroup');}
return filters;};QueryBuilder.prototype.checkOperators=function(operators){var definedOperators=[];operators.forEach(function(operator,i){if(typeof operator=='string'){if(!QueryBuilder.OPERATORS[operator]){Utils.error('Config','Unknown operator "{0}"',operator);}
operators[i]=operator=$.extendext(true,'replace',{},QueryBuilder.OPERATORS[operator]);}
else{if(!operator.type){Utils.error('Config','Missing "type" for operator {0}',i);}
if(QueryBuilder.OPERATORS[operator.type]){operators[i]=operator=$.extendext(true,'replace',{},QueryBuilder.OPERATORS[operator.type],operator);}
if(operator.nb_inputs===undefined||operator.apply_to===undefined){Utils.error('Config','Missing "nb_inputs" and/or "apply_to" for operator "{0}"',operator.type);}}
if(definedOperators.indexOf(operator.type)!=-1){Utils.error('Config','Operator "{0}" already defined',operator.type);}
definedOperators.push(operator.type);if(!operator.optgroup){operator.optgroup=null;}
else{this.status.has_operator_optgroup=true;if(!this.settings.optgroups[operator.optgroup]){this.settings.optgroups[operator.optgroup]=operator.optgroup;}}},this);if(this.status.has_operator_optgroup){operators=Utils.groupSort(operators,'optgroup');}
return operators;};QueryBuilder.prototype.bindEvents=function(){var self=this;var Selectors=QueryBuilder.selectors;this.$el.on('change.queryBuilder',Selectors.group_condition,function(){if($(this).is(':checked')){var $group=$(this).closest(Selectors.group_container);self.getModel($group).condition=$(this).val();}});this.$el.on('change.queryBuilder',Selectors.rule_filter,function(){var $rule=$(this).closest(Selectors.rule_container);self.getModel($rule).filter=self.getFilterById($(this).val());});this.$el.on('change.queryBuilder',Selectors.rule_operator,function(){var $rule=$(this).closest(Selectors.rule_container);self.getModel($rule).operator=self.getOperatorByType($(this).val());});this.$el.on('click.queryBuilder',Selectors.add_rule,function(){var $group=$(this).closest(Selectors.group_container);self.addRule(self.getModel($group));});this.$el.on('click.queryBuilder',Selectors.delete_rule,function(){var $rule=$(this).closest(Selectors.rule_container);self.deleteRule(self.getModel($rule));});if(this.settings.allow_groups!==0){this.$el.on('click.queryBuilder',Selectors.add_group,function(){var $group=$(this).closest(Selectors.group_container);self.addGroup(self.getModel($group));});this.$el.on('click.queryBuilder',Selectors.delete_group,function(){var $group=$(this).closest(Selectors.group_container);self.deleteGroup(self.getModel($group));});}
this.model.on({'drop':function(e,node){node.$el.remove();self.refreshGroupsConditions();},'add':function(e,parent,node,index){if(index===0){node.$el.prependTo(parent.$el.find('>'+QueryBuilder.selectors.rules_list));}
else{node.$el.insertAfter(parent.rules[index-1].$el);}
self.refreshGroupsConditions();},'move':function(e,node,group,index){node.$el.detach();if(index===0){node.$el.prependTo(group.$el.find('>'+QueryBuilder.selectors.rules_list));}
else{node.$el.insertAfter(group.rules[index-1].$el);}
self.refreshGroupsConditions();},'update':function(e,node,field,value,oldValue){if(node instanceof Rule){switch(field){case'error':self.updateError(node);break;case'flags':self.applyRuleFlags(node);break;case'filter':self.updateRuleFilter(node,oldValue);break;case'operator':self.updateRuleOperator(node,oldValue);break;case'value':self.updateRuleValue(node,oldValue);break;}}
else{switch(field){case'error':self.updateError(node);break;case'flags':self.applyGroupFlags(node);break;case'condition':self.updateGroupCondition(node,oldValue);break;}}}});};QueryBuilder.prototype.setRoot=function(addRule,data,flags){addRule=(addRule===undefined||addRule===true);var group_id=this.nextGroupId();var $group=$(this.getGroupTemplate(group_id,1));this.$el.append($group);this.model.root=new Group(null,$group);this.model.root.model=this.model;this.model.root.data=data;this.model.root.__.flags=$.extend({},this.settings.default_group_flags,flags);this.trigger('afterAddGroup',this.model.root);this.model.root.condition=this.settings.default_condition;if(addRule){this.addRule(this.model.root);}
return this.model.root;};QueryBuilder.prototype.addGroup=function(parent,addRule,data,flags){addRule=(addRule===undefined||addRule===true);var level=parent.level+1;var e=this.trigger('beforeAddGroup',parent,addRule,level);if(e.isDefaultPrevented()){return null;}
var group_id=this.nextGroupId();var $group=$(this.getGroupTemplate(group_id,level));var model=parent.addGroup($group);model.data=data;model.__.flags=$.extend({},this.settings.default_group_flags,flags);this.trigger('afterAddGroup',model);this.trigger('rulesChanged');model.condition=this.settings.default_condition;if(addRule){this.addRule(model);}
return model;};QueryBuilder.prototype.deleteGroup=function(group){if(group.isRoot()){return false;}
var e=this.trigger('beforeDeleteGroup',group);if(e.isDefaultPrevented()){return false;}
var del=true;group.each('reverse',function(rule){del&=this.deleteRule(rule);},function(group){del&=this.deleteGroup(group);},this);if(del){group.drop();this.trigger('afterDeleteGroup');this.trigger('rulesChanged');}
return del;};QueryBuilder.prototype.updateGroupCondition=function(group,previousCondition){group.$el.find('>'+QueryBuilder.selectors.group_condition).each(function(){var $this=$(this);$this.prop('checked',$this.val()===group.condition);$this.parent().toggleClass('active',$this.val()===group.condition);});this.trigger('afterUpdateGroupCondition',group,previousCondition);this.trigger('rulesChanged');};QueryBuilder.prototype.refreshGroupsConditions=function(){(function walk(group){if(!group.flags||(group.flags&&!group.flags.condition_readonly)){group.$el.find('>'+QueryBuilder.selectors.group_condition).prop('disabled',group.rules.length<=1).parent().toggleClass('disabled',group.rules.length<=1);}
group.each(null,function(group){walk(group);},this);}(this.model.root));};QueryBuilder.prototype.addRule=function(parent,data,flags){var e=this.trigger('beforeAddRule',parent);if(e.isDefaultPrevented()){return null;}
var rule_id=this.nextRuleId();var $rule=$(this.getRuleTemplate(rule_id));var model=parent.addRule($rule);if(data!==undefined){model.data=data;}
model.__.flags=$.extend({},this.settings.default_rule_flags,flags);this.trigger('afterAddRule',model);this.trigger('rulesChanged');this.createRuleFilters(model);if(this.settings.default_filter||!this.settings.display_empty_filter){model.filter=this.change('getDefaultFilter',this.getFilterById(this.settings.default_filter||this.filters[0].id),model);}
return model;};QueryBuilder.prototype.deleteRule=function(rule){if(rule.flags.no_delete){return false;}
var e=this.trigger('beforeDeleteRule',rule);if(e.isDefaultPrevented()){return false;}
rule.drop();this.trigger('afterDeleteRule');this.trigger('rulesChanged');return true;};QueryBuilder.prototype.createRuleFilters=function(rule){var filters=this.change('getRuleFilters',this.filters,rule);var $filterSelect=$(this.getRuleFilterSelect(rule,filters));rule.$el.find(QueryBuilder.selectors.filter_container).html($filterSelect);this.trigger('afterCreateRuleFilters',rule);};QueryBuilder.prototype.createRuleOperators=function(rule){var $operatorContainer=rule.$el.find(QueryBuilder.selectors.operator_container).empty();if(!rule.filter){return;}
var operators=this.getOperators(rule.filter);var $operatorSelect=$(this.getRuleOperatorSelect(rule,operators));$operatorContainer.html($operatorSelect);if(rule.filter.default_operator){rule.__.operator=this.getOperatorByType(rule.filter.default_operator);}
else{rule.__.operator=operators[0];}
rule.$el.find(QueryBuilder.selectors.rule_operator).val(rule.operator.type);this.trigger('afterCreateRuleOperators',rule,operators);};QueryBuilder.prototype.createRuleInput=function(rule){var $valueContainer=rule.$el.find(QueryBuilder.selectors.value_container).empty();rule.__.value=undefined;if(!rule.filter||!rule.operator||rule.operator.nb_inputs===0){return;}
var self=this;var $inputs=$();var filter=rule.filter;for(var i=0;i<rule.operator.nb_inputs;i++){var $ruleInput=$(this.getRuleInput(rule,i));if(i>0)$valueContainer.append(this.settings.inputs_separator);$valueContainer.append($ruleInput);$inputs=$inputs.add($ruleInput);}
$valueContainer.css('display','');$inputs.on('change '+(filter.input_event||''),function(){if(!rule._updating_input){rule._updating_value=true;rule.value=self.getRuleInputValue(rule);rule._updating_value=false;}});if(filter.plugin){$inputs[filter.plugin](filter.plugin_config||{});}
this.trigger('afterCreateRuleInput',rule);if(filter.default_value!==undefined){rule.value=filter.default_value;}
else{rule._updating_value=true;rule.value=self.getRuleInputValue(rule);rule._updating_value=false;}};QueryBuilder.prototype.updateRuleFilter=function(rule,previousFilter){this.createRuleOperators(rule);this.createRuleInput(rule);rule.$el.find(QueryBuilder.selectors.rule_filter).val(rule.filter?rule.filter.id:'-1');if(previousFilter&&rule.filter&&previousFilter.id!==rule.filter.id){rule.data=undefined;}
this.trigger('afterUpdateRuleFilter',rule,previousFilter);this.trigger('rulesChanged');};QueryBuilder.prototype.updateRuleOperator=function(rule,previousOperator){var $valueContainer=rule.$el.find(QueryBuilder.selectors.value_container);if(!rule.operator||rule.operator.nb_inputs===0){$valueContainer.hide();rule.__.value=undefined;}
else{$valueContainer.css('display','');if($valueContainer.is(':empty')||!previousOperator||rule.operator.nb_inputs!==previousOperator.nb_inputs||rule.operator.optgroup!==previousOperator.optgroup){this.createRuleInput(rule);}}
if(rule.operator){rule.$el.find(QueryBuilder.selectors.rule_operator).val(rule.operator.type);rule.__.value=this.getRuleInputValue(rule);}
this.trigger('afterUpdateRuleOperator',rule,previousOperator);this.trigger('rulesChanged');};QueryBuilder.prototype.updateRuleValue=function(rule,previousValue){if(!rule._updating_value){this.setRuleInputValue(rule,rule.value);}
this.trigger('afterUpdateRuleValue',rule,previousValue);this.trigger('rulesChanged');};QueryBuilder.prototype.applyRuleFlags=function(rule){var flags=rule.flags;var Selectors=QueryBuilder.selectors;if(flags.filter_readonly){rule.$el.find(Selectors.rule_filter).prop('disabled',true);}
if(flags.operator_readonly){rule.$el.find(Selectors.rule_operator).prop('disabled',true);}
if(flags.value_readonly){rule.$el.find(Selectors.rule_value).prop('disabled',true);}
if(flags.no_delete){rule.$el.find(Selectors.delete_rule).remove();}
this.trigger('afterApplyRuleFlags',rule);};QueryBuilder.prototype.applyGroupFlags=function(group){var flags=group.flags;var Selectors=QueryBuilder.selectors;if(flags.condition_readonly){group.$el.find('>'+Selectors.group_condition).prop('disabled',true).parent().addClass('readonly');}
if(flags.no_add_rule){group.$el.find(Selectors.add_rule).remove();}
if(flags.no_add_group){group.$el.find(Selectors.add_group).remove();}
if(flags.no_delete){group.$el.find(Selectors.delete_group).remove();}
this.trigger('afterApplyGroupFlags',group);};QueryBuilder.prototype.clearErrors=function(node){node=node||this.model.root;if(!node){return;}
node.error=null;if(node instanceof Group){node.each(function(rule){rule.error=null;},function(group){this.clearErrors(group);},this);}};QueryBuilder.prototype.updateError=function(node){if(this.settings.display_errors){if(node.error===null){node.$el.removeClass('has-error');}
else{var errorMessage=this.translate('errors',node.error[0]);errorMessage=Utils.fmt(errorMessage,node.error.slice(1));errorMessage=this.change('displayError',errorMessage,node.error,node);node.$el.addClass('has-error').find(QueryBuilder.selectors.error_container).eq(0).attr('title',errorMessage);}}};QueryBuilder.prototype.triggerValidationError=function(node,error,value){if(!$.isArray(error)){error=[error];}
var e=this.trigger('validationError',node,error,value);if(!e.isDefaultPrevented()){node.error=error;}};QueryBuilder.prototype.destroy=function(){this.trigger('beforeDestroy');if(this.status.generated_id){this.$el.removeAttr('id');}
this.clear();this.model=null;this.$el.off('.queryBuilder').removeClass('query-builder').removeData('queryBuilder');delete this.$el[0].queryBuilder;};QueryBuilder.prototype.reset=function(){var e=this.trigger('beforeReset');if(e.isDefaultPrevented()){return;}
this.status.group_id=1;this.status.rule_id=0;this.model.root.empty();this.addRule(this.model.root);this.trigger('afterReset');this.trigger('rulesChanged');};QueryBuilder.prototype.clear=function(){var e=this.trigger('beforeClear');if(e.isDefaultPrevented()){return;}
this.status.group_id=0;this.status.rule_id=0;if(this.model.root){this.model.root.drop();this.model.root=null;}
this.trigger('afterClear');this.trigger('rulesChanged');};QueryBuilder.prototype.setOptions=function(options){$.each(options,function(opt,value){if(QueryBuilder.modifiable_options.indexOf(opt)!==-1){this.settings[opt]=value;}}.bind(this));};QueryBuilder.prototype.getModel=function(target){if(!target){return this.model.root;}
else if(target instanceof Node){return target;}
else{return $(target).data('queryBuilderModel');}};QueryBuilder.prototype.validate=function(options){options=$.extend({skip_empty:false},options);this.clearErrors();var self=this;var valid=(function parse(group){var done=0;var errors=0;group.each(function(rule){if(!rule.filter&&options.skip_empty){return;}
if(!rule.filter){self.triggerValidationError(rule,'no_filter',null);errors++;return;}
if(!rule.operator){self.triggerValidationError(rule,'no_operator',null);errors++;return;}
if(rule.operator.nb_inputs!==0){var valid=self.validateValue(rule,rule.value);if(valid!==true){self.triggerValidationError(rule,valid,rule.value);errors++;return;}}
done++;},function(group){var res=parse(group);if(res===true){done++;}
else if(res===false){errors++;}});if(errors>0){return false;}
else if(done===0&&!group.isRoot()&&options.skip_empty){return null;}
else if(done===0&&(!self.settings.allow_empty||!group.isRoot())){self.triggerValidationError(group,'empty_group',null);return false;}
return true;}(this.model.root));return this.change('validate',valid);};QueryBuilder.prototype.getRules=function(options){options=$.extend({get_flags:false,allow_invalid:false,skip_empty:false},options);var valid=this.validate(options);if(!valid&&!options.allow_invalid){return null;}
var self=this;var out=(function parse(group){var groupData={condition:group.condition,rules:[]};if(group.data){groupData.data=$.extendext(true,'replace',{},group.data);}
if(options.get_flags){var flags=self.getGroupFlags(group.flags,options.get_flags==='all');if(!$.isEmptyObject(flags)){groupData.flags=flags;}}
group.each(function(rule){if(!rule.filter&&options.skip_empty){return;}
var value=null;if(!rule.operator||rule.operator.nb_inputs!==0){value=rule.value;}
var ruleData={id:rule.filter?rule.filter.id:null,field:rule.filter?rule.filter.field:null,type:rule.filter?rule.filter.type:null,input:rule.filter?rule.filter.input:null,operator:rule.operator?rule.operator.type:null,value:value};if(rule.filter&&rule.filter.data||rule.data){ruleData.data=$.extendext(true,'replace',{},rule.filter.data,rule.data);}
if(options.get_flags){var flags=self.getRuleFlags(rule.flags,options.get_flags==='all');if(!$.isEmptyObject(flags)){ruleData.flags=flags;}}
groupData.rules.push(self.change('ruleToJson',ruleData,rule));},function(model){var data=parse(model);if(data.rules.length!==0||!options.skip_empty){groupData.rules.push(data);}},this);return self.change('groupToJson',groupData,group);}(this.model.root));out.valid=valid;return this.change('getRules',out);};QueryBuilder.prototype.setRules=function(data,options){options=$.extend({allow_invalid:false},options);if($.isArray(data)){data={condition:this.settings.default_condition,rules:data};}
if(!data||!data.rules||(data.rules.length===0&&!this.settings.allow_empty)){Utils.error('RulesParse','Incorrect data object passed');}
this.clear();this.setRoot(false,data.data,this.parseGroupFlags(data));this.applyGroupFlags(this.model.root);data=this.change('setRules',data,options);var self=this;(function add(data,group){if(group===null){return;}
if(data.condition===undefined){data.condition=self.settings.default_condition;}
else if(self.settings.conditions.indexOf(data.condition)==-1){Utils.error(!options.allow_invalid,'UndefinedCondition','Invalid condition "{0}"',data.condition);data.condition=self.settings.default_condition;}
group.condition=data.condition;data.rules.forEach(function(item){var model;if(item.rules!==undefined){if(self.settings.allow_groups!==-1&&self.settings.allow_groups<group.level){Utils.error(!options.allow_invalid,'RulesParse','No more than {0} groups are allowed',self.settings.allow_groups);self.reset();}
else{model=self.addGroup(group,false,item.data,self.parseGroupFlags(item));if(model===null){return;}
self.applyGroupFlags(model);add(item,model);}}
else{if(!item.empty){if(item.id===undefined){Utils.error(!options.allow_invalid,'RulesParse','Missing rule field id');item.empty=true;}
if(item.operator===undefined){item.operator='equal';}}
model=self.addRule(group,item.data,self.parseRuleFlags(item));if(model===null){return;}
if(!item.empty){model.filter=self.getFilterById(item.id,!options.allow_invalid);}
if(model.filter){model.operator=self.getOperatorByType(item.operator,!options.allow_invalid);if(!model.operator){model.operator=self.getOperators(model.filter)[0];}}
if(model.operator&&model.operator.nb_inputs!==0){if(item.value!==undefined){model.value=item.value;}
else if(model.filter.default_value!==undefined){model.value=model.filter.default_value;}}
self.applyRuleFlags(model);if(self.change('jsonToRule',model,item)!=model){Utils.error('RulesParse','Plugin tried to change rule reference');}}});if(self.change('jsonToGroup',group,data)!=group){Utils.error('RulesParse','Plugin tried to change group reference');}}(data,this.model.root));this.trigger('afterSetRules');};QueryBuilder.prototype.validateValue=function(rule,value){var validation=rule.filter.validation||{};var result=true;if(validation.callback){result=validation.callback.call(this,value,rule);}
else{result=this._validateValue(rule,value);}
return this.change('validateValue',result,value,rule);};QueryBuilder.prototype._validateValue=function(rule,value){var filter=rule.filter;var operator=rule.operator;var validation=filter.validation||{};var result=true;var tmp,tempValue;if(rule.operator.nb_inputs===1){value=[value];}
for(var i=0;i<operator.nb_inputs;i++){if(!operator.multiple&&$.isArray(value[i])&&value[i].length>1){result=['operator_not_multiple',operator.type,this.translate('operators',operator.type)];break;}
switch(filter.input){case'radio':if(value[i]===undefined||value[i].length===0){if(!validation.allow_empty_value){result=['radio_empty'];}
break;}
break;case'checkbox':if(value[i]===undefined||value[i].length===0){if(!validation.allow_empty_value){result=['checkbox_empty'];}
break;}
break;case'select':if(value[i]===undefined||value[i].length===0||(filter.placeholder&&value[i]==filter.placeholder_value)){if(!validation.allow_empty_value){result=['select_empty'];}
break;}
break;default:tempValue=$.isArray(value[i])?value[i]:[value[i]];for(var j=0;j<tempValue.length;j++){switch(QueryBuilder.types[filter.type]){case'string':if(tempValue[j]===undefined||tempValue[j].length===0){if(!validation.allow_empty_value){result=['string_empty'];}
break;}
if(validation.min!==undefined){if(tempValue[j].length<parseInt(validation.min)){result=[this.getValidationMessage(validation,'min','string_exceed_min_length'),validation.min];break;}}
if(validation.max!==undefined){if(tempValue[j].length>parseInt(validation.max)){result=[this.getValidationMessage(validation,'max','string_exceed_max_length'),validation.max];break;}}
if(validation.format){if(typeof validation.format=='string'){validation.format=new RegExp(validation.format);}
if(!validation.format.test(tempValue[j])){result=[this.getValidationMessage(validation,'format','string_invalid_format'),validation.format];break;}}
break;case'number':if(tempValue[j]===undefined||tempValue[j].length===0){if(!validation.allow_empty_value){result=['number_nan'];}
break;}
if(isNaN(tempValue[j])){result=['number_nan'];break;}
if(filter.type=='integer'){if(parseInt(tempValue[j])!=tempValue[j]){result=['number_not_integer'];break;}}
else{if(parseFloat(tempValue[j])!=tempValue[j]){result=['number_not_double'];break;}}
if(validation.min!==undefined){if(tempValue[j]<parseFloat(validation.min)){result=[this.getValidationMessage(validation,'min','number_exceed_min'),validation.min];break;}}
if(validation.max!==undefined){if(tempValue[j]>parseFloat(validation.max)){result=[this.getValidationMessage(validation,'max','number_exceed_max'),validation.max];break;}}
if(validation.step!==undefined&&validation.step!=='any'){var v=(tempValue[j]/validation.step).toPrecision(14);if(parseInt(v)!=v){result=[this.getValidationMessage(validation,'step','number_wrong_step'),validation.step];break;}}
break;case'datetime':if(tempValue[j]===undefined||tempValue[j].length===0){if(!validation.allow_empty_value){result=['datetime_empty'];}
break;}
if(validation.format){if(!('moment'in window)){Utils.error('MissingLibrary','MomentJS is required for Date/Time validation. Get it here http://momentjs.com');}
var datetime=moment(tempValue[j],validation.format);if(!datetime.isValid()){result=[this.getValidationMessage(validation,'format','datetime_invalid'),validation.format];break;}
else{if(validation.min){if(datetime<moment(validation.min,validation.format)){result=[this.getValidationMessage(validation,'min','datetime_exceed_min'),validation.min];break;}}
if(validation.max){if(datetime>moment(validation.max,validation.format)){result=[this.getValidationMessage(validation,'max','datetime_exceed_max'),validation.max];break;}}}}
break;case'boolean':if(tempValue[j]===undefined||tempValue[j].length===0){if(!validation.allow_empty_value){result=['boolean_not_valid'];}
break;}
tmp=(''+tempValue[j]).trim().toLowerCase();if(tmp!=='true'&&tmp!=='false'&&tmp!=='1'&&tmp!=='0'&&tempValue[j]!==1&&tempValue[j]!==0){result=['boolean_not_valid'];break;}}
if(result!==true){break;}}}
if(result!==true){break;}}
if((rule.operator.type==='between'||rule.operator.type==='not_between')&&value.length===2){switch(QueryBuilder.types[filter.type]){case'number':if(value[0]>value[1]){result=['number_between_invalid',value[0],value[1]];}
break;case'datetime':if(validation.format){if(!('moment'in window)){Utils.error('MissingLibrary','MomentJS is required for Date/Time validation. Get it here http://momentjs.com');}
if(moment(value[0],validation.format).isAfter(moment(value[1],validation.format))){result=['datetime_between_invalid',value[0],value[1]];}}
break;}}
return result;};QueryBuilder.prototype.nextGroupId=function(){return this.status.id+'_group_'+(this.status.group_id++);};QueryBuilder.prototype.nextRuleId=function(){return this.status.id+'_rule_'+(this.status.rule_id++);};QueryBuilder.prototype.getOperators=function(filter){if(typeof filter=='string'){filter=this.getFilterById(filter);}
var result=[];for(var i=0,l=this.operators.length;i<l;i++){if(filter.operators){if(filter.operators.indexOf(this.operators[i].type)==-1){continue;}}
else if(this.operators[i].apply_to.indexOf(QueryBuilder.types[filter.type])==-1){continue;}
result.push(this.operators[i]);}
if(filter.operators){result.sort(function(a,b){return filter.operators.indexOf(a.type)-filter.operators.indexOf(b.type);});}
return this.change('getOperators',result,filter);};QueryBuilder.prototype.getFilterById=function(id,doThrow){if(id=='-1'){return null;}
for(var i=0,l=this.filters.length;i<l;i++){if(this.filters[i].id==id){return this.filters[i];}}
Utils.error(doThrow!==false,'UndefinedFilter','Undefined filter "{0}"',id);return null;};QueryBuilder.prototype.getOperatorByType=function(type,doThrow){if(type=='-1'){return null;}
for(var i=0,l=this.operators.length;i<l;i++){if(this.operators[i].type==type){return this.operators[i];}}
Utils.error(doThrow!==false,'UndefinedOperator','Undefined operator "{0}"',type);return null;};QueryBuilder.prototype.getRuleInputValue=function(rule){var filter=rule.filter;var operator=rule.operator;var value=[];if(filter.valueGetter){value=filter.valueGetter.call(this,rule);}
else{var $value=rule.$el.find(QueryBuilder.selectors.value_container);for(var i=0;i<operator.nb_inputs;i++){var name=Utils.escapeElementId(rule.id+'_value_'+i);var tmp;switch(filter.input){case'radio':value.push($value.find('[name='+name+']:checked').val());break;case'checkbox':tmp=[];$value.find('[name='+name+']:checked').each(function(){tmp.push($(this).val());});value.push(tmp);break;case'select':if(filter.multiple){tmp=[];$value.find('[name='+name+'] option:selected').each(function(){tmp.push($(this).val());});value.push(tmp);}
else{value.push($value.find('[name='+name+'] option:selected').val());}
break;default:value.push($value.find('[name='+name+']').val());}}
value=value.map(function(val){if(operator.multiple&&filter.value_separator&&typeof val=='string'){val=val.split(filter.value_separator);}
if($.isArray(val)){return val.map(function(subval){return Utils.changeType(subval,filter.type);});}
else{return Utils.changeType(val,filter.type);}});if(operator.nb_inputs===1){value=value[0];}
if(filter.valueParser){value=filter.valueParser.call(this,rule,value);}}
return this.change('getRuleValue',value,rule);};QueryBuilder.prototype.setRuleInputValue=function(rule,value){var filter=rule.filter;var operator=rule.operator;if(!filter||!operator){return;}
rule._updating_input=true;if(filter.valueSetter){filter.valueSetter.call(this,rule,value);}
else{var $value=rule.$el.find(QueryBuilder.selectors.value_container);if(operator.nb_inputs==1){value=[value];}
for(var i=0;i<operator.nb_inputs;i++){var name=Utils.escapeElementId(rule.id+'_value_'+i);switch(filter.input){case'radio':$value.find('[name='+name+'][value="'+value[i]+'"]').prop('checked',true).trigger('change');break;case'checkbox':if(!$.isArray(value[i])){value[i]=[value[i]];}
value[i].forEach(function(value){$value.find('[name='+name+'][value="'+value+'"]').prop('checked',true).trigger('change');});break;default:if(operator.multiple&&filter.value_separator&&$.isArray(value[i])){value[i]=value[i].join(filter.value_separator);}
$value.find('[name='+name+']').val(value[i]).trigger('change');break;}}}
rule._updating_input=false;};QueryBuilder.prototype.parseRuleFlags=function(rule){var flags=$.extend({},this.settings.default_rule_flags);if(rule.readonly){$.extend(flags,{filter_readonly:true,operator_readonly:true,value_readonly:true,no_delete:true});}
if(rule.flags){$.extend(flags,rule.flags);}
return this.change('parseRuleFlags',flags,rule);};QueryBuilder.prototype.getRuleFlags=function(flags,all){if(all){return $.extend({},flags);}
else{var ret={};$.each(this.settings.default_rule_flags,function(key,value){if(flags[key]!==value){ret[key]=flags[key];}});return ret;}};QueryBuilder.prototype.parseGroupFlags=function(group){var flags=$.extend({},this.settings.default_group_flags);if(group.readonly){$.extend(flags,{condition_readonly:true,no_add_rule:true,no_add_group:true,no_delete:true});}
if(group.flags){$.extend(flags,group.flags);}
return this.change('parseGroupFlags',flags,group);};QueryBuilder.prototype.getGroupFlags=function(flags,all){if(all){return $.extend({},flags);}
else{var ret={};$.each(this.settings.default_group_flags,function(key,value){if(flags[key]!==value){ret[key]=flags[key];}});return ret;}};QueryBuilder.prototype.translate=function(category,key){if(!key){key=category;category=undefined;}
var translation;if(typeof key==='object'){translation=key[this.settings.lang_code]||key['en'];}
else{translation=(category?this.lang[category]:this.lang)[key]||key;}
return this.change('translate',translation,key,category);};QueryBuilder.prototype.getValidationMessage=function(validation,type,def){return validation.messages&&validation.messages[type]||def;};QueryBuilder.templates.group='\
<div id="{{= it.group_id }}" class="rules-group-container"> \
  <div class="rules-group-header"> \
    <div class="btn-group pull-right group-actions"> \
      <button type="button" class="btn btn-xs btn-success" data-add="rule"> \
        <i class="{{= it.icons.add_rule }}"></i> {{= it.translate("add_rule") }} \
      </button> \
      {{? it.settings.allow_groups===-1 || it.settings.allow_groups>=it.level }} \
        <button type="button" class="btn btn-xs btn-success" data-add="group"> \
          <i class="{{= it.icons.add_group }}"></i> {{= it.translate("add_group") }} \
        </button> \
      {{?}} \
      {{? it.level>1 }} \
        <button type="button" class="btn btn-xs btn-danger" data-delete="group"> \
          <i class="{{= it.icons.remove_group }}"></i> {{= it.translate("delete_group") }} \
        </button> \
      {{?}} \
    </div> \
    <div class="btn-group group-conditions"> \
      {{~ it.conditions: condition }} \
        <label class="btn btn-xs btn-primary"> \
          <input type="radio" name="{{= it.group_id }}_cond" value="{{= condition }}"> {{= it.translate("conditions", condition) }} \
        </label> \
      {{~}} \
    </div> \
    {{? it.settings.display_errors }} \
      <div class="error-container"><i class="{{= it.icons.error }}"></i></div> \
    {{?}} \
  </div> \
  <div class=rules-group-body> \
    <div class=rules-list></div> \
  </div> \
</div>';QueryBuilder.templates.rule='\
<div id="{{= it.rule_id }}" class="rule-container"> \
  <div class="rule-header"> \
    <div class="btn-group pull-right rule-actions"> \
      <button type="button" class="btn btn-xs btn-danger" data-delete="rule"> \
        <i class="{{= it.icons.remove_rule }}"></i> {{= it.translate("delete_rule") }} \
      </button> \
    </div> \
  </div> \
  {{? it.settings.display_errors }} \
    <div class="error-container"><i class="{{= it.icons.error }}"></i></div> \
  {{?}} \
  <div class="rule-filter-container"></div> \
  <div class="rule-operator-container"></div> \
  <div class="rule-value-container"></div> \
</div>';QueryBuilder.templates.filterSelect='\
{{ var optgroup = null; }} \
<select class="form-control" name="{{= it.rule.id }}_filter"> \
  {{? it.settings.display_empty_filter }} \
    <option value="-1">{{= it.settings.select_placeholder }}</option> \
  {{?}} \
  {{~ it.filters: filter }} \
    {{? optgroup !== filter.optgroup }} \
      {{? optgroup !== null }}</optgroup>{{?}} \
      {{? (optgroup = filter.optgroup) !== null }} \
        <optgroup label="{{= it.translate(it.settings.optgroups[optgroup]) }}"> \
      {{?}} \
    {{?}} \
    <option value="{{= filter.id }}" {{? filter.icon}}data-icon="{{= filter.icon}}"{{?}}>{{= it.translate(filter.label) }}</option> \
  {{~}} \
  {{? optgroup !== null }}</optgroup>{{?}} \
</select>';QueryBuilder.templates.operatorSelect='\
{{? it.operators.length === 1 }} \
<span> \
{{= it.translate("operators", it.operators[0].type) }} \
</span> \
{{?}} \
{{ var optgroup = null; }} \
<select class="form-control {{? it.operators.length === 1 }}hide{{?}}" name="{{= it.rule.id }}_operator"> \
  {{~ it.operators: operator }} \
    {{? optgroup !== operator.optgroup }} \
      {{? optgroup !== null }}</optgroup>{{?}} \
      {{? (optgroup = operator.optgroup) !== null }} \
        <optgroup label="{{= it.translate(it.settings.optgroups[optgroup]) }}"> \
      {{?}} \
    {{?}} \
    <option value="{{= operator.type }}" {{? operator.icon}}data-icon="{{= operator.icon}}"{{?}}>{{= it.translate("operators", operator.type) }}</option> \
  {{~}} \
  {{? optgroup !== null }}</optgroup>{{?}} \
</select>';QueryBuilder.templates.ruleValueSelect='\
{{ var optgroup = null; }} \
<select class="form-control" name="{{= it.name }}" {{? it.rule.filter.multiple }}multiple{{?}}> \
  {{? it.rule.filter.placeholder }} \
    <option value="{{= it.rule.filter.placeholder_value }}" disabled selected>{{= it.rule.filter.placeholder }}</option> \
  {{?}} \
  {{~ it.rule.filter.values: entry }} \
    {{? optgroup !== entry.optgroup }} \
      {{? optgroup !== null }}</optgroup>{{?}} \
      {{? (optgroup = entry.optgroup) !== null }} \
        <optgroup label="{{= it.translate(it.settings.optgroups[optgroup]) }}"> \
      {{?}} \
    {{?}} \
    <option value="{{= entry.value }}">{{= entry.label }}</option> \
  {{~}} \
  {{? optgroup !== null }}</optgroup>{{?}} \
</select>';QueryBuilder.prototype.getGroupTemplate=function(group_id,level){var h=this.templates.group({builder:this,group_id:group_id,level:level,conditions:this.settings.conditions,icons:this.icons,settings:this.settings,translate:this.translate.bind(this)});return this.change('getGroupTemplate',h,level);};QueryBuilder.prototype.getRuleTemplate=function(rule_id){var h=this.templates.rule({builder:this,rule_id:rule_id,icons:this.icons,settings:this.settings,translate:this.translate.bind(this)});return this.change('getRuleTemplate',h);};QueryBuilder.prototype.getRuleFilterSelect=function(rule,filters){var h=this.templates.filterSelect({builder:this,rule:rule,filters:filters,icons:this.icons,settings:this.settings,translate:this.translate.bind(this)});return this.change('getRuleFilterSelect',h,rule,filters);};QueryBuilder.prototype.getRuleOperatorSelect=function(rule,operators){var h=this.templates.operatorSelect({builder:this,rule:rule,operators:operators,icons:this.icons,settings:this.settings,translate:this.translate.bind(this)});return this.change('getRuleOperatorSelect',h,rule,operators);};QueryBuilder.prototype.getRuleValueSelect=function(name,rule){var h=this.templates.ruleValueSelect({builder:this,name:name,rule:rule,icons:this.icons,settings:this.settings,translate:this.translate.bind(this)});return this.change('getRuleValueSelect',h,name,rule);};QueryBuilder.prototype.getRuleInput=function(rule,value_id){var filter=rule.filter;var validation=rule.filter.validation||{};var name=rule.id+'_value_'+value_id;var c=filter.vertical?' class=block':'';var h='';if(typeof filter.input=='function'){h=filter.input.call(this,rule,name);}
else{switch(filter.input){case'radio':case'checkbox':Utils.iterateOptions(filter.values,function(key,val){h+='<label'+c+'><input type="'+filter.input+'" name="'+name+'" value="'+key+'"> '+val+'</label> ';});break;case'select':h=this.getRuleValueSelect(name,rule);break;case'textarea':h+='<textarea class="form-control" name="'+name+'"';if(filter.size)h+=' cols="'+filter.size+'"';if(filter.rows)h+=' rows="'+filter.rows+'"';if(validation.min!==undefined)h+=' minlength="'+validation.min+'"';if(validation.max!==undefined)h+=' maxlength="'+validation.max+'"';if(filter.placeholder)h+=' placeholder="'+filter.placeholder+'"';h+='></textarea>';break;case'number':h+='<input class="form-control" type="number" name="'+name+'"';if(validation.step!==undefined)h+=' step="'+validation.step+'"';if(validation.min!==undefined)h+=' min="'+validation.min+'"';if(validation.max!==undefined)h+=' max="'+validation.max+'"';if(filter.placeholder)h+=' placeholder="'+filter.placeholder+'"';if(filter.size)h+=' size="'+filter.size+'"';h+='>';break;default:h+='<input class="form-control" type="text" name="'+name+'"';if(filter.placeholder)h+=' placeholder="'+filter.placeholder+'"';if(filter.type==='string'&&validation.min!==undefined)h+=' minlength="'+validation.min+'"';if(filter.type==='string'&&validation.max!==undefined)h+=' maxlength="'+validation.max+'"';if(filter.size)h+=' size="'+filter.size+'"';h+='>';}}
return this.change('getRuleInput',h,rule,name);};var Utils={};QueryBuilder.utils=Utils;Utils.iterateOptions=function(options,tpl){if(options){if($.isArray(options)){options.forEach(function(entry){if($.isPlainObject(entry)){if('value'in entry){tpl(entry.value,entry.label||entry.value,entry.optgroup);}
else{$.each(entry,function(key,val){tpl(key,val);return false;});}}
else{tpl(entry,entry);}});}
else{$.each(options,function(key,val){tpl(key,val);});}}};Utils.fmt=function(str,args){if(!Array.isArray(args)){args=Array.prototype.slice.call(arguments,1);}
return str.replace(/{([0-9]+)}/g,function(m,i){return args[parseInt(i)];});};Utils.error=function(){var i=0;var doThrow=typeof arguments[i]==='boolean'?arguments[i++]:true;var type=arguments[i++];var message=arguments[i++];var args=Array.isArray(arguments[i])?arguments[i]:Array.prototype.slice.call(arguments,i);if(doThrow){var err=new Error(Utils.fmt(message,args));err.name=type+'Error';err.args=args;throw err;}
else{console.error(type+'Error: '+Utils.fmt(message,args));}};Utils.changeType=function(value,type){if(value===''||value===undefined){return undefined;}
switch(type){case'integer':if(typeof value==='string'&&!/^-?\d+$/.test(value)){return value;}
return parseInt(value);case'double':if(typeof value==='string'&&!/^-?\d+\.?\d*$/.test(value)){return value;}
return parseFloat(value);case'boolean':if(typeof value==='string'&&!/^(0|1|true|false){1}$/i.test(value)){return value;}
return value===true||value===1||value.toLowerCase()==='true'||value==='1';default:return value;}};Utils.escapeString=function(value){if(typeof value!='string'){return value;}
return value.replace(/[\0\n\r\b\\\'\"]/g,function(s){switch(s){case'\0':return'\\0';case'\n':return'\\n';case'\r':return'\\r';case'\b':return'\\b';default:return'\\'+s;}}).replace(/\t/g,'\\t').replace(/\x1a/g,'\\Z');};Utils.escapeRegExp=function(str){return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,'\\$&');};Utils.escapeElementId=function(str){return(str)?str.replace(/(\\)?([:.\[\],])/g,function($0,$1,$2){return $1?$0:'\\'+$2;}):str;};Utils.groupSort=function(items,key){var optgroups=[];var newItems=[];items.forEach(function(item){var idx;if(item[key]){idx=optgroups.lastIndexOf(item[key]);if(idx==-1){idx=optgroups.length;}
else{idx++;}}
else{idx=optgroups.length;}
optgroups.splice(idx,0,item[key]);newItems.splice(idx,0,item);});return newItems;};Utils.defineModelProperties=function(obj,fields){fields.forEach(function(field){Object.defineProperty(obj.prototype,field,{enumerable:true,get:function(){return this.__[field];},set:function(value){var previousValue=(this.__[field]!==null&&typeof this.__[field]=='object')?$.extend({},this.__[field]):this.__[field];this.__[field]=value;if(this.model!==null){this.model.trigger('update',this,field,value,previousValue);}}});});};function Model(){this.root=null;this.$=$(this);}
$.extend(Model.prototype,{trigger:function(type){var event=new $.Event(type);this.$.triggerHandler(event,Array.prototype.slice.call(arguments,1));return event;},on:function(){this.$.on.apply(this.$,Array.prototype.slice.call(arguments));return this;},off:function(){this.$.off.apply(this.$,Array.prototype.slice.call(arguments));return this;},once:function(){this.$.one.apply(this.$,Array.prototype.slice.call(arguments));return this;}});var Node=function(parent,$el){if(!(this instanceof Node)){return new Node(parent,$el);}
Object.defineProperty(this,'__',{value:{}});$el.data('queryBuilderModel',this);this.__.level=1;this.__.error=null;this.__.flags={};this.__.data=undefined;this.$el=$el;this.id=$el[0].id;this.model=null;this.parent=parent;};Utils.defineModelProperties(Node,['level','error','data','flags']);Object.defineProperty(Node.prototype,'parent',{enumerable:true,get:function(){return this.__.parent;},set:function(value){this.__.parent=value;this.level=value===null?1:value.level+1;this.model=value===null?null:value.model;}});Node.prototype.isRoot=function(){return(this.level===1);};Node.prototype.getPos=function(){if(this.isRoot()){return-1;}
else{return this.parent.getNodePos(this);}};Node.prototype.drop=function(){var model=this.model;if(!!this.parent){this.parent.removeNode(this);}
this.$el.removeData('queryBuilderModel');if(model!==null){model.trigger('drop',this);}};Node.prototype.moveAfter=function(target){if(!this.isRoot()){this.move(target.parent,target.getPos()+1);}};Node.prototype.moveAtBegin=function(target){if(!this.isRoot()){if(target===undefined){target=this.parent;}
this.move(target,0);}};Node.prototype.moveAtEnd=function(target){if(!this.isRoot()){if(target===undefined){target=this.parent;}
this.move(target,target.length()===0?0:target.length()-1);}};Node.prototype.move=function(target,index){if(!this.isRoot()){if(typeof target==='number'){index=target;target=this.parent;}
this.parent.removeNode(this);target.insertNode(this,index,false);if(this.model!==null){this.model.trigger('move',this,target,index);}}};var Group=function(parent,$el){if(!(this instanceof Group)){return new Group(parent,$el);}
Node.call(this,parent,$el);this.rules=[];this.__.condition=null;};Group.prototype=Object.create(Node.prototype);Group.prototype.constructor=Group;Utils.defineModelProperties(Group,['condition']);Group.prototype.empty=function(){this.each('reverse',function(rule){rule.drop();},function(group){group.drop();});};Group.prototype.drop=function(){this.empty();Node.prototype.drop.call(this);};Group.prototype.length=function(){return this.rules.length;};Group.prototype.insertNode=function(node,index,trigger){if(index===undefined){index=this.length();}
this.rules.splice(index,0,node);node.parent=this;if(trigger&&this.model!==null){this.model.trigger('add',this,node,index);}
return node;};Group.prototype.addGroup=function($el,index){return this.insertNode(new Group(this,$el),index,true);};Group.prototype.addRule=function($el,index){return this.insertNode(new Rule(this,$el),index,true);};Group.prototype.removeNode=function(node){var index=this.getNodePos(node);if(index!==-1){node.parent=null;this.rules.splice(index,1);}};Group.prototype.getNodePos=function(node){return this.rules.indexOf(node);};Group.prototype.each=function(reverse,cbRule,cbGroup,context){if(typeof reverse!=='boolean'&&typeof reverse!=='string'){context=cbGroup;cbGroup=cbRule;cbRule=reverse;reverse=false;}
context=context===undefined?null:context;var i=reverse?this.rules.length-1:0;var l=reverse?0:this.rules.length-1;var c=reverse?-1:1;var next=function(){return reverse?i>=l:i<=l;};var stop=false;for(;next();i+=c){if(this.rules[i]instanceof Group){if(!!cbGroup){stop=cbGroup.call(context,this.rules[i])===false;}}
else if(!!cbRule){stop=cbRule.call(context,this.rules[i])===false;}
if(stop){break;}}
return!stop;};Group.prototype.contains=function(node,recursive){if(this.getNodePos(node)!==-1){return true;}
else if(!recursive){return false;}
else{return!this.each(function(){return true;},function(group){return!group.contains(node,true);});}};var Rule=function(parent,$el){if(!(this instanceof Rule)){return new Rule(parent,$el);}
Node.call(this,parent,$el);this._updating_value=false;this._updating_input=false;this.__.filter=null;this.__.operator=null;this.__.value=undefined;};Rule.prototype=Object.create(Node.prototype);Rule.prototype.constructor=Rule;Utils.defineModelProperties(Rule,['filter','operator','value']);Rule.prototype.isRoot=function(){return false;};QueryBuilder.Group=Group;QueryBuilder.Rule=Rule;$.fn.queryBuilder=function(option){if(this.length===0){Utils.error('Config','No target defined');}
if(this.length>1){Utils.error('Config','Unable to initialize on multiple target');}
var data=this.data('queryBuilder');var options=(typeof option=='object'&&option)||{};if(!data&&option=='destroy'){return this;}
if(!data){var builder=new QueryBuilder(this,options);this.data('queryBuilder',builder);builder.init(options.rules);}
if(typeof option=='string'){return data[option].apply(data,Array.prototype.slice.call(arguments,1));}
return this;};$.fn.queryBuilder.constructor=QueryBuilder;$.fn.queryBuilder.defaults=QueryBuilder.defaults;$.fn.queryBuilder.extend=QueryBuilder.extend;$.fn.queryBuilder.define=QueryBuilder.define;$.fn.queryBuilder.regional=QueryBuilder.regional;QueryBuilder.define('bt-checkbox',function(options){if(options.font=='glyphicons'){this.$el.addClass('bt-checkbox-glyphicons');}
this.on('getRuleInput.filter',function(h,rule,name){var filter=rule.filter;if((filter.input==='radio'||filter.input==='checkbox')&&!filter.plugin){h.value='';if(!filter.colors){filter.colors={};}
if(filter.color){filter.colors._def_=filter.color;}
var style=filter.vertical?' style="display:block"':'';var i=0;Utils.iterateOptions(filter.values,function(key,val){var color=filter.colors[key]||filter.colors._def_||options.color;var id=name+'_'+(i++);h.value+='\
<div'+style+' class="'+filter.input+' '+filter.input+'-'+color+'"> \
  <input type="'+filter.input+'" name="'+name+'" id="'+id+'" value="'+key+'"> \
  <label for="'+id+'">'+val+'</label> \
</div>';});}});},{font:'glyphicons',color:'default'});QueryBuilder.define('bt-selectpicker',function(options){if(!$.fn.selectpicker||!$.fn.selectpicker.Constructor){Utils.error('MissingLibrary','Bootstrap Select is required to use "bt-selectpicker" plugin. Get it here: http://silviomoreto.github.io/bootstrap-select');}
var Selectors=QueryBuilder.selectors;this.on('afterCreateRuleFilters',function(e,rule){rule.$el.find(Selectors.rule_filter).removeClass('form-control').selectpicker(options);});this.on('afterCreateRuleOperators',function(e,rule){rule.$el.find(Selectors.rule_operator).removeClass('form-control').selectpicker(options);});this.on('afterUpdateRuleFilter',function(e,rule){rule.$el.find(Selectors.rule_filter).selectpicker('render');});this.on('afterUpdateRuleOperator',function(e,rule){rule.$el.find(Selectors.rule_operator).selectpicker('render');});this.on('beforeDeleteRule',function(e,rule){rule.$el.find(Selectors.rule_filter).selectpicker('destroy');rule.$el.find(Selectors.rule_operator).selectpicker('destroy');});},{container:'body',style:'btn-inverse btn-xs',width:'auto',showIcon:false});QueryBuilder.define('bt-tooltip-errors',function(options){if(!$.fn.tooltip||!$.fn.tooltip.Constructor||!$.fn.tooltip.Constructor.prototype.fixTitle){Utils.error('MissingLibrary','Bootstrap Tooltip is required to use "bt-tooltip-errors" plugin. Get it here: http://getbootstrap.com');}
var self=this;this.on('getRuleTemplate.filter getGroupTemplate.filter',function(h){var $h=$(h.value);$h.find(QueryBuilder.selectors.error_container).attr('data-toggle','tooltip');h.value=$h.prop('outerHTML');});this.model.on('update',function(e,node,field){if(field=='error'&&self.settings.display_errors){node.$el.find(QueryBuilder.selectors.error_container).eq(0).tooltip(options).tooltip('hide').tooltip('fixTitle');}});},{placement:'right'});QueryBuilder.extend({setFilters:function(deleteOrphans,filters){var self=this;if(filters===undefined){filters=deleteOrphans;deleteOrphans=false;}
filters=this.checkFilters(filters);filters=this.change('setFilters',filters);var filtersIds=filters.map(function(filter){return filter.id;});if(!deleteOrphans){(function checkOrphans(node){node.each(function(rule){if(rule.filter&&filtersIds.indexOf(rule.filter.id)===-1){Utils.error('ChangeFilter','A rule is using filter "{0}"',rule.filter.id);}},checkOrphans);}(this.model.root));}
this.filters=filters;(function updateBuilder(node){node.each(true,function(rule){if(rule.filter&&filtersIds.indexOf(rule.filter.id)===-1){rule.drop();self.trigger('rulesChanged');}
else{self.createRuleFilters(rule);rule.$el.find(QueryBuilder.selectors.rule_filter).val(rule.filter?rule.filter.id:'-1');self.trigger('afterUpdateRuleFilter',rule);}},updateBuilder);}(this.model.root));if(this.settings.plugins){if(this.settings.plugins['unique-filter']){this.updateDisabledFilters();}
if(this.settings.plugins['bt-selectpicker']){this.$el.find(QueryBuilder.selectors.rule_filter).selectpicker('render');}}
if(this.settings.default_filter){try{this.getFilterById(this.settings.default_filter);}
catch(e){this.settings.default_filter=null;}}
this.trigger('afterSetFilters',filters);},addFilter:function(newFilters,position){if(position===undefined||position=='#end'){position=this.filters.length;}
else if(position=='#start'){position=0;}
if(!$.isArray(newFilters)){newFilters=[newFilters];}
var filters=$.extend(true,[],this.filters);if(parseInt(position)==position){Array.prototype.splice.apply(filters,[position,0].concat(newFilters));}
else{if(this.filters.some(function(filter,index){if(filter.id==position){position=index+1;return true;}})){Array.prototype.splice.apply(filters,[position,0].concat(newFilters));}
else{Array.prototype.push.apply(filters,newFilters);}}
this.setFilters(filters);},removeFilter:function(filterIds,deleteOrphans){var filters=$.extend(true,[],this.filters);if(typeof filterIds==='string'){filterIds=[filterIds];}
filters=filters.filter(function(filter){return filterIds.indexOf(filter.id)===-1;});this.setFilters(deleteOrphans,filters);}});QueryBuilder.define('chosen-selectpicker',function(options){if(!$.fn.chosen){Utils.error('MissingLibrary','chosen is required to use "chosen-selectpicker" plugin. Get it here: https://github.com/harvesthq/chosen');}
if(this.settings.plugins['bt-selectpicker']){Utils.error('Conflict','bt-selectpicker is already selected as the dropdown plugin. Please remove chosen-selectpicker from the plugin list');}
var Selectors=QueryBuilder.selectors;this.on('afterCreateRuleFilters',function(e,rule){rule.$el.find(Selectors.rule_filter).removeClass('form-control').chosen(options);});this.on('afterCreateRuleOperators',function(e,rule){rule.$el.find(Selectors.rule_operator).removeClass('form-control').chosen(options);});this.on('afterUpdateRuleFilter',function(e,rule){rule.$el.find(Selectors.rule_filter).trigger('chosen:updated');});this.on('afterUpdateRuleOperator',function(e,rule){rule.$el.find(Selectors.rule_operator).trigger('chosen:updated');});this.on('beforeDeleteRule',function(e,rule){rule.$el.find(Selectors.rule_filter).chosen('destroy');rule.$el.find(Selectors.rule_operator).chosen('destroy');});});QueryBuilder.define('filter-description',function(options){if(options.mode==='inline'){this.on('afterUpdateRuleFilter afterUpdateRuleOperator',function(e,rule){var $p=rule.$el.find('p.filter-description');var description=e.builder.getFilterDescription(rule.filter,rule);if(!description){$p.hide();}
else{if($p.length===0){$p=$('<p class="filter-description"></p>');$p.appendTo(rule.$el);}
else{$p.css('display','');}
$p.html('<i class="'+options.icon+'"></i> '+description);}});}
else if(options.mode==='popover'){if(!$.fn.popover||!$.fn.popover.Constructor||!$.fn.popover.Constructor.prototype.fixTitle){Utils.error('MissingLibrary','Bootstrap Popover is required to use "filter-description" plugin. Get it here: http://getbootstrap.com');}
this.on('afterUpdateRuleFilter afterUpdateRuleOperator',function(e,rule){var $b=rule.$el.find('button.filter-description');var description=e.builder.getFilterDescription(rule.filter,rule);if(!description){$b.hide();if($b.data('bs.popover')){$b.popover('hide');}}
else{if($b.length===0){$b=$('<button type="button" class="btn btn-xs btn-info filter-description" data-toggle="popover"><i class="'+options.icon+'"></i></button>');$b.prependTo(rule.$el.find(QueryBuilder.selectors.rule_actions));$b.popover({placement:'left',container:'body',html:true});$b.on('mouseout',function(){$b.popover('hide');});}
else{$b.css('display','');}
$b.data('bs.popover').options.content=description;if($b.attr('aria-describedby')){$b.popover('show');}}});}
else if(options.mode==='bootbox'){if(!('bootbox'in window)){Utils.error('MissingLibrary','Bootbox is required to use "filter-description" plugin. Get it here: http://bootboxjs.com');}
this.on('afterUpdateRuleFilter afterUpdateRuleOperator',function(e,rule){var $b=rule.$el.find('button.filter-description');var description=e.builder.getFilterDescription(rule.filter,rule);if(!description){$b.hide();}
else{if($b.length===0){$b=$('<button type="button" class="btn btn-xs btn-info filter-description" data-toggle="bootbox"><i class="'+options.icon+'"></i></button>');$b.prependTo(rule.$el.find(QueryBuilder.selectors.rule_actions));$b.on('click',function(){bootbox.alert($b.data('description'));});}
else{$b.css('display','');}
$b.data('description',description);}});}},{icon:'glyphicon glyphicon-info-sign',mode:'popover'});QueryBuilder.extend({getFilterDescription:function(filter,rule){if(!filter){return undefined;}
else if(typeof filter.description=='function'){return filter.description.call(this,rule);}
else{return filter.description;}}});QueryBuilder.define('invert',function(options){var self=this;var Selectors=QueryBuilder.selectors;this.on('afterInit',function(){self.$el.on('click.queryBuilder','[data-invert=group]',function(){var $group=$(this).closest(Selectors.group_container);self.invert(self.getModel($group),options);});if(options.display_rules_button&&options.invert_rules){self.$el.on('click.queryBuilder','[data-invert=rule]',function(){var $rule=$(this).closest(Selectors.rule_container);self.invert(self.getModel($rule),options);});}});if(!options.disable_template){this.on('getGroupTemplate.filter',function(h){var $h=$(h.value);$h.find(Selectors.condition_container).after('<button type="button" class="btn btn-xs btn-default" data-invert="group">'+'<i class="'+options.icon+'"></i> '+self.translate('invert')+'</button>');h.value=$h.prop('outerHTML');});if(options.display_rules_button&&options.invert_rules){this.on('getRuleTemplate.filter',function(h){var $h=$(h.value);$h.find(Selectors.rule_actions).prepend('<button type="button" class="btn btn-xs btn-default" data-invert="rule">'+'<i class="'+options.icon+'"></i> '+self.translate('invert')+'</button>');h.value=$h.prop('outerHTML');});}}},{icon:'glyphicon glyphicon-random',recursive:true,invert_rules:true,display_rules_button:false,silent_fail:false,disable_template:false});QueryBuilder.defaults({operatorOpposites:{'equal':'not_equal','not_equal':'equal','in':'not_in','not_in':'in','less':'greater_or_equal','less_or_equal':'greater','greater':'less_or_equal','greater_or_equal':'less','between':'not_between','not_between':'between','begins_with':'not_begins_with','not_begins_with':'begins_with','contains':'not_contains','not_contains':'contains','ends_with':'not_ends_with','not_ends_with':'ends_with','is_empty':'is_not_empty','is_not_empty':'is_empty','is_null':'is_not_null','is_not_null':'is_null'},conditionOpposites:{'AND':'OR','OR':'AND'}});QueryBuilder.extend({invert:function(node,options){if(!(node instanceof Node)){if(!this.model.root)return;options=node;node=this.model.root;}
if(typeof options!='object')options={};if(options.recursive===undefined)options.recursive=true;if(options.invert_rules===undefined)options.invert_rules=true;if(options.silent_fail===undefined)options.silent_fail=false;if(options.trigger===undefined)options.trigger=true;if(node instanceof Group){if(this.settings.conditionOpposites[node.condition]){node.condition=this.settings.conditionOpposites[node.condition];}
else if(!options.silent_fail){Utils.error('InvertCondition','Unknown inverse of condition "{0}"',node.condition);}
if(options.recursive){var tempOpts=$.extend({},options,{trigger:false});node.each(function(rule){if(options.invert_rules){this.invert(rule,tempOpts);}},function(group){this.invert(group,tempOpts);},this);}}
else if(node instanceof Rule){if(node.operator&&!node.filter.no_invert){if(this.settings.operatorOpposites[node.operator.type]){var invert=this.settings.operatorOpposites[node.operator.type];if(!node.filter.operators||node.filter.operators.indexOf(invert)!=-1){node.operator=this.getOperatorByType(invert);}}
else if(!options.silent_fail){Utils.error('InvertOperator','Unknown inverse of operator "{0}"',node.operator.type);}}}
if(options.trigger){this.trigger('afterInvert',node,options);this.trigger('rulesChanged');}}});QueryBuilder.defaults({mongoOperators:{equal:function(v){return v[0];},not_equal:function(v){return{'$ne':v[0]};},in:function(v){return{'$in':v};},not_in:function(v){return{'$nin':v};},less:function(v){return{'$lt':v[0]};},less_or_equal:function(v){return{'$lte':v[0]};},greater:function(v){return{'$gt':v[0]};},greater_or_equal:function(v){return{'$gte':v[0]};},between:function(v){return{'$gte':v[0],'$lte':v[1]};},not_between:function(v){return{'$lt':v[0],'$gt':v[1]};},begins_with:function(v){return{'$regex':'^'+Utils.escapeRegExp(v[0])};},not_begins_with:function(v){return{'$regex':'^(?!'+Utils.escapeRegExp(v[0])+')'};},contains:function(v){return{'$regex':Utils.escapeRegExp(v[0])};},not_contains:function(v){return{'$regex':'^((?!'+Utils.escapeRegExp(v[0])+').)*$','$options':'s'};},ends_with:function(v){return{'$regex':Utils.escapeRegExp(v[0])+'$'};},not_ends_with:function(v){return{'$regex':'(?<!'+Utils.escapeRegExp(v[0])+')$'};},is_empty:function(v){return'';},is_not_empty:function(v){return{'$ne':''};},is_null:function(v){return null;},is_not_null:function(v){return{'$ne':null};}},mongoRuleOperators:{$ne:function(v){v=v.$ne;return{'val':v,'op':v===null?'is_not_null':(v===''?'is_not_empty':'not_equal')};},eq:function(v){return{'val':v,'op':v===null?'is_null':(v===''?'is_empty':'equal')};},$regex:function(v){v=v.$regex;if(v.slice(0,4)=='^(?!'&&v.slice(-1)==')'){return{'val':v.slice(4,-1),'op':'not_begins_with'};}
else if(v.slice(0,5)=='^((?!'&&v.slice(-5)==').)*$'){return{'val':v.slice(5,-5),'op':'not_contains'};}
else if(v.slice(0,4)=='(?<!'&&v.slice(-2)==')$'){return{'val':v.slice(4,-2),'op':'not_ends_with'};}
else if(v.slice(-1)=='$'){return{'val':v.slice(0,-1),'op':'ends_with'};}
else if(v.slice(0,1)=='^'){return{'val':v.slice(1),'op':'begins_with'};}
else{return{'val':v,'op':'contains'};}},between:function(v){return{'val':[v.$gte,v.$lte],'op':'between'};},not_between:function(v){return{'val':[v.$lt,v.$gt],'op':'not_between'};},$in:function(v){return{'val':v.$in,'op':'in'};},$nin:function(v){return{'val':v.$nin,'op':'not_in'};},$lt:function(v){return{'val':v.$lt,'op':'less'};},$lte:function(v){return{'val':v.$lte,'op':'less_or_equal'};},$gt:function(v){return{'val':v.$gt,'op':'greater'};},$gte:function(v){return{'val':v.$gte,'op':'greater_or_equal'};}}});QueryBuilder.extend({getMongo:function(data){data=(data===undefined)?this.getRules():data;if(!data){return null;}
var self=this;return(function parse(group){if(!group.condition){group.condition=self.settings.default_condition;}
if(['AND','OR'].indexOf(group.condition.toUpperCase())===-1){Utils.error('UndefinedMongoCondition','Unable to build MongoDB query with condition "{0}"',group.condition);}
if(!group.rules){return{};}
var parts=[];group.rules.forEach(function(rule){if(rule.rules&&rule.rules.length>0){parts.push(parse(rule));}
else{var mdb=self.settings.mongoOperators[rule.operator];var ope=self.getOperatorByType(rule.operator);if(mdb===undefined){Utils.error('UndefinedMongoOperator','Unknown MongoDB operation for operator "{0}"',rule.operator);}
if(ope.nb_inputs!==0){if(!(rule.value instanceof Array)){rule.value=[rule.value];}}
var field=self.change('getMongoDBField',rule.field,rule);var ruleExpression={};ruleExpression[field]=mdb.call(self,rule.value);parts.push(self.change('ruleToMongo',ruleExpression,rule,rule.value,mdb));}});var groupExpression={};groupExpression['$'+group.condition.toLowerCase()]=parts;return self.change('groupToMongo',groupExpression,group);}(data));},getRulesFromMongo:function(query){if(query===undefined||query===null){return null;}
var self=this;query=self.change('parseMongoNode',query);if('rules'in query&&'condition'in query){return query;}
if('id'in query&&'operator'in query&&'value'in query){return{condition:this.settings.default_condition,rules:[query]};}
var key=andOr(query);if(!key){Utils.error('MongoParse','Invalid MongoDB query format');}
return(function parse(data,topKey){var rules=data[topKey];var parts=[];rules.forEach(function(data){data=self.change('parseMongoNode',data);if('rules'in data&&'condition'in data){parts.push(data);return;}
if('id'in data&&'operator'in data&&'value'in data){parts.push(data);return;}
var key=andOr(data);if(key){parts.push(parse(data,key));}
else{var field=Object.keys(data)[0];var value=data[field];var operator=determineMongoOperator(value,field);if(operator===undefined){Utils.error('MongoParse','Invalid MongoDB query format');}
var mdbrl=self.settings.mongoRuleOperators[operator];if(mdbrl===undefined){Utils.error('UndefinedMongoOperator','JSON Rule operation unknown for operator "{0}"',operator);}
var opVal=mdbrl.call(self,value);var id=self.getMongoDBFieldID(field,value);var rule=self.change('mongoToRule',{id:id,field:field,operator:opVal.op,value:opVal.val},data);parts.push(rule);}});return self.change('mongoToGroup',{condition:topKey.replace('$','').toUpperCase(),rules:parts},data);}(query,key));},setRulesFromMongo:function(query){this.setRules(this.getRulesFromMongo(query));},getMongoDBFieldID:function(field,value){var matchingFilters=this.filters.filter(function(filter){return filter.field===field;});var id;if(matchingFilters.length===1){id=matchingFilters[0].id;}
else{id=this.change('getMongoDBFieldID',field,value);}
return id;}});function determineMongoOperator(value){if(value!==null&&typeof value=='object'){var subkeys=Object.keys(value);if(subkeys.length===1){return subkeys[0];}
else{if(value.$gte!==undefined&&value.$lte!==undefined){return'between';}
if(value.$lt!==undefined&&value.$gt!==undefined){return'not_between';}
else if(value.$regex!==undefined){return'$regex';}
else{return;}}}
else{return'eq';}}
function andOr(data){var keys=Object.keys(data);for(var i=0,l=keys.length;i<l;i++){if(keys[i].toLowerCase()=='$or'||keys[i].toLowerCase()=='$and'){return keys[i];}}
return undefined;}
QueryBuilder.define('not-group',function(options){var self=this;this.on('afterInit',function(){self.$el.on('click.queryBuilder','[data-not=group]',function(){var $group=$(this).closest(QueryBuilder.selectors.group_container);var group=self.getModel($group);group.not=!group.not;});self.model.on('update',function(e,node,field){if(node instanceof Group&&field==='not'){self.updateGroupNot(node);}});});this.on('afterAddGroup',function(e,group){group.__.not=false;});if(!options.disable_template){this.on('getGroupTemplate.filter',function(h){var $h=$(h.value);$h.find(QueryBuilder.selectors.condition_container).prepend('<button type="button" class="btn btn-xs btn-default" data-not="group">'+'<i class="'+options.icon_unchecked+'"></i> '+self.translate('NOT')+'</button>');h.value=$h.prop('outerHTML');});}
this.on('groupToJson.filter',function(e,group){e.value.not=group.not;});this.on('jsonToGroup.filter',function(e,json){e.value.not=!!json.not;});this.on('groupToSQL.filter',function(e,group){if(group.not){e.value='NOT ( '+e.value+' )';}});this.on('parseSQLNode.filter',function(e){if(e.value.name&&e.value.name.toUpperCase()=='NOT'){e.value=e.value.arguments.value[0];if(['AND','OR'].indexOf(e.value.operation.toUpperCase())===-1){e.value={left:e.value,operation:self.settings.default_condition,right:null};}
e.value.not=true;}});this.on('sqlGroupsDistinct.filter',function(e,group,data){if(data.not){e.value=true;}});this.on('sqlToGroup.filter',function(e,data){e.value.not=!!data.not;});this.on('groupToMongo.filter',function(e,group){var key='$'+group.condition.toLowerCase();if(group.not&&e.value[key]){e.value={'$nor':[e.value]};}});this.on('parseMongoNode.filter',function(e){var keys=Object.keys(e.value);if(keys[0]=='$nor'){e.value=e.value[keys[0]][0];e.value.not=true;}});this.on('mongoToGroup.filter',function(e,data){e.value.not=!!data.not;});},{icon_unchecked:'glyphicon glyphicon-unchecked',icon_checked:'glyphicon glyphicon-check',disable_template:false});Utils.defineModelProperties(Group,['not']);QueryBuilder.selectors.group_not=QueryBuilder.selectors.group_header+' [data-not=group]';QueryBuilder.extend({updateGroupNot:function(group){var options=this.plugins['not-group'];group.$el.find('>'+QueryBuilder.selectors.group_not).toggleClass('active',group.not).find('i').attr('class',group.not?options.icon_checked:options.icon_unchecked);this.trigger('afterUpdateGroupNot',group);this.trigger('rulesChanged');}});QueryBuilder.define('sortable',function(options){if(!('interact'in window)){Utils.error('MissingLibrary','interact.js is required to use "sortable" plugin. Get it here: http://interactjs.io');}
if(options.default_no_sortable!==undefined){Utils.error(false,'Config','Sortable plugin : "default_no_sortable" options is deprecated, use standard "default_rule_flags" and "default_group_flags" instead');this.settings.default_rule_flags.no_sortable=this.settings.default_group_flags.no_sortable=options.default_no_sortable;}
interact.dynamicDrop(true);interact.pointerMoveTolerance(10);var placeholder;var ghost;var src;var moved;this.on('afterAddRule afterAddGroup',function(e,node){if(node==placeholder){return;}
var self=e.builder;if(options.inherit_no_sortable&&node.parent&&node.parent.flags.no_sortable){node.flags.no_sortable=true;}
if(options.inherit_no_drop&&node.parent&&node.parent.flags.no_drop){node.flags.no_drop=true;}
if(!node.flags.no_sortable){interact(node.$el[0]).draggable({allowFrom:QueryBuilder.selectors.drag_handle,onstart:function(event){moved=false;src=self.getModel(event.target);ghost=src.$el.clone().appendTo(src.$el.parent()).width(src.$el.outerWidth()).addClass('dragging');var ph=$('<div class="rule-placeholder">&nbsp;</div>').height(src.$el.outerHeight());placeholder=src.parent.addRule(ph,src.getPos());src.$el.hide();},onmove:function(event){ghost[0].style.top=event.clientY-15+'px';ghost[0].style.left=event.clientX-15+'px';},onend:function(event){if(event.dropzone){moveSortableToTarget(src,$(event.relatedTarget),self);moved=true;}
ghost.remove();ghost=undefined;placeholder.drop();placeholder=undefined;src.$el.css('display','');self.trigger('afterMove',src);self.trigger('rulesChanged');}});}
if(!node.flags.no_drop){interact(node.$el[0]).dropzone({accept:QueryBuilder.selectors.rule_and_group_containers,ondragenter:function(event){moveSortableToTarget(placeholder,$(event.target),self);},ondrop:function(event){if(!moved){moveSortableToTarget(src,$(event.target),self);}}});if(node instanceof Group){interact(node.$el.find(QueryBuilder.selectors.group_header)[0]).dropzone({accept:QueryBuilder.selectors.rule_and_group_containers,ondragenter:function(event){moveSortableToTarget(placeholder,$(event.target),self);},ondrop:function(event){if(!moved){moveSortableToTarget(src,$(event.target),self);}}});}}});this.on('beforeDeleteRule beforeDeleteGroup',function(e,node){if(!e.isDefaultPrevented()){interact(node.$el[0]).unset();if(node instanceof Group){interact(node.$el.find(QueryBuilder.selectors.group_header)[0]).unset();}}});this.on('afterApplyRuleFlags afterApplyGroupFlags',function(e,node){if(node.flags.no_sortable){node.$el.find('.drag-handle').remove();}});if(!options.disable_template){this.on('getGroupTemplate.filter',function(h,level){if(level>1){var $h=$(h.value);$h.find(QueryBuilder.selectors.condition_container).after('<div class="drag-handle"><i class="'+options.icon+'"></i></div>');h.value=$h.prop('outerHTML');}});this.on('getRuleTemplate.filter',function(h){var $h=$(h.value);$h.find(QueryBuilder.selectors.rule_header).after('<div class="drag-handle"><i class="'+options.icon+'"></i></div>');h.value=$h.prop('outerHTML');});}},{inherit_no_sortable:true,inherit_no_drop:true,icon:'glyphicon glyphicon-sort',disable_template:false});QueryBuilder.selectors.rule_and_group_containers=QueryBuilder.selectors.rule_container+', '+QueryBuilder.selectors.group_container;QueryBuilder.selectors.drag_handle='.drag-handle';QueryBuilder.defaults({default_rule_flags:{no_sortable:false,no_drop:false},default_group_flags:{no_sortable:false,no_drop:false}});function moveSortableToTarget(node,target,builder){var parent,method;var Selectors=QueryBuilder.selectors;parent=target.closest(Selectors.rule_container);if(parent.length){method='moveAfter';}
if(!method){parent=target.closest(Selectors.group_header);if(parent.length){parent=target.closest(Selectors.group_container);method='moveAtBegin';}}
if(!method){parent=target.closest(Selectors.group_container);if(parent.length){method='moveAtEnd';}}
if(method){node[method](builder.getModel(parent));if(builder&&node instanceof Rule){builder.setRuleInputValue(node,node.value);}}}
QueryBuilder.define('sql-support',function(options){},{boolean_as_integer:true});QueryBuilder.defaults({sqlOperators:{equal:{op:'= ?'},not_equal:{op:'!= ?'},in:{op:'IN(?)',sep:', '},not_in:{op:'NOT IN(?)',sep:', '},less:{op:'< ?'},less_or_equal:{op:'<= ?'},greater:{op:'> ?'},greater_or_equal:{op:'>= ?'},between:{op:'BETWEEN ?',sep:' AND '},not_between:{op:'NOT BETWEEN ?',sep:' AND '},begins_with:{op:'LIKE(?)',mod:'{0}%'},not_begins_with:{op:'NOT LIKE(?)',mod:'{0}%'},contains:{op:'LIKE(?)',mod:'%{0}%'},not_contains:{op:'NOT LIKE(?)',mod:'%{0}%'},ends_with:{op:'LIKE(?)',mod:'%{0}'},not_ends_with:{op:'NOT LIKE(?)',mod:'%{0}'},is_empty:{op:'= \'\''},is_not_empty:{op:'!= \'\''},is_null:{op:'IS NULL'},is_not_null:{op:'IS NOT NULL'}},sqlRuleOperator:{'=':function(v){return{val:v,op:v===''?'is_empty':'equal'};},'!=':function(v){return{val:v,op:v===''?'is_not_empty':'not_equal'};},'LIKE':function(v){if(v.slice(0,1)=='%'&&v.slice(-1)=='%'){return{val:v.slice(1,-1),op:'contains'};}
else if(v.slice(0,1)=='%'){return{val:v.slice(1),op:'ends_with'};}
else if(v.slice(-1)=='%'){return{val:v.slice(0,-1),op:'begins_with'};}
else{Utils.error('SQLParse','Invalid value for LIKE operator "{0}"',v);}},'NOT LIKE':function(v){if(v.slice(0,1)=='%'&&v.slice(-1)=='%'){return{val:v.slice(1,-1),op:'not_contains'};}
else if(v.slice(0,1)=='%'){return{val:v.slice(1),op:'not_ends_with'};}
else if(v.slice(-1)=='%'){return{val:v.slice(0,-1),op:'not_begins_with'};}
else{Utils.error('SQLParse','Invalid value for NOT LIKE operator "{0}"',v);}},'IN':function(v){return{val:v,op:'in'};},'NOT IN':function(v){return{val:v,op:'not_in'};},'<':function(v){return{val:v,op:'less'};},'<=':function(v){return{val:v,op:'less_or_equal'};},'>':function(v){return{val:v,op:'greater'};},'>=':function(v){return{val:v,op:'greater_or_equal'};},'BETWEEN':function(v){return{val:v,op:'between'};},'NOT BETWEEN':function(v){return{val:v,op:'not_between'};},'IS':function(v){if(v!==null){Utils.error('SQLParse','Invalid value for IS operator');}
return{val:null,op:'is_null'};},'IS NOT':function(v){if(v!==null){Utils.error('SQLParse','Invalid value for IS operator');}
return{val:null,op:'is_not_null'};}},sqlStatements:{'question_mark':function(){var params=[];return{add:function(rule,value){params.push(value);return'?';},run:function(){return params;}};},'numbered':function(char){if(!char||char.length>1)char='$';var index=0;var params=[];return{add:function(rule,value){params.push(value);index++;return char+index;},run:function(){return params;}};},'named':function(char){if(!char||char.length>1)char=':';var indexes={};var params={};return{add:function(rule,value){if(!indexes[rule.field])indexes[rule.field]=1;var key=rule.field+'_'+(indexes[rule.field]++);params[key]=value;return char+key;},run:function(){return params;}};}},sqlRuleStatement:{'question_mark':function(values){var index=0;return{parse:function(v){return v=='?'?values[index++]:v;},esc:function(sql){return sql.replace(/\?/g,'\'?\'');}};},'numbered':function(values,char){if(!char||char.length>1)char='$';var regex1=new RegExp('^\\'+char+'[0-9]+$');var regex2=new RegExp('\\'+char+'([0-9]+)','g');return{parse:function(v){return regex1.test(v)?values[v.slice(1)-1]:v;},esc:function(sql){return sql.replace(regex2,'\''+(char=='$'?'$$':char)+'$1\'');}};},'named':function(values,char){if(!char||char.length>1)char=':';var regex1=new RegExp('^\\'+char);var regex2=new RegExp('\\'+char+'('+Object.keys(values).join('|')+')','g');return{parse:function(v){return regex1.test(v)?values[v.slice(1)]:v;},esc:function(sql){return sql.replace(regex2,'\''+(char=='$'?'$$':char)+'$1\'');}};}}});QueryBuilder.extend({getSQL:function(stmt,nl,data){data=(data===undefined)?this.getRules():data;if(!data){return null;}
nl=!!nl?'\n':' ';var boolean_as_integer=this.getPluginOptions('sql-support','boolean_as_integer');if(stmt===true){stmt='question_mark';}
if(typeof stmt=='string'){var config=getStmtConfig(stmt);stmt=this.settings.sqlStatements[config[1]](config[2]);}
var self=this;var sql=(function parse(group){if(!group.condition){group.condition=self.settings.default_condition;}
if(['AND','OR'].indexOf(group.condition.toUpperCase())===-1){Utils.error('UndefinedSQLCondition','Unable to build SQL query with condition "{0}"',group.condition);}
if(!group.rules){return'';}
var parts=[];group.rules.forEach(function(rule){if(rule.rules&&rule.rules.length>0){parts.push('('+nl+parse(rule)+nl+')'+nl);}
else{var sql=self.settings.sqlOperators[rule.operator];var ope=self.getOperatorByType(rule.operator);var value='';if(sql===undefined){Utils.error('UndefinedSQLOperator','Unknown SQL operation for operator "{0}"',rule.operator);}
if(ope.nb_inputs!==0){if(!(rule.value instanceof Array)){rule.value=[rule.value];}
rule.value.forEach(function(v,i){if(i>0){value+=sql.sep;}
if(rule.type=='boolean'&&boolean_as_integer){v=v?1:0;}
else if(!stmt&&rule.type!=='integer'&&rule.type!=='double'&&rule.type!=='boolean'){v=Utils.escapeString(v);}
if(sql.mod){v=Utils.fmt(sql.mod,v);}
if(stmt){value+=stmt.add(rule,v);}
else{if(typeof v=='string'){v='\''+v+'\'';}
value+=v;}});}
var sqlFn=function(v){return sql.op.replace(/\?/,v);};var field=self.change('getSQLField',rule.field,rule);var ruleExpression=field+' '+sqlFn(value);parts.push(self.change('ruleToSQL',ruleExpression,rule,value,sqlFn));}});var groupExpression=parts.join(' '+group.condition+nl);return self.change('groupToSQL',groupExpression,group);}(data));if(stmt){return{sql:sql,params:stmt.run()};}
else{return{sql:sql};}},getRulesFromSQL:function(query,stmt){if(!('SQLParser'in window)){Utils.error('MissingLibrary','SQLParser is required to parse SQL queries. Get it here https://github.com/mistic100/sql-parser');}
var self=this;if(typeof query=='string'){query={sql:query};}
if(stmt===true)stmt='question_mark';if(typeof stmt=='string'){var config=getStmtConfig(stmt);stmt=this.settings.sqlRuleStatement[config[1]](query.params,config[2]);}
if(stmt){query.sql=stmt.esc(query.sql);}
if(query.sql.toUpperCase().indexOf('SELECT')!==0){query.sql='SELECT * FROM table WHERE '+query.sql;}
var parsed=SQLParser.parse(query.sql);if(!parsed.where){Utils.error('SQLParse','No WHERE clause found');}
var data=self.change('parseSQLNode',parsed.where.conditions);if('rules'in data&&'condition'in data){return data;}
if('id'in data&&'operator'in data&&'value'in data){return{condition:this.settings.default_condition,rules:[data]};}
var out=self.change('sqlToGroup',{condition:this.settings.default_condition,rules:[]},data);var curr=out;(function flatten(data,i){if(data===null){return;}
data=self.change('parseSQLNode',data);if('rules'in data&&'condition'in data){curr.rules.push(data);return;}
if('id'in data&&'operator'in data&&'value'in data){curr.rules.push(data);return;}
if(!('left'in data)||!('right'in data)||!('operation'in data)){Utils.error('SQLParse','Unable to parse WHERE clause');}
if(['AND','OR'].indexOf(data.operation.toUpperCase())!==-1){var createGroup=self.change('sqlGroupsDistinct',i>0&&curr.condition!=data.operation.toUpperCase(),curr,data);if(createGroup){var group=self.change('sqlToGroup',{condition:self.settings.default_condition,rules:[]},data);curr.rules.push(group);curr=group;}
curr.condition=data.operation.toUpperCase();i++;var next=curr;flatten(data.left,i);curr=next;flatten(data.right,i);}
else{if($.isPlainObject(data.right.value)){Utils.error('SQLParse','Value format not supported for {0}.',data.left.value);}
var value;if($.isArray(data.right.value)){value=data.right.value.map(function(v){return v.value;});}
else{value=data.right.value;}
if(stmt){if($.isArray(value)){value=value.map(stmt.parse);}
else{value=stmt.parse(value);}}
var operator=data.operation.toUpperCase();if(operator=='<>'){operator='!=';}
var sqlrl=self.settings.sqlRuleOperator[operator];if(sqlrl===undefined){Utils.error('UndefinedSQLOperator','Invalid SQL operation "{0}".',data.operation);}
var opVal=sqlrl.call(this,value,data.operation);var field;if('values'in data.left){field=data.left.values.join('.');}
else if('value'in data.left){field=data.left.value;}
else{Utils.error('SQLParse','Cannot find field name in {0}',JSON.stringify(data.left));}
var id=self.getSQLFieldID(field,value);var rule=self.change('sqlToRule',{id:id,field:field,operator:opVal.op,value:opVal.val},data);curr.rules.push(rule);}}(data,0));return out;},setRulesFromSQL:function(query,stmt){this.setRules(this.getRulesFromSQL(query,stmt));},getSQLFieldID:function(field,value){var matchingFilters=this.filters.filter(function(filter){return filter.field.toLowerCase()===field.toLowerCase();});var id;if(matchingFilters.length===1){id=matchingFilters[0].id;}
else{id=this.change('getSQLFieldID',field,value);}
return id;}});function getStmtConfig(stmt){var config=stmt.match(/(question_mark|numbered|named)(?:\((.)\))?/);if(!config)config=[null,'question_mark',undefined];return config;}
QueryBuilder.define('unique-filter',function(){this.status.used_filters={};this.on('afterUpdateRuleFilter',this.updateDisabledFilters);this.on('afterDeleteRule',this.updateDisabledFilters);this.on('afterCreateRuleFilters',this.applyDisabledFilters);this.on('afterReset',this.clearDisabledFilters);this.on('afterClear',this.clearDisabledFilters);this.on('getDefaultFilter.filter',function(e,model){var self=e.builder;self.updateDisabledFilters();if(e.value.id in self.status.used_filters){var found=self.filters.some(function(filter){if(!(filter.id in self.status.used_filters)||self.status.used_filters[filter.id].length>0&&self.status.used_filters[filter.id].indexOf(model.parent)===-1){e.value=filter;return true;}});if(!found){Utils.error(false,'UniqueFilter','No more non-unique filters available');e.value=undefined;}}});});QueryBuilder.extend({updateDisabledFilters:function(e){var self=e?e.builder:this;self.status.used_filters={};if(!self.model){return;}
(function walk(group){group.each(function(rule){if(rule.filter&&rule.filter.unique){if(!self.status.used_filters[rule.filter.id]){self.status.used_filters[rule.filter.id]=[];}
if(rule.filter.unique=='group'){self.status.used_filters[rule.filter.id].push(rule.parent);}}},function(group){walk(group);});}(self.model.root));self.applyDisabledFilters(e);},clearDisabledFilters:function(e){var self=e?e.builder:this;self.status.used_filters={};self.applyDisabledFilters(e);},applyDisabledFilters:function(e){var self=e?e.builder:this;self.$el.find(QueryBuilder.selectors.filter_container+' option').prop('disabled',false);$.each(self.status.used_filters,function(filterId,groups){if(groups.length===0){self.$el.find(QueryBuilder.selectors.filter_container+' option[value="'+filterId+'"]:not(:selected)').prop('disabled',true);}
else{groups.forEach(function(group){group.each(function(rule){rule.$el.find(QueryBuilder.selectors.filter_container+' option[value="'+filterId+'"]:not(:selected)').prop('disabled',true);});});}});if(self.settings.plugins&&self.settings.plugins['bt-selectpicker']){self.$el.find(QueryBuilder.selectors.rule_filter).selectpicker('render');}}});
/*!
 * jQuery QueryBuilder 2.5.1
 * Locale: English (en)
 * Author: Damien "Mistic" Sorel, http://www.strangeplanet.fr
 * Licensed under MIT (http://opensource.org/licenses/MIT)
 */
QueryBuilder.regional['en']={"__locale":"English (en)","__author":"Damien \"Mistic\" Sorel, http://www.strangeplanet.fr","add_rule":"Add rule","add_group":"Add group","delete_rule":"Delete","delete_group":"Delete","conditions":{"AND":"AND","OR":"OR"},"operators":{"equal":"equal","not_equal":"not equal","in":"in","not_in":"not in","less":"less","less_or_equal":"less or equal","greater":"greater","greater_or_equal":"greater or equal","between":"between","not_between":"not between","begins_with":"begins with","not_begins_with":"doesn't begin with","contains":"contains","not_contains":"doesn't contain","ends_with":"ends with","not_ends_with":"doesn't end with","is_empty":"is empty","is_not_empty":"is not empty","is_null":"is null","is_not_null":"is not null"},"errors":{"no_filter":"No filter selected","empty_group":"The group is empty","radio_empty":"No value selected","checkbox_empty":"No value selected","select_empty":"No value selected","string_empty":"Empty value","string_exceed_min_length":"Must contain at least {0} characters","string_exceed_max_length":"Must not contain more than {0} characters","string_invalid_format":"Invalid format ({0})","number_nan":"Not a number","number_not_integer":"Not an integer","number_not_double":"Not a real number","number_exceed_min":"Must be greater than {0}","number_exceed_max":"Must be lower than {0}","number_wrong_step":"Must be a multiple of {0}","number_between_invalid":"Invalid values, {0} is greater than {1}","datetime_empty":"Empty value","datetime_invalid":"Invalid date format ({0})","datetime_exceed_min":"Must be after {0}","datetime_exceed_max":"Must be before {0}","datetime_between_invalid":"Invalid values, {0} is greater than {1}","boolean_not_valid":"Not a boolean","operator_not_multiple":"Operator \"{1}\" cannot accept multiple values"},"invert":"Invert","NOT":"NOT"};QueryBuilder.defaults({lang_code:'en'});return QueryBuilder;}));