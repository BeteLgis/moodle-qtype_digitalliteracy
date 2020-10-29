<?php
$string['pluginname'] = 'Digital Literacy';
$string['pluginname_help'] = 'Autogradable Digital Literacy (Excel, Powerpoint, Word) question.';
$string['pluginnameadding'] = 'Adding a Digital Literacy question';
$string['pluginnameediting'] = 'Editing a Digital Literacy question';
$string['pluginnamesummary'] = 'Provides a platform for automatically gradable Microsoft Excel, PowerPoint or Word questions.';

// exception.php
$string['exception_shell'] = 'Shell exception';

// excel_tester.php
$string['shellex_coordinate_394'] = 'Using all row (column) selection in formulas is prohibited.';
$string['shellex_stringhelper_481'] = 'The uploaded file is too big in size.';
$string['shellex_xlsx_442'] = $string['shellex_stringhelper_481'];
$string['shellerr_sheetlimit'] = 'Spreadsheet has no sheets or more than one sheet.';
$string['shellerr_zerocells'] = 'Sheet with title {$a->title} has 0 non-empty cells.';

// powerpoint_tester.php


// word_tester.php
$string['shellerr_emtyfile'] = 'Empty file (or only whitespace characters).';

// sandbox.php
$string['exception_noshell'] = 'File \'shell.php\' not found.';
$string['exception_unknownshell'] = 'Unknown exception (empty shell output).';
$string['exception_unexpected'] = 'Unexpected shell exception with code (message): {$a}.';
$string['exception_resultnotarray'] = 'Not an array was returned from the shell.';
$string['exception_emptyresultshell'] = 'Empty (in terms of \'errors\' or \'fraction\' keys) array was returned from the shell.';
$string['exception_nofilespassed'] = 'Uploaded files weren\'t found in a corresponding file area.';
$string['exception_filecopy'] = 'Failed to copy a file {$a} into request directory.';
$string['exception_filenotexist'] = 'File \'{$a->name}\' by path \'{$a->path}\' doesn\'t exist.';
$string['error_unexpected'] = 'Unexpected shell error with code: {$a}.';
$string['error_disallowedfiletype'] = 'File {$a} has a wrong type (extension).';
$string['error_tooshortfilename'] = 'File \'{$a}\' has a too short name (at least 3 characters excluding extension and a dot are needed).';
$string['error_insufficientattachments'] = 'Not proper amount of attachments, {$a} required.';

// shell.php
$string['shellex_corrupteddata'] = 'Couldn\'t decode or unserialize data received by the shell.';
$string['shellex_fatal'] = 'File {$a->file}, line {$a->line}, message {$a->msg}.';
$string['shellex_wrongresponseformat'] = 'Not a registered response format: {$a}.';

// shell_result.php
$string['shellex_resultwrite'] = 'File write error has occurred. Maybe the folder {$a} has a restricted write permissions.';
$string['shellex_prohibitedread'] = 'File has to be read from within Moodle.';
$string['shellex_noresultfile'] = 'The resulting file in the request directory \'{$a}\' wasn\'t created.';
$string['shellex_resultread'] = 'Couldn\'t read and decode (deserialize) the result from file {$a}.';

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
// word
$string['grouponeparamone_word'] = 'Text';
$string['grouponeparamtwo_word'] = 'Calculated value';
$string['groupone_help_title_word'] = 'Compare Text';
$string['groupone_help_text_word'] = 'Consider text specific parameters in comparison.';
$string['grouptwoparamone_word'] = 'Bold';
$string['grouptwoparamtwo_word'] = 'Fill color';
$string['grouptwo_help_title_word'] = 'Compare Styles';
$string['grouptwo_help_text_word'] = 'Consider style text specific parameters in comparison.';
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
$string['word'] = 'Word';

// renderer.php
$string['sourcefiles_heading'] = 'Source file';
$string['templatefiles_heading'] = 'Template file';
$string['answerfiles_heading'] = 'Answer file';
$string['mistakefiles_heading'] = 'Mistakes file';
$string['nomistakes'] = 'No mistakes';