## Описание

Компонент редактируемых таблиц для фронта и бекенда MODX на основе 
bootstrap и pdoTools.

Данный компонент задумывается в 2 версиях getTables и getTablesPro.
getTables можно модифицировать и распространять на условиях GNU GENERAL PUBLIC LICENSE,
но на основе getTables запрещено повторять и распровстранять функционал зарезервированный для 
платной версии getTablesPro. На данный момент, это сборка конфига gts из нескольких gts конфигов,
лог записи в базу через getTables, редактор базы и визуальный конфигуратор gts-кода. 

This component is conceived in 2 versions of getTables and getTablesPro.
getTables can be modified and distributed under the terms of the GNU GENERAL PUBLIC LICENSE,
but based on getTables it is forbidden to repeat and redistribute the functionality reserved for
paid version of getTablesPro. At the moment, this is the assembly of the gts config from several gts configs,
database log via getTables, database editor and visual configurator of gts-code.

## Планы и хотелки
1. [x] санация параметров запросов. Для полей псевдокода и json продумать. 
2. [x] Интеграция в админку MODX старт.
4. [ ] getTablesPro 
    - [ ] config админка. 
    - [ ] config megre. 
    - [ ] Тригеры базы. 
    - [ ] лог.
    - [ ] мастер баз, таблиц и форм,
    - [ ] настройки и таблица конфигов, 
6. [ ] фильтры в заголовки таблицы. Избавиться от формы в таблице. Сортировка по столбцам.
8. [ ]  Импорт, экспорт в эксель (Загрузка выписок в БВ). 
10. [ ] копирование,
12. [ ] разнести css и js. Неудачная загрузка чанков. подгрузка css и js как в модкс
14. [ ] Табы, селекты, формы(панели).
16. [ ] Печать. Ссылки actions.

30. action pdoSets (с формой и без). 
32. АПИ для 1С (загрузка транзакций в 1С из БВ. Загрузка счетов в 1С и обратно).
34. Связанные селекты и поля.
36. Шаблон добавления полей.
38. jevix и редактор textarea.
40. Выделение ячеек с подсчетом суммы и среднего. Копи выделений и быстрый фильтр.
42. gtsTree, gtsPanel, gtsForm, getTabs, getSelect
44. Автоматически длинную модалку в столбцы.
46. В настройки лимит по умолчанию.
48. Sort как menuindex. DrapAndDrop.
48. Сохранение фильтров и настроек юзера.
48. Групировка и скрытие столбцов.
48. Суммирующяя строка.
48. Слоеная таблица.