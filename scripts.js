var highlightedText;
var highlightArray = [];
var o1;

function updateChartAxies(){
 
    if (typeof editor === "undefined"){
        return false;
    }
    
    var sql = editor.getValue();
    $.post(www + '/blocks/bc_dashboard/reporting/sql/ajax', {action: 'parse_sql', sql: sql, ajax: 1}, function(data){
        
        var data = $.parseJSON(data);
        
        // Error
        if (typeof data['error'] !== "undefined"){
            $('#sql_errors').show();
            $('#sql_errors').text(data['error']);
            return false;
        }
        
        $('#sql_errors').hide();
        
        // Get the current values
        var x = $('#xaxis').val();
        var y = $('#yaxis').val();
        

        // x-axis
        $('#xaxis').selectpicker('destroy');
        $('#xaxis').html('');
        $.each(data, function(k, i){
            var sel = (x !== null && x.indexOf(i) >= 0) ? 'selected' : '';
            $('#xaxis').append('<option value="'+i+'" '+sel+' >'+i+'</option>');
        });
        $('#xaxis').selectpicker();
        
        // y-axis
        $('#yaxis').selectpicker('destroy');
        $('#yaxis').html('<option></option>');
        $.each(data, function(k, i){
            var sel = (y == i) ? 'selected' : '';
            $('#yaxis').append('<option value="'+i+'" '+sel+'>'+i+'</option>');
        });
        $('#yaxis').selectpicker();
        
    });
        
}
    

function updateSQLParams(paramValues){
        
    if (typeof editor === "undefined"){
        return false;
    }    
        
    if (typeof highlightedText === "object"){
        highlightedText.clear();
    }
    
    $('#sql_params').hide();
    $('#sql_params_loader').show();
    
    var sql = editor.getValue();
    $.post(www + '/blocks/bc_dashboard/reporting/sql/ajax', {action: 'count_sql_params', sql: sql, ajax: 1}, function(data){
        
        var data = $.parseJSON(data);
        highlightArray = data;
        
        // Count the number of parameter elements
        var cnt = $('div.sql_param').length;
        
        // Count the number of parameters found in the query
        var num = data.length;
        
        // If the number of parameters doesn't match, wipe what we have and start again
        
        // If we have submitted a query with fewer parameters then we currently have elements for, remove the difference from the elements
        if (num < cnt){
            
            for (var i = cnt; i >= num; i--){
                
                $($('div.sql_param')[i]).remove();
                
            }
            
        }
        
        // Otherwise, if we have submitted a query with more parameters than we have elements, create the new ones needed
        else if (num > cnt){
                        
            for (var i = cnt; i < num; i++){
                
                var disp = i + 1;
                var el = "<div class='sql_param' param='"+i+"'>";
                    
                    el += "<div class='text-center'><input id='param-name-"+i+"' type='text' class='text-center sql_param_name' placeholder='name' name='report_params["+i+"][name]' value='"+str['parameter']+" "+disp+"' /></div>";
                    
                    el += "<div class='col-lg-6'>";
                        el += "<span>"+str['format'] + "</span>";
                        el += "<select id='param-type-"+i+"' class='form-control param-type' param='"+i+"' onchange='changeParamType( $(this) );return false;' name='report_params["+i+"][type]'>";
                           $.each(formats, function(k, v){
                               el += "<option value='"+v+"'>"+v+"</option>";
                           });
                        el += "</select>";
                    el += "</div>";
                    
                    el += "<div class='col-lg-6'>";
                        el += "<span>"+str['default']+"</span>";
                        el += "<input type='text' class='form-control param-default' id='param-default-"+i+"' name='report_params["+i+"][default]' />";
                    el += "</div>";
                    
                    el += "<div id='options_"+i+"' class='col-lg-12' style='display:none;'>";
                        el += "<span>"+str['options']+"</span>";
                        el += "<input type='text' class='form-control param-default' id='param-options-"+i+"' name='report_params["+i+"][options]' placeholder='Option 1,Option 2,Option 3' />";
                    el += "</div>";
                    
                    
                    el += "<br class='clear-both'>";
                    
                el += "</div>";
                                
                $('#sql_params').append(el);
                
            }
            
        }
        
        
        
        // Bind highlight
        $('.sql_param').off('mouseover');
        $('.sql_param').on('mouseover', function(){
            var num = parseInt($(this).attr('param'));
            var pos = highlightArray[num][1];
            highlightParam(pos);
        });
        $('#sql_params').off('mouseleave');
        $('#sql_params').on('mouseleave', function(){
            if (typeof highlightedText === "object"){
                highlightedText.clear();
            }
        });
        
        
        
        // if we supplied values, apply them to the elements
        if (paramValues){
            $.each(paramValues, function(k, o){
                $('#param-name-'+k).val(o.name);
                $('#param-type-'+k).val(o.type).change();
                $('#param-default-'+k).val(o.default);
                $('#param-options-'+k).val(o.options);
            });
        }
        
        if ( $('#sql_params').hasClass('ui-sortable') ){
            $('#sql_params').sortable('destroy');
        }
        
        $('#sql_params').sortable({
            placeholder: "ui-state-highlight",
            update: reNumberParams
        });
        
        $('#sql_params_loader').hide();
        $('#sql_params').show();
        
    });
    
}


