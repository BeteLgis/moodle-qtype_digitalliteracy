<?php
$string['pluginname'] = 'Цифровая Грамотность';
$string['pluginname_help'] = 'Проверка форматирования документов, электронных таблиц и презентаций MS Office.';
$string['pluginnameadding'] = 'Добавить новый вопрос «Цифровая Грамотность»';
$string['pluginnameediting'] = 'Редактирование вопроса «Цифровая Грамотность»';
$string['pluginnamesummary'] = 'Используйте данный тип вопроса для автоматического оценивания практических навыков студентов в рамках курса «Цифровая Грамотность». Оценивание происходит путём сравнения верного ответа с ответом студента.';

// exception.php
$string['exception_shell'] = 'Возникло исключение в песочнице';

// common
$string['shellerr_cantread'] = 'Файл "{$a}" не читается.';

// excel_tester.php
$string['shellex_coordinate_394'] = 'В формулах запрещено выделять строки и столбы полностью [например, =СУММ(F:F)].';
$string['shellex_stringhelper_481'] = 'Загружен слишком большой по размеру файл.';
$string['shellex_xlsx_442'] = $string['shellex_stringhelper_481'];
$string['shellerr_sheetlimit'] = 'В книге (таблице) нет листов или более одного листа (на данный момент поддерживаются только книги с 1 листом).';
$string['shellerr_zerocells'] = 'Лист с именем "{$a->title}" не содержит непустых клеток.';

// powerpoint_tester.php


// word_tester.php
$string['shellerr_emtyfile'] = 'Пустой файл (или файл состоит только из пробельных символов).';

// sandbox.php
$string['exception_noshell'] = 'Файл "shell.php" не найден.';
$string['exception_unknownshell'] = 'Неизвестное исключение (пустой вывод из песочницы).';
$string['exception_unexpected'] = 'Неожиданное исключение возникло в песочнице, код (сообщение): {$a}.';
$string['exception_resultnotarray'] = 'Из песочницы был возвращен не массив.';
$string['exception_emptyresultshell'] = 'Пустой массив был возвращен из песочницы.';
$string['exception_nofilespassed'] = 'Загруженные файлы не были найдены в хранилище (возможно они были удалены некорректно).';
$string['exception_filecopy'] = 'Ошибка копирования файла "{$a}" во временную папку.';
$string['exception_filenotexist'] = 'Файл "{$a->name}" по пути "{$a->path}" не существует.';
$string['error_unexpected'] = 'Неожиданная ошибка возникла в песчонице: {$a}.';
$string['error_disallowedfiletype'] = 'Файл "{$a}" имеет неверное расширение.';
$string['error_tooshortfilename'] = 'Файл "{$a}" имеет слишком короткое имя (нужно хотя бы 3 символа, за исключением точки и расширения).';
$string['error_insufficientattachments'] = 'Неверное количество файлов прикреплено, {$a} необходимо.';

// shell.php
$string['shellex_corrupteddata'] = 'Не получилось расшифровать или десериализировать данные принятие песочницей.';
$string['shellex_fatal'] = 'Файл {$a->file}, линия {$a->line}, сообщение {$a->msg}.';
$string['shellex_wrongresponseformat'] = 'Незарегистрированный тип вопроса: {$a}.';

// shell_result.php
$string['shellex_resultwrite'] = 'Ошибка записи результата.';
$string['shellex_prohibitedread'] = 'Файл нужно читать из Moodle.';
$string['shellex_noresultfile'] = 'Результирующий файл не был создан.';
$string['shellex_resultread'] = 'Не получилось прочитать результат из песчоницы.';

