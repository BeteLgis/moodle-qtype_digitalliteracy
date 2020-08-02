<?php
// edit_digitalliteracy_form.php
$string['responsefileoptions'] = 'Response file options';
$string['sourcefiles'] = 'Source file';
$string['responseformat'] = 'Response format';
$string['attachmentsrequired'] = 'Require attachments';
$string['attachmentsrequired_help'] = 'This option specifies the minimum number of attachments required for a response to be considered gradable.';
$string['acceptedfiletypes'] = 'Accepted file types';
$string['acceptedfiletypes_help'] = 'Accepted file types can be restricted by entering a list of file extensions. If the field is left empty, then all file types are allowed.';
$string['hastemplatefile'] = 'Does the task have a template file?';
$string['responsefiletemplate'] = 'Response file template';
$string['responsegradingoptions'] = 'Grading options';
$string['significance'] = 'Significance:';
// excel
$string['paramvalue_excel'] = 'Value';
$string['paramtype_'] = 'Data type';
$string['coef_value_group'] = 'Compare text';
$string['coef_value_group_help'] = 'Consider value and (or) data type in comparison';
$string['parambold'] = 'Bold';
$string['paramfillcolor'] = 'Fill color';
$string['coef_format_group'] = 'Compare styles';
$string['coef_format_group_help'] = 'Consider bold and (or) fill color in comparison';
$string['paramcharts'] = 'Charts';
$string['paramimages'] = 'Images';
$string['coef_enclosures_group'] = 'Compare enclosures';
$string['coef_enclosures_group_help'] = 'Consider charts and (or) images in comparison';
$string['excludetemplate'] = 'Exclude template';
$string['binarygrading'] = 'Binary grading';
$string['showmistakes'] = 'Show mistake files to students';
$string['checkbutton'] = 'Show check button';
$string['commonsettings'] = 'Common settings';
$string['commonsettings_help'] = 'Exclude template - exclude from graduation all data from template, binary grading - mark is 0 or 1';
$string['validatecoef'] = 'Enter a float in range [0, 100], please!';
$string['notahunred'] = 'Sum is not a hundred';
$string['tickacheckbox'] = 'Tick one setting at least';
$string['validationerror'] = 'Wrong comparison parameters (it\'s impossible to get max mark)';
$string['emptyfiletypelist'] = 'Filetypelist should not be empty';

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

// known internal errors for phpspreadsheet
$string['error_coordinate_344'] = 'Using all row (column) selection in formulas is prohibited!';
$string['error_worksheet_1262'] = 'Trying to process a too big file!';

$string['pluginname'] = 'Digital Literacy';
$string['pluginname_help'] = 'Autogradable Digital Literacy (Excel, Powerpoint) question.';
$string['pluginnameadding'] = 'Adding an Digital Literacy question';
$string['pluginnameediting'] = 'Editing an Digital Literacy question';
$string['pluginnamesummary'] = 'Allows Spreadsheet and Presentation file uploads, automatically graded!';