function highlightParam(pos){
    
    if (typeof highlightedText === "object"){
        highlightedText.clear();
    }
    
    var ttl = 0;
    var line = 0;

    $('.CodeMirror-line').each( function(){

        var s = (ttl === 0) ? ttl : ttl + 1;
        var l = $(this).text().length;
        ttl += l;

        if (line > 0){
            ttl++;
        }

        if (pos >= s && pos <= ttl){
            var diff = ttl - l;
            var posAdjA = pos - diff;
            var posAdjB = posAdjA + 1;
            highlightedText = editor.markText({line: line, ch: posAdjA}, {line: line, ch: posAdjB}, {css: "background:yellow;font-weight:bold;"});
            editor.setCursor({line: line, ch: posAdjA});
        }

        line++;

    } );
    
}


function changeParamType(el){
    
    // Get the param num
    var num = $(el).attr('param');
    var val = $(el).val();
    
    // Reset the default value
    var def = $('#param-default-'+num);
    def.val('');
    
    
    // Show/Hide options
    if (val === 'select'){
        $('#options_'+num).show();
    } else {
        $('#options_'+num+' input').val('');
        $('#options_'+num).hide(); 
    }
    
    
    // If it's text, remove any classes that might have been added
    if (val === 'text' || val === 'select')
    {
        
        if (def.hasClass('hasDatepicker')){
            def.datepicker('destroy');
        }
        
        def.removeProp('disabled');
                       
    }
    
    // If it's date or datetime, add the datepicker class and instantiate the datepicker
    else if (val === 'date')
    {
        
        if (def.hasClass('hasDatepicker')){
            def.datepicker('destroy');
        }
        
        def.datepicker({
            dateFormat: "dd-mm-yy",
            changeMonth: true,
            changeYear: true
        });
        
        def.removeProp('disabled');
        
    }
    
    else if (val === 'datetime')
    {
        
        if (def.hasClass('hasDatepicker')){
            def.datepicker('destroy');
        }
        
        def.datetimepicker({
            dateFormat: "dd-mm-yy",
            changeMonth: true,
            changeYear: true
        });
        
        def.removeProp('disabled');
        
    }
    
    // otherwise, disable it, as it is not one we can set a default for - most likely a "picker" of some kind - Look at setting a starting point for cat/course picker
    else
    {
        if (!def.hasClass('hasDatepicker')){
            def.datepicker('destroy');
        }
        
        def.prop('disabled', true);
    }
        
}