// edit_digitalliteracy_form.php
$string['responseformat'] = 'Формат ответа';
$string['responsefileoptions'] = 'Настройки загружаемых файлов';
$string['attachmentsrequired'] = 'Вложения обязательны';
$string['attachmentsrequired_help'] = 'Этот параметр определяет минимальное количество вложений, необходимых для оценивания ответа.';
$string['acceptedfiletypes'] = 'Разрешенные типы файлов';
$string['acceptedfiletypes_help'] = 'Принимаемые типы файлов могут быть ограничены путем ввода списка их расширений.';
$string['sourcefiles'] = 'Верный ответ';
$string['showtemplatefile'] = 'Показать шаблон студентам';
$string['excludetemplate'] = 'Исключить шаблон из оценивания';
$string['templatesettings'] = 'Настройки шаблона';
$string['templatesettings_help'] = 'Исключить шаблон из оценивания - если в шаблоне, в ответе студента и в верном ответе совпадает какая-то часть информации, то не учитывать её при оценивании (пока что работает только для Excel).';
$string['templatefiles'] = 'Шаблон ответа';
$string['responsegradingoptions'] = 'Настройки оценивания';
$string['fontparams'] = 'Шрифт';
$string['allfontparams'] = 'Выбраны все';
$string['choosefontparams'] = 'Выберите параметры';
$string['fontname'] = 'Название';
$string['fontsize'] = 'Размер';
$string['fontbold'] = 'Полужирный';
$string['fontitalic'] = 'Курсив';
$string['fontunderline'] = 'Подчёркнутый';
$string['fontcolor'] = 'Цвет';
// placeholders
$string['significance'] = 'Значимость:';
$string['paramplaceholder'] = 'Параметр по умолчанию';
$string['groupplaceholder'] = 'Группа по умолчания';
$string['groupplaceholder_help'] = 'Сообщение по умолчанию.';
$string['pattern_help_title'] = 'Справка по использованию элемента "{$a}"';
$string['pattern_help_text'] = '<div class="no-overflow"><p>{$a}</p></div>';
$string['filetype_description'] = '<ul class="list-unstyled unstyled"><li><span class="default_filetype">Excel 2007 spreadsheet </span><small class="text-muted muted">.xlsx</small></li></ul>';
// excel
$string['group1param1_excel'] = 'Значение';
$string['group1param2_excel'] = 'Расчётное значение';
$string['group1param3_excel'] = 'Видимость';
$string['group1param4_excel'] = 'Объединение ячеек';
$string['group1_help_title_excel'] = 'Сравнить текст';
$string['group1_help_text_excel'] = 'Сравнить текст согласно настройкам. Видимость проверяет скрытость фильтрами, объединение - принадлежность конкретному объединению.';
$string['group2param1_excel'] = 'Шрифт';
$string['group2param2_excel'] = 'Цвет заливки';
$string['group2param3_excel'] = 'Числовой формат';
$string['group2param4_excel'] = 'Выравнивание';
$string['group2_help_title_excel'] = 'Сравнить стили';
$string['group2_help_text_excel'] = 'Сравнить стиль согласно настройкам. Цвет заливки сравнивает начальный цвет (важно при использовании градиента).';
$string['group3param1_excel'] = 'Тип диаграммы';
$string['group3param2_excel'] = 'Данные';
$string['group3param3_excel'] = 'Ось Y (легенда)';
$string['group3param4_excel'] = 'Ось X';
$string['group3_help_title_excel'] = 'Сравнить диаграммы';
$string['group3_help_text_excel'] = 'Сравнить диаграммы. Тип диаграммы - гистограмма, график, круговая и т.д.. Сравнение данных происходит по всем измерениям.';
// powerpoint
$string['group1param1_powerpoint'] = 'Стиль';
$string['group1param2_powerpoint'] = 'Текст';
$string['group1_help_title_powerpoint'] = 'Сравнить текст';
$string['group1_help_text_powerpoint'] = 'Сравнить текст согласно настройкам.';
$string['group2param1_powerpoint'] = 'Разметка';
$string['group2param2_powerpoint'] = 'Изображения';
$string['group2_help_title_powerpoint'] = 'Форматирование';
$string['group2_help_text_powerpoint'] = 'Сравнить форматирование';
// word
$string['group1param1_word'] = 'Основной текст';
$string['group1param2_word'] = 'Ссылки';
$string['group1param3_word'] = 'Списки';
$string['group1param4_word'] = 'Таблицы';
$string['group1_help_title_word'] = 'Сравнить текст';
$string['group1_help_text_word'] = 'Сравнить текст для выбранных элементов.';
$string['group2param1_word'] = 'Основной текст';
$string['group2param2_word'] = 'Ссылки';
$string['group2param3_word'] = 'Списки';
$string['group2param4_word'] = 'Таблицы';
$string['group2_help_title_word'] = 'Сравнить стиль';
$string['group2_help_text_word'] = 'Сравнивает стиль (шрифт - полужирный/курсив/подчёркнутый) для выбранных элементов. Для списков, дополнительно, сравнивается тип символа нумерации (буллет, число, буква и т.д.).';
$string['group3param1_word'] = 'Оринтация';
$string['group3param2_word'] = 'Поля';
$string['group3param3_word'] = 'Колонки';
$string['group3_help_title_word'] = 'Сравнить макет';
$string['group3_help_text_word'] = 'Сравнить макет для каждого раздела по выбранным критериям. Поля - все четыре. Колонки - количество и промежуток.';
// common
$string['binarygrading'] = 'Бинарное оценивание';
$string['showmistakes'] = 'Показать файл с ошибками студентам';
$string['checkbutton'] = 'Показать кнопку проверки';
$string['commonsettings'] = 'Общие настройки';
$string['commonsettings_help'] = 'Бинарное оценивание - оценка 0 или 1 (сравнение не прерывается, файл с ошибками создаётся полный).';
// validation
$string['validatecoef'] = 'Введите целое число в промежутке [0, 100]';
$string['notahundred'] = 'Сумма коэффициентов не 100';
$string['tickacheckbox'] = 'Выберите хотя бы одну опцию';
$string['elementchanged'] = 'Код элемента был изменён пользователем вручную';
$string['emptyfiletypelist'] = '"Разрешенные типы файлов" должен быть не пустым';
$string['incorrectfiletypes'] = 'Выбрано недопустимое расширение: {$a}';
$string['validationerror'] = 'Неверные параметры сравнения (макс. балл {$a}). Возможно, вы загрузили одинаковые файл ответа и шаблона, а также включили "Исключение шаблона из оценивания".';

// lib.php
$string['downloadanswer'] = 'Простите, у вас нет доступа к этому действию.';

// question.php
$string['answered'] = 'Вы загрузили: {$a}.';
$string['notanswered'] = 'Вы не загрузили никаких файлов.';
$string['unknownerror'] = 'Возникла ошибка во время проверки. Подробнее читайте ниже.';

// questiontype.php
$string['excel'] = 'Excel';
$string['powerpoint'] = 'PowerPoint';
$string['word'] = 'Word';

// renderer.php
$string['sourcefiles_heading'] = 'Файл ответа';
$string['templatefiles_heading'] = 'Файл-шаблон';
$string['answerfiles_heading'] = 'Ваш ответ';
$string['mistakefiles_heading'] = 'Файл ошибок';
$string['nofiles'] = 'Нет файлов';
$string['nomistakes'] = 'Нет ошибок';