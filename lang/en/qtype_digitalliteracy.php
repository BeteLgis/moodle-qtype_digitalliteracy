<?php
$string['pluginname'] = 'Digital Literacy';
$string['pluginname_help'] = 'Autogradable Digital Literacy (Excel, Powerpoint, Word) question.';
$string['pluginnameadding'] = 'Adding a Digital Literacy question';
$string['pluginnameediting'] = 'Editing a Digital Literacy question';
$string['pluginnamesummary'] = 'Provides a platform for automatically gradable Microsoft Excel, PowerPoint or Word questions.';

// base_tester.php
$string['error_fatal'] = 'File {@a->file}, line {@a->line}, message {@a->msg}.';
$string['error_noreader'] = 'Can\'t read or accept file {$a->file}. Message: {$a->msg}.';

// excel_tester.php
$string['errorsandbox_coordinate_394'] = 'Using all row (column) selection in formulas is prohibited!';
$string['errorsandbox_stringhelper_481'] = 'The uploaded file is too big!';
$string['errorsandbox_sheetlimit'] = 'Spreadsheet has no sheets or more than one sheet!';
$string['errorsandbox_zerocells'] = 'Sheet with title {$a->title} has 0 non-empty cells!';

// exception.php
$string['error_unexpected'] = 'Unexpected error: {$a}';

// powerpoint_tester.php


// sandbox.php file errors
$string['error_notesterbase'] = 'File tester_base.php not found.';
$string['error_unknownshell'] = 'Unknown error has occurred in the shell.';
$string['error_nofilespassed'] = 'Uploaded files weren\'t found in a corresponding file area.';
$string['error_filecopy'] = 'Failed to copy a file {$a} into request directory.';
$string['error_filenotexist'] = 'File \'{$a->name}\' by path \'{$a->path}\' doesn\'t exist.';
$string['error_disallowedfiletype'] = 'File {@a} has a wrong type (extension).';
$string['error_tooshortfilename'] = 'File \'{$a}\' has a too short name (at least 3 characters excluding extension and a dot are needed).';
$string['error_insufficientattachments'] = 'Not proper amount of attachments, {$a} required.';

// edit_digitalliteracy_form.php
$string['responseformat'] = 'Response format';
$string['responsefileoptions'] = 'Response file options';
$string['attachmentsrequired'] = 'Require attachments';
$string['attachmentsrequired_help'] = 'This option specifies the minimum number of attachments required for a response to be considered gradable. [Left for future development]';
$string['acceptedfiletypes'] = 'Accepted file types';
$string['acceptedfiletypes_help'] = 'Accepted file types can be restricted by entering a list of file extensions. If the field is left empty, then all file types are allowed.';
$string['sourcefiles'] = 'Source files';
$string['hastemplatefile'] = 'Does the task have a template file?';
$string['excludetemplate'] = 'Exclude template';
$string['templatesettings'] = 'Template settings';
$string['templatesettings_help'] = 'Exclude template - exclude from graduation all data from template';
$string['templatefiles'] = 'Template files';
$string['responsegradingoptions'] = 'Grading options';
// placeholders
$string['significance'] = 'Significance:';
$string['paramplaceholder'] = 'Default param';
$string['groupplaceholder'] = 'Default group';
$string['groupplaceholder_help'] = 'Default message.';
$string['pattern_help_title'] = 'Help with {$a}';
$string['pattern_help_text'] = '<div class="no-overflow"><p>{$a}</p></div>';
$string['filetype_description'] = '<ul class="list-unstyled unstyled"><li><span class="default_filetype">Excel 2007 spreadsheet </span><small class="text-muted muted">.xlsx</small></li></ul>';
// excel
$string['grouponeparamone_excel'] = 'Value';
$string['grouponeparamtwo_excel'] = 'Calculated value';
$string['grouponeparamthree_excel'] = 'Visibility';
$string['grouponeparamfour_excel'] = 'Merge range';
$string['groupone_help_title_excel'] = 'Compare Text';
$string['groupone_help_text_excel'] = 'Consider text specific parameters in comparison. Visibility means filter visibility (checks if cell is hidden by the filter or not).';
$string['grouptwoparamone_excel'] = 'Bold';
$string['grouptwoparamtwo_excel'] = 'Fill color';
$string['grouptwoparamthree_excel'] = 'Number format';
$string['grouptwoparamfour_excel'] = 'Font';
$string['grouptwo_help_title_excel'] = 'Compare Styles';
$string['grouptwo_help_text_excel'] = 'Consider style text specific parameters in comparison. Fill color compares start color (it matters when gradient is used). Font compares: name, size, underline, color and italic.';
$string['groupthreeparamone_excel'] = 'Chart type';
$string['groupthreeparamtwo_excel'] = 'Plot values';
$string['groupthreeparamthree_excel'] = 'Axis Y (Legend)';
$string['groupthreeparamfour_excel'] = 'Axis X';
$string['groupthree_help_title_excel'] = 'Compare Charts';
$string['groupthree_help_text_excel'] = 'Consider charts in comparison. Chart type means barChart, lineChart, pieChart etc.';
// powerpoint
$string['grouponeparamone_powerpoint'] = 'Style';
$string['grouponeparamtwo_powerpoint'] = 'Text';
$string['groupone_help_title_powerpoint'] = 'Compare Text';
$string['groupone_help_text_powerpoint'] = 'Consider style and (or) text in comparison';
$string['grouptwoparamone_powerpoint'] = 'Layouts';
$string['grouptwoparamtwo_powerpoint'] = 'Pictures';
$string['grouptwo_help_title_powerpoint'] = 'Compare Slide Formatting';
$string['grouptwo_help_text_powerpoint'] = 'Consider layouts and (or) pictures in comparison';
$string['groupthreeparamone_powerpoint'] = 'Bullets';
$string['groupthreeparamtwo_powerpoint'] = '???';
$string['groupthree_help_title_powerpoint'] = 'Compare Text formatting';
$string['groupthree_help_text_powerpoint'] = 'Consider bullets and (or) ??? in comparison';
// common
$string['binarygrading'] = 'Binary grading';
$string['showmistakes'] = 'Show mistake files to students';
$string['checkbutton'] = 'Show check button';
$string['commonsettings'] = 'Common settings';
$string['commonsettings_help'] = 'Binary grading - mark is 0 or 1.';
// validation
$string['validatecoef'] = 'Enter a float in range [0, 100]';
$string['notahundred'] = 'Sum is not a hundred';
$string['tickacheckbox'] = 'Tick one param at least';
$string['elementchanged'] = 'The element (its html code) was changed by the user';
$string['emptyfiletypelist'] = 'File types list should not be empty';
$string['incorrectfiletypes'] = 'Incorrect file extensions: {$a}';
$string['validationerror'] = 'Wrong comparison parameters (it\'s impossible to get max mark). Perhaps, source and template files are same and "Exclude template" is checked.';

// lib.php
$string['downloadanswer'] = 'Sorry, you don\'t have the permission to download the correct answer files.';

// question.php
$string['answered'] = 'You uploaded: {$a}.';
$string['notanswered'] = 'You haven\'t uploaded any files.';
$string['unknownerror'] = 'An error occurred during answer check. Read more details below.';

// questiontype.php
$string['excel'] = 'Excel';
$string['powerpoint'] = 'Power point';

// renderer.php
$string['sourcefiles_heading'] = '';
$string['templatefiles_heading'] = 'Template file';
$string['answerfiles_heading'] = 'Answer file';
$string['mistakefiles_heading'] = 'Mistakes file';
$string['nomistakes'] = 'No mistakes';