function reNumberParams(){
 
    // Loop through params
    $('.sql_param').each( function(num, el){
                
        $(this).attr('param', num);
        
        var nm = $($(this).find('.sql_param_name')[0]);
        var regexp = new RegExp('^'+ str['parameter'] + ' \\d+$');
        if (nm.val() === '' || nm.val().match(regexp)){
            $($(this).find('.sql_param_name')[0]).val( str['parameter'] + ' ' + (num+1) );
        }
            
        $($(this).find('.param-type')[0]).attr('name', 'report_params['+num+'][type]');
        $($(this).find('.param-default')[0]).attr('name', 'report_params['+num+'][default]');
        $($(this).find('.param-type')[0]).attr('param', num);

        
    } );
    
}

function submitReport(reportType, type){
 
    // Check all parameters have been filled in  
    var err = false;
    
    if (reportType === 'sql')
    {
        $('.sql_param:input').each( function(){
            if ( $(this).val() == '' ){
                err = true;
                alert( str['filloutparam'] + $(this).attr('param') );
                return false;
            }
        } );
    }
    
    if (err){
        return false;
    }
    
    
    // Loading gif
    $('#errors').html('');
    $('#report_results').html('<img src="'+www+'/blocks/bc_dashboard/resources/pix/loading.gif" alt="loading..." style="width:24px;" />');
    
    var params = $('form#report').serialize();
    
    $.post(www+'/blocks/bc_dashboard/reporting/'+reportType+'/ajax', {action: type, params: params}, function(data){
        
        data = $.parseJSON(data);
                
        // Display any errors
        if (typeof data['errors'] !== 'undefined'){
            displayErrors( data['errors'] );
            $('#report_results').html('');
            return false;
        }
        
        // Download any file
        if (typeof data['download'] !== 'undefined'){
            $('#report_results').html('');
            window.location = www + '/blocks/bc_dashboard/download.php?code='+data['download'];
            return false;
        }
        
        // No results
        if (data['data'].length === 0){
            $('#report_results').html( str['nodata'] );
            return false;
        }
        
        
        // SQL Report
        if (reportType === 'sql'){
        
            // Bar Chart
            if (data['reportsubtype'] == 'chart/bar' || data['reportsubtype'] == 'chart/line' || data['reportsubtype'] == 'chart/area'){

                $('#report_results').html('<div id="report_chart" style="width:100%;"></div>');

                var chartData = [];
                var labels = [];
                var keys = [];
                var colours = ['#20B447', '#2092b4', '#F0412A', '#F0E12A'];

                $.each(data['data'], function(k, row){

                    var rowData = {};

                    // X-Axis - The label along the bottom
                    rowData['X_L'] = row[data['x-axis']];

                    // Y-Axies - The value of each point on the chart
                    var num = 1;
                    $.each(data['y-axis'], function(kY, vY){

                        // If the field has uppercase letters and using Moodle DB, it won't exist in the data, because moodle converts all to lowercase
                        if (typeof row[vY] === "undefined"){
                            vY = vY.toLowerCase();
                        }

                        // Get the data for this column
                        rowData['V_'+num] = row[vY];

                        // X Key
                        if (keys.indexOf('V_'+num) < 0){
                            keys.push('V_'+num);
                        }

                        // Label
                        if (labels.indexOf(vY) < 0){
                            labels.push(vY);
                        }

                        num++;

                    });

                    // Push to chart
                    chartData.push(rowData);

                });

                // Make the chart
                var chart = {
                    element: 'report_chart',
                    data: chartData,
                    xkey: 'X_L',
                    ykeys: keys,
                    labels: labels,
                    hideHover: 'auto',
                    xLabelAngle: 90,
                    xLabelMargin: 1,
                    parseTime: false,
                    barColors: colours,
                    lineColors: colours
                  };

                  generateChart(data['reportsubtype'], chart);
                  return;

            }


            // Standard report
            else
            {

                var output = "";
                output += "<table id='sql_report_results' class='table table-bordered table-hover table-striped'>";

                    output += "<thead><tr>";
                        $.each(data['headers'], function(hk, hv){
                            output += "<th data-sort='"+data['datatypes'][hv]+"'>"+hv+"</th>";
                        });
                    output += "</tr></thead>";

                        $.each(data['data'], function(k, row){
                            output += "<tr>";
                            $.each(data['headers'], function(hk, hv){
                                output += "<td>"+row[hv]+"</td>";
                            });
                            output += "</tr>";
                        });

                output += "</table>";

                $('#report_results').html(output);
                $('#sql_report_results').stupidtable();

            }
        
        }
        
        // Builder report
        else if (reportType === 'builder'){
            
            // Build the table
            var output = "";
            output += "<table id='builder_report_results' class='table table-bordered table-hover'>";

                // Headers
                output += "<thead><tr>";
                    $.each(data['headers'], function(hk, hv){
                        output += "<th>"+hv+"</th>";
                    });
                output += "</tr></thead>";

                // Data
                var headers = data['headers'];
                delete headers[0]; delete headers[1]; // Delete name and no.students headers
                output += recursiveGetData(data['data'], headers, 'cat');

            output += "</table>";
                                    
            $('#report_results').html(output);
            $('#builder_report_results').stupidtable();
            
            // Bind table rows to links
            $('table#builder_report_results tbody tr').click( function(){
                var rowID = $(this).attr('id');
                toggleRow(rowID);
            } );
            
        }
        
    }).fail(function(xhr, txt, err){
        var resp = $(xhr.responseText);
        var err = $(resp).find('.errormessage').parent('div').parent('div');
        $('#report_results').html( err.html() );
    });
    
    return false;
    
}


