<?php
// edit_digitalliteracy_form.php
$string['responsefileoptions'] = 'Response file options';
$string['sourcefiles'] = 'Source file';
$string['responseformat'] = 'Response format';
$string['attachmentsrequired'] = 'Require attachments';
$string['attachmentsrequired_help'] = 'This option specifies the minimum number of attachments required for a response to be considered gradable. [Left for future development]';
$string['acceptedfiletypes'] = 'Accepted file types';
$string['acceptedfiletypes_help'] = 'Accepted file types can be restricted by entering a list of file extensions. If the field is left empty, then all file types are allowed.';
$string['hastemplatefile'] = 'Does the task have a template file?';
$string['responsefiletemplate'] = 'Response file template';
$string['responsegradingoptions'] = 'Grading options';
$string['significance'] = 'Significance:';
$string['excludetemplate'] = 'Exclude template';
$string['template_settings'] = 'Template settings';
$string['template_settings_help'] = 'Exclude template - exclude from graduation all data from template';
// additional placeholders
$string['groupplaceholder'] = 'Default title';
$string['groupplaceholder_help'] = 'Default message';
$string['pattern_help_title'] = 'Help with {$a}';
$string['pattern_help_text'] = '<div class="no-overflow"><p>{$a}</p></div>';
$string['filetype_description'] = '<ul class="list-unstyled unstyled"><li><span class="default_filetype">Excel 2007 spreadsheet </span><small class="text-muted muted">.xlsx</small></li></ul>';
// excel
$string['grouponeparamone_excel'] = 'Value';
$string['grouponeparamtwo_excel'] = 'Calculated value';
$string['grouponeparamthree_excel'] = 'Visibility';
$string['grouponeparamfour_excel'] = 'Merge range';
$string['groupone_help_title_excel'] = 'Compare Text';
$string['groupone_help_text_excel'] = 'Consider value and (or) data type in comparison';
$string['grouptwoparamone_excel'] = 'Bold';
$string['grouptwoparamtwo_excel'] = 'Fill color';
$string['grouptwoparamthree_excel'] = 'Number format';
$string['grouptwoparamfour_excel'] = 'Font';
$string['grouptwo_help_title_excel'] = 'Compare Styles';
$string['grouptwo_help_text_excel'] = 'Consider bold and (or) fill color in comparison';
$string['groupthreeparamone_excel'] = 'Chart type';
$string['groupthreeparamtwo_excel'] = 'Plot values';
$string['groupthreeparamthree_excel'] = 'Axis Y (Legend)';
$string['groupthreeparamfour_excel'] = 'Axis X';
$string['groupthree_help_title_excel'] = 'Compare Charts';
$string['groupthree_help_text_excel'] = 'Consider plot area and (or) legend in comparison';
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
$string['commonsettings_help'] = 'Binary grading - mark is 0 or 1';
// validation
$string['validatecoef'] = 'Enter a float in range [0, 100], please!';
$string['notahundred'] = 'Sum is not a hundred';
$string['tickacheckbox'] = 'Tick one setting at least';
$string['validationerror'] = 'Wrong comparison parameters (it\'s impossible to get max mark)';
$string['emptyfiletypelist'] = 'Filetype list should not be empty';
$string['incorrectfiletypes'] = 'Incorrect file extensions: {$a}';
// file errors
$string['error_noreader'] = 'Can\'t read or accept file {$a->file}. Message: {$a->msg}';
$string['error_incorrectextension'] = 'File with wrong extension: {$a}';
$string['error_filecopy'] = 'Internal error: failed to copy a file {$a} into temporary directory needed for analysis';
$string['error_tooshortfilename'] = 'File \'{$a}\' has a too short name (3 characters excluding extension and a dot are needed)';
$string['error_filenotexist'] = 'Internal error: file \'{$a->name}\' by path \'{$a->path}\' doesn\'t exist';

// questiontype.php
$string['excel'] = 'Excel';
$string['powerpoint'] = 'Power point';

// question.php
$string['answered'] = 'You uploaded: {$a}';
$string['notanswered'] = 'You haven\'t uploaded any file.';
$string['mustrequirefewer'] = 'You cannot require more attachments than you allow.';
$string['insufficientattachments'] = 'Not proper amount of attachments, {$a} required.';
$string['unknownerror'] = 'An unexpected error occurred. Read feedback below.';

// renderer.php
$string['templatefiles'] = 'Template file';
$string['answerfiles'] = 'Answer file';
$string['noattachments'] = 'No attachments';
$string['mistakefiles'] = 'Mistakes file';
$string['nomistakes'] = 'No mistakes';

// known internal errors for phpspreadsheet
$string['error_coordinate_344'] = 'Using all row (column) selection in formulas is prohibited!';
$string['error_worksheet_1262'] = 'Trying to process a too big file!';

$string['pluginname'] = 'Digital Literacy';
$string['pluginname_help'] = 'Autogradable Digital Literacy (Excel, Powerpoint) question.';
$string['pluginnameadding'] = 'Adding an Digital Literacy question';
$string['pluginnameediting'] = 'Editing an Digital Literacy question';
$string['pluginnamesummary'] = 'Allows Spreadsheet and Presentation file uploads, automatically graded!';