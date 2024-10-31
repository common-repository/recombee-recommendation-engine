jQuery(document).ready(function($) {
	'use strict';
	
	var Recombee;
	
	Recombee = {
		select_css_transit	: 500,
		rules_levels		: 0,
		dialog_object		: $('#recombee-query-builder-wrapper'),
		dialog_triggers		: $('.recombee-widget-parameters .widget-tools .qb'),
		query_builder_obj	: $('#recombee-query-builder'),
		queryBuilderPlugins	: [
			'sortable',
			'filter-description',
			'unique-filter',
			'bt-tooltip-errors',
			'bt-selectpicker',
			'bt-checkbox',
			/* 'invert', */
			/* 'not-group' */
		],
		queryBuilderIcons	: {
			add_group: 'far fa-plus-square',
			add_rule: 'fas fa-plus-square',
			remove_group: 'far fa-minus-square',
			remove_rule: 'far fa-minus-square',
			error: 'fas fa-exclamation-triangle'
		},
		dialog_arg			: {
			autoOpen	: false,
			modal		: false,
			dialogClass	: 'recombee-dialog-query-builder',
			appendTo	: 'body',
			show		: {
				effect: "drop",
				direction: 'up',
				duration: 500
			},
			hide		: {
				effect: "drop",
				direction: 'down',
				duration: 300
			},
			beforeClose: function( event, ui ){
			
				var hide_duration = $(this).dialog('instance').options.hide.duration;
				$('#wpwrap').animate({
					'opacity' : '1'
				}, hide_duration, function(){
					$(this).css('opacity', '');
				});
				
			},
			open : function( event, ui ){
				Recombee.dialog_object.trigger('opened');
			},
			close : function( event, ui ){
				var hide_duration = $(this).dialog('instance').options.hide.duration;
				$('#recombee-query-builder').queryBuilder('destroy');
				$( this ).dialog( 'destroy' );
				setTimeout(function(){
					Recombee.dialog_object.trigger('destroyed');
				}, hide_duration );
				
			},
			position: {
				my: 'center top+64',
				at: 'center top', 
				of: window
			},
			maxHeight	: $(window).height() - 64,
			height		: 'auto',
			width		: 'auto',
		},
		
		dialogInit : function(event){
			
			Recombee.dialog_object.data('caller', event.target);
			Recombee.dialog_object.dialog( Recombee.dialog_arg );
			
			/**
			* event's order
			* is IMPORTANT
			*/
			
 			Recombee.query_builder_obj.on('getRuleTemplate.queryBuilder.filter',	Recombee.ruleGetTemplate);
			Recombee.query_builder_obj.on('validateValue.queryBuilder.filter',		Recombee.validateArgs);
			Recombee.query_builder_obj.on('afterUpdateRuleValue.queryBuilder',		Recombee.ruleValueUpdated );
			Recombee.query_builder_obj.on('afterUpdateRuleOperator.queryBuilder',	Recombee.ruleOperatorUpdated );
			Recombee.query_builder_obj.on('getRuleInput.queryBuilder.filter',		Recombee.ruleUpdated );
			Recombee.query_builder_obj.on('afterMove.queryBuilder',					Recombee.ruleUpdated);
			Recombee.query_builder_obj.on('afterMove.queryBuilder',					Recombee.ruleValueUpdated);
			
			/* booster counterfort (limit rules number)*/
			/* Recombee.query_builder_obj.on('beforeAddRule.queryBuilder',				Recombee.rulesLimit); */
			/* booster counterfort */
			
			var curr_interaction_type	= $(event.target).parents('.recombee-widget-parameters').find('select[data-parameter-name="interaction-type"] option:selected').val();
			var appropriate_filter_key	= $(event.target).parents('.recombee-widget-parameters').find('select[data-parameter-name="interaction-type"] option:selected').data('filtersSetKey');
			var _filters				= RecombeeReQBArgs[appropriate_filter_key].slice(0); /* clone array */
			
			/**
			* filter filters to remove excluded
			* functions per interaction type
			*/
			
			for (var q=0; q < _filters.length; q++){
				
				if( _filters[q]['field'] == 'reql_func_lhs' ){
					
					var func_name = _filters[q]['id'];
					
					if( RecombeeReQBArgs.reql_funcs[func_name].data.reference.exclude_for.indexOf(curr_interaction_type) != -1  ){
						_filters.splice(q,1);
					}
				}
			}
			
			$(event.target).data('lastAppropriateFilterKey', curr_interaction_type);
			
			var QB_Args = {
				operators		: RecombeeReQBArgs.operators,
				plugins			: Recombee.queryBuilderPlugins,
				filters			: _filters,
				icons			: Recombee.queryBuilderIcons,
				sort_filters	: function(a, b){
					if (a.optgroup > b.optgroup){
						return 1;
					}
					else if(a.optgroup == b.optgroup){
						if(a.label >= b.label){
							return 1;
						}
						else{
							return -1;
						}
					}
					else{
						return -1;
					}
				},
			};
			
			/* booster counterfort */
			if( $('#' + $(event.target).data('resultReqlGetterId')).data('parameterName') == 'booster' ){
				
				$.extend(QB_Args, {
					/* allow_groups		: 0, */
					/* conditions			: ['IF'], */
					/* default_condition	: 'IF', */
				});
			}
			/* booster counterfort */
			
			Recombee.query_builder_obj.queryBuilder(QB_Args);
			
			try{
				
				var reql	= $('#' + $(event.target).data('resultReqlGetterId')).val();
				var rules	= $('#' + $(event.target).data('resultJsonGetterId')).val();

				if(rules != ''){
					var json_rules = JSON.parse(rules);
					Recombee.query_builder_obj.data('rules', json_rules);
					Recombee.query_builder_obj.queryBuilder('setRules', json_rules);
				}
				else{
					Recombee.query_builder_obj.data('rules', false);
				}
			}
			catch (err) {
				alert(err.message);
				Recombee.query_builder_obj.data('rules', false);
			}
			
			/*
			Recombee.dialog_object.data('caller', event.target);
			Recombee.dialog_object.dialog( Recombee.dialog_arg );
			*/
			Recombee.dialog_object.dialog( 'option',{
				'title'		: $(event.target).data('dialogTitle'),
				'buttons'	: [{
						text: $(event.target).data('clear-btn-text'),
						click	: function(e) {
							
							var JsonGetterId = $(Recombee.dialog_object.data('caller')).data('resultJsonGetterId');
							var ReqlGetterId = $(Recombee.dialog_object.data('caller')).data('resultReqlGetterId');
							
							$('#' + JsonGetterId).val( '' );
							$('#' + ReqlGetterId).val( '' ).trigger('change');
							$( this ).dialog( 'close' );
							Recombee.dialog_object.one('destroyed', function(){
								$('#' + ReqlGetterId).focus();
							});
						},
					},{
						text	: $(event.target).data('generate-btn-text'),
						click	: function(e) {

						var Rules = Recombee.query_builder_obj.queryBuilder('getRules'); 

						if (Rules != null){
							var ReQl;
							var JsonGetterId = $(Recombee.dialog_object.data('caller')).data('resultJsonGetterId');
							var ReqlGetterId = $(Recombee.dialog_object.data('caller')).data('resultReqlGetterId');
							
							Recombee.rules_levels = 0;
							
							Rules.rules = Recombee.reIndexRuleIds(Rules.rules, console.log);
							
							var _Rules	= $.extend(true,{},Rules);
							ReQl		= Recombee.rulesToReql(_Rules.rules, _Rules.condition );
							
							$('#' + JsonGetterId).val( JSON.stringify(Rules) );
							$('#' + ReqlGetterId).val( ReQl ).trigger('change');
							$( this ).dialog( 'close' );
							Recombee.dialog_object.one('destroyed', function(){
								$('#' + ReqlGetterId).focus();
							});
						}
					},
				}],
			});
			
			var widget = $(Recombee.dialog_object).dialog( 'widget' ).css( 'position', 'fixed' );
			widget.draggable( 'option',{
				'scroll'		: false,
				'containment'	: 'window',
			});
		},
		
		openDialog : function(event){
			
			if( typeof(Recombee.dialog_object.dialog('instance')) == 'undefined' ){
				$(event.target).addClass('pulsate');
				setTimeout( function(){
					Recombee.dialogInit(event);
					Recombee.openDialog(event);
				}, 200);
			}
			else{
				if( Recombee.dialog_object.dialog('isOpen') ){
					if( event.target.id != Recombee.dialog_object.data('caller').id ){
						$(event.target).addClass('pulsate');
						setTimeout(function(){
							Recombee.dialog_object.dialog( 'close' );
							Recombee.dialog_object.one('destroyed', function(){
								$(event.target).trigger('click');
							});
						}, 300);
					}
				}
				else{
					$(event.target).addClass('pulsate');
					setTimeout(function(){
						var show_duration = Recombee.dialog_object.dialog('instance').options.show.duration;
						$('#wpwrap').animate({
							'opacity' : '0.6'
						}, show_duration/2, function(){
							Recombee.dialog_object.one('opened', function(){
								$(event.target).removeClass('pulsate');
								return $(this);
							}).dialog( 'open' );
						});
					}, 300);
				}
			}
		},
		
		restartQueryBulider : function(event){
			
			if( typeof(Recombee.dialog_object.dialog('instance')) != 'undefined' ){
				
				var required_filters = $(event.target).find('option:selected').val();
				var previous_filters = $(Recombee.dialog_object.data('caller')).data('lastAppropriateFilterKey');
				
				if(required_filters == previous_filters){
					return;
				}
				
				var caller	 = Recombee.dialog_object.data('caller');
				var JsonGetterId = $(caller).data('resultJsonGetterId');
				var ReqlGetterId = $(caller).data('resultReqlGetterId');
				
				Recombee.dialog_object.dialog( 'close' );
				Recombee.dialog_object.one('destroyed', {'required_filters': required_filters, 'previous_filters': previous_filters}, function(event){
					
					$('#' + ReqlGetterId).data('prevInteractionSet')[event.data.previous_filters] = {'json': $('#' + JsonGetterId).val(),'reql': $('#' + ReqlGetterId).val()};
					
					$('#' + JsonGetterId).val( $('#' + ReqlGetterId).data().prevInteractionSet[event.data.required_filters].json ).trigger('change');
					$('#' + ReqlGetterId).val( $('#' + ReqlGetterId).data().prevInteractionSet[event.data.required_filters].reql ).trigger('change');
					
					$(caller).trigger('click');
			
				});
			}
			else{
				var previous_filters = $(event.target).find('[last-selected="true"]').val();
				var required_filters = $(event.target).find(':selected').val();
				
				if(required_filters == previous_filters){
					return;
				}
				
				var getters = $(event.target).parents('.recombee-widget-parameters').find('.expert-options-wrapper [data-parameter-name="filter"]').siblings('label').find('span span');
				var JsonGetterId = getters.data('resultJsonGetterId');
				var ReqlGetterId = getters.data('resultReqlGetterId');
				
				$('#' + ReqlGetterId).data('prevInteractionSet')[previous_filters] = {'json': $('#' + JsonGetterId).val(),'reql': $('#' + ReqlGetterId).val()};
				
				$('#' + JsonGetterId).val( $('#' + ReqlGetterId).data().prevInteractionSet[required_filters].json );
				$('#' + ReqlGetterId).val( $('#' + ReqlGetterId).data().prevInteractionSet[required_filters].reql );
				
				$(event.target).find(':selected').attr('last-selected', true);
				$(event.target).find('[last-selected="true"]').attr('last-selected', false);
			}
		},
		
		reIndexRuleIds : function (rules, callback){

			for (var i=0; i < rules.length; i++){
	
				if( typeof(rules[i].rules) != 'undefined' ){
					Recombee.reIndexRuleIds(rules[i].rules, callback);
				}
				else if( typeof(rules[i].data) != 'undefined' && rules[i].data != false){
					if( typeof(rules[i].data['lhs']) != 'undefined' ){
						
						if(rules[i].data['lhs'].selected !== false ){
							var new_rule_id = rules[i].data['lhs'].selected.rule_id.replace(/_[\d]+/gm, '_' + Recombee.rules_levels);
							rules[i].data['lhs'].selected.rule_id = new_rule_id;
						}
					}
					if( typeof(rules[i].data['rhs']) != 'undefined' ){

						if(rules[i].data['rhs'].selected !== false ){
							var new_rule_id = rules[i].data['rhs'].selected.rule_id.replace(/_[\d]+/gm, '_' + Recombee.rules_levels);
							rules[i].data['rhs'].selected.rule_id = new_rule_id;
						}
					}
					if( typeof(rules[i].data['lhs']) != 'undefined' || typeof(rules[i].data['rhs']) != 'undefined' ){
						Recombee.rules_levels++;
					}
					
				}
				else{
					Recombee.rules_levels++;
				}
			}
			return rules;
		},
		
		findSavedRuleData : function (rules, key_value, side, callback){
			
			var result = false;
			
			for (var i=0; i < rules.length; i++){
	
				if( typeof(rules[i].rules) != 'undefined' ){
					result = Recombee.findSavedRuleData(rules[i].rules, key_value, side, callback);
				}
				else if(typeof(rules[i].data) != 'undefined' && typeof(rules[i].data[side]) != 'undefined'){
					if( rules[i].data[side].selected !== false && rules[i].data[side].selected.rule_id == key_value ){
			
						result = rules[i].data[side].selected;
						break;
					}
				}
			}
			return result;
		},
		
		validateArgs : function(result, value, rule){
			
			var warning = [];
			var func_right = value.filter(x => Object.keys(RecombeeReQBArgs.reql_funcs).includes(x));
			
			if(rule.filter.field == 'reql_func_lhs'){
				var args_num = RecombeeReQBArgs.reql_funcs[rule.filter.id].data.args.args_num;
				var selected_left = $(rule.$el[0]).find('.rule-reql-lhs-args').get(0).selectize.items;

				if(args_num != selected_left.length){
					warning.push('Not enougth arguments for function ' + rule.filter.label + ' (' + selected_left.length + ' of ' + args_num + ')');
				}
			}
			
			if (func_right.length > 0){
				var selected_right = $(rule.$el[0]).find('.rule-reql-rhs-args').get(0).selectize.items;
				
				for (var i=0; i < func_right.length; i++){
					var args_num = RecombeeReQBArgs.reql_funcs[func_right[i]].data.args.args_num;
					if(args_num != selected_right.length){
						warning.push('Not enougth arguments for function ' + RecombeeReQBArgs.reql_funcs[func_right[i]].label + ' (' + selected_right.length + ' of ' + args_num + ')');
					}
				}
			}
			
			if(warning.length > 0){
				result.value = warning.join('; ');
			}
		},
		
		rulesToReql : function(rules, condition){
			
			var reql = '';
			
			for (var i=0; i < rules.length; i++){
				if( typeof(rules[i].rules) != 'undefined' ){
					reql = reql + '(' + Recombee.rulesToReql(rules[i].rules, rules[i].condition) + ')';
				}
				else{
					var lhs = rules[i].id;
					var rhs = rules[i].value;
					
					if(rhs != null){
						var func_right = rules[i].value.filter(x => Object.keys(RecombeeReQBArgs.reql_funcs).includes(x)); /* array intersection */
						
						/* function selected on the left side */
						if ( typeof(RecombeeReQBArgs.reql_funcs[lhs]) == 'object'){
							if(RecombeeReQBArgs.reql_funcs[lhs].data.args.args_num > 0){
								lhs = Recombee.reqlFuncBuilder(lhs, rules[i].data.lhs.selected);
							}
							else{
								lhs = Recombee.reqlFuncBuilder(lhs, {items: []} );
							}
						}
						/* property selected on the left side */
						else{
							lhs = '\'' + lhs + '\'';
						}
						/* function selected on the right side */
						if ( func_right.length > 0 ){
							
							for (var z = 0; z < func_right.length; z++) {
								var func_index = rhs.indexOf(func_right[z]);
								if(RecombeeReQBArgs.reql_funcs[rhs[func_index]].data.args.args_num > 0){
									rhs[func_index] = Recombee.reqlFuncBuilder(func_right[z], rules[i].data.rhs.selected);
								}
								else{
									rhs[func_index] = Recombee.reqlFuncBuilder(func_right[z], {items: []} );
								}
							}
						}
						/* property selected on the right side */
						else{
							rhs = rhs.map(function (currentValue){
								
								/* function selected on the left side */
								if( typeof(RecombeeReQBArgs.reql_funcs[rules[i].id]) != 'undefined' ){
									
									/* if 'select_only' is true - function result reference to property, not value */
									if(RecombeeReQBArgs.reql_funcs[rules[i].id].data.args.select_only){
										return '\'' + currentValue + '\'';
									}
									/* if 'select_only' is false - function result reference to value, not property */
									else{
										return '"' + currentValue + '"'; 
									}
								}
								else{
									if( rules[i].type == 'boolean' || rules[i].type == 'double' || rules[i].type == 'integer' ){
										return currentValue; 
									}
									else{
										return '"' + currentValue + '"';
									}
								}
							});
						}
					}
					else{
						lhs = '\'' + lhs + '\'';
					}
					reql = reql + lhs + Recombee.equalitySet(rules[i].operator, rhs);
				}
				i < (rules.length - 1) ? reql = reql + ' ' + condition + ' ': '';
			}
			
			/* replace multiple spaces with single space */
			return reql.replace( /\s\s+/g, ' ' );
		},
		
		reqlFuncBuilder : function(field, data){
			
			var args_delimiter	= RecombeeReQBArgs.reql_funcs[field].data.args.args_delim;
			var select_only		= RecombeeReQBArgs.reql_funcs[field].data.args.select_only;
			
			if(!select_only){
				
				var args_arr = data.items.map(function (currentValue) {
					return data.options[currentValue].label;
				});
				
				var args_string = args_arr.join(args_delimiter);
			}
			else{
				var args_string = data.items.join(args_delimiter);
			}
			
			if( RecombeeReQBArgs.reql_funcs[field].data.reference.drop_name){
				field = RecombeeReQBArgs.reql_funcs[field].data.args.wrapper.replace('%args%', args_string);
			}
			else{
				field = field + RecombeeReQBArgs.reql_funcs[field].data.args.wrapper.replace('%args%', args_string);
			}

			return field;
		},
		
		equalitySet : function(operator, value){
			
			(value == null) ? value = [] : '';
			
			var string = RecombeeReQBArgs.operators_ref[operator].replace('%value%', value.join(','));
			return ' ' + string + ' ';
		},
		
		ruleUpdated : function( value, rule, name ){
			
			/**
			* this is rule update,
			* called by operator change
			*/
			if( typeof(event) != 'undefined' && $(event.target).parents('.rule-filter-container').length === 0){
				return;
			}
			
			/**
			* this is rule update,
			* called by filter change
			*/		
			var saved_rules = Recombee.query_builder_obj.data('rules');
			var l_side_args = rule.$el.first().find('.rule-reql-lhs-args');
			var r_side_args = rule.$el.first().find('.rule-reql-rhs-args');
				
			if(rule.filter.field == 'reql_func_lhs'){
		
				if(saved_rules.rules){
					var l_saved_data = Recombee.findSavedRuleData(saved_rules.rules, rule.id, 'lhs', console.log);
				}
				else{
					var l_saved_data = false;
				}
				
				Recombee.argsOperator(rule, rule.filter.id, l_side_args, 'lhs', l_saved_data);
			}
			else if( l_side_args.hasClass('selectized') ){
				$(l_side_args[0]).val('').get(0).selectize.destroy();
			}
			
			if( r_side_args.hasClass('selectized') ){
				$(r_side_args[0]).val('').get(0).selectize.destroy();
			}
			return value;
		},
		
		rulesLimit : function (event){
			var rulesNow = Recombee.query_builder_obj.find('.rule-container').length;
			
			if($('#' + $(Recombee.dialog_object.data('caller')).data('resultReqlGetterId')).data('parameterName') == 'booster'){
				if(rulesNow >= 1){
					event.preventDefault();
				}
			}
		},
		
		ruleValueUpdated : function(value, rule, previousValue){
			
			$(rule.$el).find('.rule-value-container select').selectpicker('refresh');
			
			if( typeof(rule.value) != 'undefined' ){
				var previousValue 	= previousValue || [];
				var saved_rules		= Recombee.query_builder_obj.data('rules');
				var side_args		= rule.$el.first().find('.rule-reql-rhs-args');
				var func_current	= Recombee.findFuncInValues( rule.value );
				var prop_current	= rule.value.filter(x => !func_current.includes(x));
				var prop_added		= rule.value.filter(x => !Object.values(previousValue).includes(x)); /* difference A->B - user added option */
				var prop_deleted	= Object.values(previousValue).filter(x => !rule.value.includes(x)); /* difference B->A - user removed option */
				
				if(func_current.length > 1){
					var new_rule_val = prop_current.concat(prop_added);
					
					Recombee.query_builder_obj.off('afterUpdateRuleValue.queryBuilder', Recombee.ruleValueUpdated );
						Recombee.query_builder_obj.data('queryBuilder').setRuleInputValue(rule, new_rule_val);
							rule.value = new_rule_val;
								$(rule.$el).find('.rule-value-container select').selectpicker('refresh');
									Recombee.query_builder_obj.on('afterUpdateRuleValue.queryBuilder', Recombee.ruleValueUpdated );
				}
				
				if(prop_added.length > 0){
					var operate_func = Recombee.findFuncInValues(prop_added);
					
					if(operate_func.length > 0){
						if(saved_rules.rules){
							var saved_data = Recombee.findSavedRuleData(saved_rules.rules, rule.id, 'rhs', console.log);
						}
						else{
							var saved_data = false;
						}
						
						Recombee.argsOperator(rule, operate_func[0], side_args, 'rhs', saved_data);
					}
				}
				else if(prop_deleted.length > 0 && func_current.length === 0){
				
					if( side_args.hasClass('selectized') ){
						$(side_args[0]).val('').get(0).selectize.destroy();
						$('body .selectize-dropdown').remove();
					}
				}
				else{
					return;
				}
			}
		},
		
		ruleOperatorUpdated : function(value, rule, previousOperator){
			
			$(rule.$el).find('.rule-value-container select').selectpicker('refresh');
			var side_args		= rule.$el.first().find('.rule-reql-rhs-args');
			
			if(rule.operator.nb_inputs === 0){
				
				if( side_args.hasClass('selectized') ){
					$(side_args[0]).val('').get(0).selectize.destroy();
					$('body .selectize-dropdown').remove();
				}
			}
		},
		
		argsOperator : function(rule, func, side_args, slot_name, saved_data){
			
			if( side_args.hasClass('selectized') ){
				$(side_args[0]).val('').get(0).selectize.destroy();
				$('body .selectize-dropdown').remove();
			}			
			var func_data = RecombeeReQBArgs.reql_funcs[func].data.args;
			var selectize_args = {
				dropdownParent	:'body',
				hideSelected	: true,
				placeholder		: func_data.placeholder,
				plugins			: ['remove_button', 'drag_drop'],
				delimiter		: ',',
				onInitialize	: function(){
					if(func_data.select_only){
						this.$control_input.attr('readonly', true);
					};
					this.$wrapper.css('width', 200);
					
					if(Object.keys(this.options).length === 0 && typeof(saved_data.options) != 'undefined'){
						this.options = saved_data.options;
					}
					if(typeof(saved_data.items) != 'undefined' && func == saved_data.filter_id){
						this.setValue(saved_data.items, true);
						this.trigger('change');
					}
				},
				onFocus			: function(){
					(this.items.length === func_data.args_num) ? this.settings.maxItems = func_data.args_num : this.settings.maxItems = null;
				},
				onChange		: function(value){
					(this.items.length === func_data.args_num) ? this.settings.maxItems = func_data.args_num : this.settings.maxItems = null;
					
					var selected = {
						rule_id		: rule.id,
						filter_id	: func,
						items		: this.items,
					};
					if( func_data.select_only === false) {
						selected['options'] = this.options;
					}
					if( typeof(rule['data']) == 'undefined' || rule.data.length === 0){
						rule['data'] = {};
					}
					
					rule['data'][slot_name] = {'selected':selected};
				},
				addPrecedence	: true,
				persist			: true,
				create			: function(input) {
					return {
						value	: Object.keys(this.options).length + 1,
						label	: input
					};
				},
				valueField		: 'value',
				labelField		: 'label',
			}
			if(func_data.options != false){
				selectize_args['options']	 = func_data.options;
			}
			
			side_args.selectize(selectize_args);
		},
		
		ruleGetTemplate : function(template){
			
			/**
			* insert additional containers
			* into builder form
			*/
			var templateHTML = $($.parseHTML(template.value));
			var filter_container = templateHTML.find('.rule-filter-container');
			var value_container  = templateHTML.find('.rule-value-container');
			$('<div class="rule-reql-lhs-args"></div>').insertAfter(filter_container);
			$('<div class="rule-reql-rhs-args"></div>').insertAfter(value_container);
			
			template.value = templateHTML;
			return template;
		},
		
		findFuncInValues : function(values_arr){
			
			var funcs_selected	= values_arr.filter(function(currentValue){
				if( typeof(RecombeeReQBArgs.reql_funcs[currentValue]) != 'undefined' ){
					return true;
				}
				else{
					return false;
				}
			});
			if(funcs_selected.length > 0 ){
				return funcs_selected;
			}
			else{
				return [];
			}
		},
		
		select_focusin : function (e){
			
			var scss = Recombee.select_css_transit;
		
			$(e.target).css('transition', 'all ' + scss + 'ms cubic-bezier(0.71, -0.01, 0.26, 0.98)').css('width', '100%');
			
			$(e.target).data( 'init_height', $(e.target).outerHeight() );
			$(e.target).css('height', $(e.target).data( 'init_height') );
			
			setTimeout(function(){
				$(e.target).css('height', $(e.target).prop('scrollHeight') + 2 );
			},scss/2);
			
			setTimeout(function(){
				$(e.target).css('transition', '' );
			},scss + scss/4);
		},
	
		select_focusout : function select_focusout(e){
			
			var scss = Recombee.select_css_transit;
		
			$(e.target).css('transition', 'all ' + scss + 'ms cubic-bezier(0.71, -0.01, 0.26, 0.98)').css( 'height', $(e.target).data( 'init_height') );
			
			setTimeout(function(){
				$(e.target).css('width', '');
			},scss/4);
			
			setTimeout(function(){
				$(e.target).css('transition',	'' );
				$(e.target).css('height',		'' );
			},scss + scss/4);
		},
		
		unlockQueryBuilder : function(event){
			
			if( $(event.target).prop('readonly') == true ){
				/* Ctrl + A */
				if( event.ctrlKey === true && event.which === 65){
					return;
				}
				/* Ctrl + C */
				if( event.ctrlKey === true && event.which === 67){
					return;
				}
				/* Ctrl */
				if( event.which === 17){
					return;
				}
				if ( confirm($(event.target).data('filtersWriteWarning')) ){
					$('#' + $(event.target).attr('id').replace('reql', 'json')).val('');
					$(event.target).prop('readonly', false).focus();
				};
			}
		},
	}
	$(window).on("load", function(event){
		$('body').on('click', '.recombee-widget-parameters label:not([disabled="disabled"]) .widget-tools .qb', Recombee.openDialog );
		$('body').on('change', '.recombee-widget-parameters select[data-parameter-name="interaction-type"]', Recombee.restartQueryBulider );
		
		$('body').on('focusin',  '.recombee-widget-parameters textarea',	Recombee.select_focusin 	);
		$('body').on('focusout', '.recombee-widget-parameters textarea',	Recombee.select_focusout	);
		
		$('body').on('keyup dblclick',	 '.recombee-widget-parameters textarea',	Recombee.unlockQueryBuilder );
	});
});