function recursiveGetData(results, headers, type, parentClass, directParentClass, parentName){
    
    var output = "";
    
    if (typeof parentClass === 'undefined'){
        parentClass = [];
    }
    
    if (typeof directParentClass === 'undefined'){
        directParentClass = '';
    }
    
    if (typeof parentName === 'undefined'){
        parentName = '';
    }
    
    

    $.each(results, function(id, data){
        
        // If type is user, get the data out of the user array element, as we had to convert to array to sort them
        if (type === 'user' || type === 'course'){
            id = data[0];
            data = data[1];
        }
        
        var rowID = type.toUpperCase() + '_' + id;
                        
        // Totals row
        output += "<tr id='"+rowID+"' parents='"+parentClass.join(' ')+"' directparent='"+directParentClass+"' class='ROW_TYPE_"+type.toUpperCase()+"'>";
        

            // Name
            if (type === 'user'){
                
                var rpt = ((parentClass.length - 1) * 3) + 1;
                var name = data['firstname'] + ' ' + data['lastname'] + ' ('+data['username']+')';
                var nbsp = Array(rpt).join("&nbsp;");
                var url = www + ((elbp == 1) ? '/blocks/elbp/view.php?id=' : '/user/profile.php?id=');
                output += "<td>"+nbsp+"<a href='"+url+data['id']+"'>"+name+"</a></td>";
            } else {
                var rpt = (parentClass.length * 3) + 1;
                var name = parentName + data['name'];
                var nbsp = Array(rpt).join("&nbsp;");
                output += "<td>"+nbsp+name+"</td>";
            }

            // Number of students
            var usercnt = (typeof data['usercnt'] !== 'undefined') ? data['usercnt'] : '-';
            output += "<td>"+usercnt+"</td>";
                        
            // Totals
            $.each(headers, function(key, header){
                output += "<td>";
                    var val = (type === 'user') ? data[key] : data['totals'][key];
                    if (typeof val === 'undefined' || val === null){
                        val = '0';
                    }
                    output += val;
                output += "</td>";
            });
                    
        output += "</tr>";
                                
                
        // Users
        if (typeof data['users'] === 'object')
        {
            
            // Sort the users, as they will currently be in the order of their ids after the parseJSON
            var users = [];
            for (var i in data['users']){
                users.push([i, data['users'][i]]);
            }
            
            users.sort( function(a, b){
                
                a = a[1]; b = b[1];
                                
                // Try lastname first
                var x = a['lastname'].localeCompare(b['lastname']);
                
                // If they are equal, do first name
                if (x === 0){
                    x = a['firstname'].localeCompare(b['firstname']);
                }
                
                // If still equal, just do username
                if (x === 0){
                    x = a['username'].localeCompare(b['username']);
                }
                
                return x;
                
            } );
                        
            output += recursiveGetData(users, headers, 'user', parentClass.concat([rowID]), rowID);
            
        }
        
        
        // Courses
        if (typeof data['courses'] === 'object')
        {
            
            // Sort courses
            var courses = [];
            for (var i in data['courses']){
                courses.push([i, data['courses'][i]]);
            }
                        
            courses.sort( function(a, b){
                a = a[1]; b = b[1];
                return a['name'].localeCompare(b['name'], undefined, {numeric: true, sensitivity: 'base'});                
            } );
            
            
            output += recursiveGetData(courses, headers, 'course', parentClass.concat([rowID]), rowID, name + ' / ');
            
        }
        
        // Sub cats
        if (typeof data['cats'] === 'object')
        {
            output += recursiveGetData(data['cats'], headers, 'subcat', parentClass.concat([rowID]), rowID, name + ' / ');
        }
        
    });
    
    return output;
    
}
    
function toggleRow(parent){
                
    // Are there any with this class already visible? In which case, we must be hiding them
    if ( $('table#builder_report_results tr[parents*="'+parent+'"]:visible').length > 0 ){
        $('table#builder_report_results tr[parents*="'+parent+'"]').hide();
    } else {
        
        // Otherwise we must be showing them, so loop through and find direct descendants
        var found = false;
        $('table#builder_report_results tr').each( function(){
            // Is this a direct child of the level clicked?
            if ( $(this).attr('directparent') == parent ){
                $(this).slideDown('slow');
                found = true;
            }        
        } );
        
        // If there were none
        if (!found){
            $('tr#'+parent+':not(.ROW_TYPE_USER) td').effect('shake');
        }
        
    }
    
}
    
function displayErrors(errors){
    
    var output = '<div class="col-lg-12 alert alert-danger"><strong>'+str['error']+'</strong><ul>';
    
    $.each(errors, function(k, err){
        output += '<li>'+err+'</li>';
    });
    
    output += '</ul></div>';
    
    $('#errors').html(output);
    
}


function runFromEdit(el){
    
    return window.confirm(str['runfromedit']);
    
}
    
function toggleNavIcon(icon1, icon2, el){
    
    var i = $($(el).children('i')[0]);
    
    if (icon2.length > 0){
    
        if (i.hasClass(icon1)){
            i.removeClass(icon1);
            i.addClass(icon2);
        } else {
            i.removeClass(icon2);
            i.addClass(icon1);
        }
    
    }
        
}


function generateChart(type, data){
    
    if (type == 'chart/bar'){    
        Morris.Bar(data);
    } else if (type == 'chart/line'){
        Morris.Line(data);
    } else if (type == 'chart/area'){
        Morris.Area(data);
    }
    
}


function showHideChartOptions(val){
    
    if (val == 'chart/bar' || val == 'chart/line' || val == 'chart/area'){
        $('#chart_options').show();
    } else {
        $('#chart_options').hide();
    }
    
}


/**
 * Scan for elements in moodle plugins and install/update/remove where required
 * @returns {undefined}
 */
function scanForElements(){
    
    $('#scan_loading').show();
    $('#table_of_elements tbody').html('');
    
    $.post(www + '/blocks/bc_dashboard/ajax.php', {action: 'scan_elements'}, function(data){
        
        data = $.parseJSON(data);
        
        $.each(data, function(plugin, elements){

            var pluginName = plugin;

            $.each(elements, function(key, val){

                // If the key is numeric, then there is no sub plugin. Otherwise, there is and we need to loop them as well
                if ( $.isNumeric(key) ){
                    
                    var en = (val._enabled == 1) ? 'checked' : '';
                    $('#table_of_elements tbody').append('<tr><td>'+val._name+'</td><td>'+pluginName+'</td><td><input name="elements_enabled['+val._id+']" type="checkbox" '+en+' /></td></tr>');
                    
                } else {
                    
                    $.each(val, function(k, v){
                        var en = (v._enabled == 1) ? 'checked' : '';
                        $('#table_of_elements tbody').append('<tr><td>'+v._name+'</td><td>'+pluginName+' // '+key+'</td><td><input name="elements_enabled['+v._id+']" type="checkbox" '+en+' /></td></tr>');                        
                    });
                    
                }

            });

        });

        $('#scan_loading').hide();
        
    });
    
}

var numFilt = 0;

function addFilter(type, filter){

    if (filter.length == 0){
        return false;
    }
    
    var num = numFilt;

    var row = "";
    row += "<tr id='f_row_"+num+"'>";
        row += "<td><a href='' onclick='$(this).parent().parent().remove();return false;'><img src='"+www+"/blocks/bc_dashboard/pix/remove.png' alt='delete' /></a></td>";
        row += "<td>"+type+"</td>";
        row += "<td>"+filter+" <input type='hidden' name='filters["+type+"]["+num+"][field]' value='"+filter+"' /></td>";
        row += "<td><select name='filters["+type+"]["+num+"][cmp]' class='form-control'>";
            row += "<option value='equals'>equals ==</option>";
            row += "<option value='notequals'>not equals !==</option>";
        row += "</select></td>";
        row += "<td><input type='text' name='filters["+type+"]["+num+"][val]' value='' class='form-control' /></td>";
    row += "</tr>";
 
   $('#selected_filters tbody').append(row);
   
   numFilt++;
    
}

var numEl = 0;

function addElement(id){
    
    if (!$.isNumeric(id) || id < 1){
        return false;
    }
    
    $.post(www + '/blocks/bc_dashboard/ajax.php', {action: 'add_report_element', id: id}, function(data){

        data = $.parseJSON(data);

        var num = numEl;

        var row = "";
        row += "<tr id='row_"+num+"'>";
            row += "<td><a href='' onclick='$(this).parent().parent().remove();return false;'><img src='"+www+"/blocks/bc_dashboard/pix/remove.png' alt='delete' /></a></td>";
            row += "<td>"+data.name+"</td>";
            row += "<td><input type='hidden' name='elements["+num+"][id]' id='element_id_"+num+"' value='"+data.id+"' /><input type='text' name='elements["+num+"][displayname]' value='"+data.name+"' class='form-control form-control-sm' /></td>";
            row += "<td class='element_options'>";
                row += displayElementOptions(data.options, num);
            row += "</td>";
        row += "</tr>";

        $('#builder_report_preview tbody').append(row);
        $('#builder_report_preview tbody tr#row_'+num+' select.selectpicker').selectpicker('refresh');
        
        numEl++;

    });
                   
}

function displayElementOptions(options, num){
    
    var output = "";
    
    if (options)
    {

        // Loop through the array of options
        $.each(options, function(pNum, optionArray){

            // Then get the type and the value from each array
            var type = optionArray[0];
            var label = optionArray[1];
            var opt = optionArray[2];

            opt = convertObjectToArray(opt);
            output += getOptionCode(type, opt, label, pNum, num);

        });
    }
    
    return output;
    
}

function getOptionCode(type, opt, label, pNum, rNum){
    
    var output = "";
    output += "<div class='form-group element_option_"+rNum+"_"+pNum+"'>";
    
    output += "<label>"+label+"</label>";
    
    if (type === 'select' || type === 'multiselect')
    {
        var multi = (type === 'multiselect') ? 'multiple' : '';
        var nm = (type === 'multiselect') ? "elements["+rNum+"][options]["+pNum+"][]" : "elements["+rNum+"][options]["+pNum+"]";
        output += "<select name='"+nm+"' class='selectpicker form-control' rownum='"+rNum+"' param='"+pNum+"' "+multi+" onchange='updateElementOptions(this);return false;' >";
            if (type === 'select'){
                output += "<option value=''></option>";
            }
            $.each(opt, function(k, o){
                output += "<option value='"+o.id+"'>"+o.name+"</option>";
            });
        output += "</select>";
    }
    else if (type == 'text')
    {
        output += "<input type='text' name='elements["+rNum+"][options]["+pNum+"]' class='form-control' rownum='"+rNum+"' param='"+pNum+"' onblur='updateElementOptions(this);return false;' />"                
    }
    
    output += "</div>";
    return output;
    
}

function updateElementOptions(el){
    
    var pNum = $(el).attr('param');
    var rowNum = $(el).attr('rownum');
    var elID = $('#element_id_'+rowNum).val();
    var value = $(el).val();
    
    $.post(www + '/blocks/bc_dashboard/ajax.php', {action: 'refresh_element_options', id: elID, param: pNum, val: value}, function(data){
       
        data = $.parseJSON(data);
        $.each(data, function(param, options){
            
            // Select element
            var sel = $('.element_option_'+rowNum+'_'+param+' select');
            
            // This will only ever update select options, so we can hard-code that
            options = convertObjectToArray(options);
            
            var output = "";
            
            if ( $(sel).prop('multiple') === false ){
                output += "<option value=''></option>";
            }
            
            // Append the options
            $.each(options, function(k, o){
                output += "<option value='"+o.id+"'>"+o.name+"</option>";
            });
            
            // Update the HTML
            $(sel).html(output);
            $(sel).selectpicker('refresh');
                        
        });
        
    });
        
}

/**
 * Convert json object to array with keys so it can be sorted by value
 * @param {type} obj
 * @returns {Array|convertObjectToArray.array}
 */
function convertObjectToArray(obj){
    
    if (typeof obj !== 'object'){
        return obj;
    }
    
    var array = [];
        
    $.each(obj, function(k, v){
        array.push( {id: k, name: v} );
    });
    
    if (array.length > 0){
    
        array = array.sort( function(a, b){
            return a.name.localeCompare(b.name);
        } );    
    
    }
    
    return array;
    
}

/**
 * http://stackoverflow.com/questions/5560248/programmatically-lighten-or-darken-a-hex-color-or-rgb-and-blend-colors
 */
function shadeColor(color, percent) {   
    var f=parseInt(color.slice(1),16),t=percent<0?0:255,p=percent<0?percent*-1:percent,R=f>>16,G=f>>8&0x00FF,B=f&0x0000FF;
    return "#"+(0x1000000+(Math.round((t-R)*p)+R)*0x10000+(Math.round((t-G)*p)+G)*0x100+(Math.round((t-B)*p)+B)).toString(16).slice(1);
}


function loadStudentLink(){
    
    var id = $('#param_element_u').val();
    if (id > 0)
    {
        $('#output').html('<img src="'+www+'/blocks/bc_dashboard/pix/loader.gif" />');
        $.post(www + '/blocks/bc_dashboard/ajax.php', {action: 'load_student_link', id: id}, function(data){
            $('#output').html(data);
            bindDataTables();
        });
    }
    
}
    
function loadCourseLinks(){
    
    var id = $('#param_element_c').val();
    if (id > 0)
    {
        $('#output').html('<img src="'+www+'/blocks/bc_dashboard/pix/loader.gif" />');
        $.post(www + '/blocks/bc_dashboard/ajax.php', {action: 'load_course_links', id: id}, function(data){
            $('#output').html(data);
            bindDataTables();
        });
    }
    
}


function bindDataTables(){
    
    // Student list tables
    var table = $('#student_list').DataTable();

    $('#chkall').on('change', function(){
        $(':checkbox', table.rows().nodes()).prop('checked', this.checked);
    } );

    $('#student_action').on('change', function(){

        if ( $(this).val() != '' ){

            // Make sure some students selected
            if ($(':checkbox:checked', table.rows().nodes()).length > 0){

                // Clear existing hidden inputs
                $('.hidden_stud').remove();

                $('.stud_checkbox:checked', table.rows().nodes()).each( function(){
                    $('#mass_action_form').append( '<input type="hidden" name="students[]" value="'+$(this).val()+'" />' );
                } );

                $('#mass_action_form').submit();

            } 
        }

    });
    
}


function bindings(){
    
    // Nodes
    $('#report-nodes').jstree({
        core: {
            "animation": 200,
            "themes": {"stripes":true}
        },
        "types" : {
            "default" : {
              "icon" : "fa fa-folder-open"
            },
            "file" : {
              "icon" : "fa fa-bar-chart"
            }
          },
        "plugins": ["types"]
    }).bind("select_node.jstree", function (e, data) {
        
        var href = data.node.a_attr.href;
        if (href.length > 1){
            window.location = href;
        }
        
      }) ;
        
    
    // Datepickers
    $('.datepicker').datepicker({
        dateFormat: "dd-mm-yy",
        changeMonth: true,
        changeYear: true
    });
    
    // Datetimepickers
    $('.datetimepicker').datetimepicker({
        dateFormat: "dd-mm-yy",
        changeMonth: true,
        changeYear: true
    });
    
    
    // Course picker autocomplete
    $('.coursepicker').autocomplete({
        source: www + '/blocks/bc_dashboard/ajax.php?action=search_course',
        minLength: 2,
        create: function(){
            var w = $(this).width() + 24; // padding on input
            $('.ui-autocomplete').css('max-width', w+'px');
        },
        search: function(){
            var id = $(this).attr('useID');
            $('#'+id).val('');
        },
        select: function( event, ui ) {
            var id = $(this).attr('useID');
            $('#'+id).val(ui.item.id);
            $('#'+id).attr('course', ui.item.value);
        }
    }).on('blur', function(){
        var id = $(this).attr('useID');
        if ( $('#'+id).val() == ''){
            $(this).val('');
        } else if ( $(this).val() != $('#'+id).attr('course') ){
            $(this).val('');
            $('#'+id).val('');
        }
    });
    
    
    // User picker autocomplete
    $('.userpicker').autocomplete({
        source: www + '/blocks/bc_dashboard/ajax.php?action=search_user',
        minLength: 2,
        create: function(){
            var w = $(this).width() + 24; // padding on input
            $('.ui-autocomplete').css('max-width', w+'px');
        },
        search: function(){
            var id = $(this).attr('useID');
            $('#'+id).val('');
        },
        select: function( event, ui ) {
            var id = $(this).attr('useID');
            $('#'+id).val(ui.item.id);
            $('#'+id).attr('user', ui.item.value);
        }
    }).on('blur', function(){
        var id = $(this).attr('useID');
        if ( $('#'+id).val() == ''){
            $(this).val('');
        } else if ( $(this).val() != $('#'+id).attr('user') ){
            $(this).val('');
            $('#'+id).val('');
        }
    });
    
    
    // Gradetracker qual picker auto complete   
    if (typeof block_gradetracker !== 'undefined'){
        $('.block_gradetracker_qual_picker').autocomplete({
            source: block_gradetracker['quals'],
            minLength: 2,
            create: function(){
                var w = $(this).width() + 24; // padding on input
                $('.ui-autocomplete').css('max-width', w+'px');
            },
            search: function(){
                var id = $(this).attr('useID');
                $('#'+id).val('');
            },
            select: function( event, ui ) {
                var id = $(this).attr('useID');
                $('#'+id).val(ui.item.id);
                $('#'+id).attr('qual', ui.item.value);
            }
        }).on('blur', function(){
            var id = $(this).attr('useID');
            if ( $('#'+id).val() == ''){
                $(this).val('');
            } else if ( $(this).val() != $('#'+id).attr('qual') ){
                $(this).val('');
                $('#'+id).val('');
            }
        });
    }
    
    // Do bindings for student list tables
    bindDataTables();  
    
}


$(document).ready( function(){
    
    bindings();        
    
} );


