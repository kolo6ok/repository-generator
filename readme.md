## Автогенерирование сервисов репозиториев

1. Выполните команду

    ```
    docker-compose run app php artisan make:repository
    ```

2. Ввод имени таблицы.

    ```
     Enter table name:
     > 
    ```
    
    > Допустимы символы латинского алфавита в нижнем регистре, цифры и знак подчеркивания.

3. Ввод названия модели

    ```
    Enter model name:
    > 
    ```
    
    > Допустимы символы латинского алфавита в любом регистре и цифры. Не может начинаться с цифры.
     
    этот набор символов будет являться префиксов сервиса и репозитория. Регистр первого символа автоматически сменится на верхний.

4. Ввод названия поля. Данная операция будет выполняться до тех пор, пока не будет введено значение "-".

    ```
    Enter name for new field [id]:
    >
    ```

    > Допустимы символы латинского алфавита в любом регистре, цифры и знак подчеркивания. Не может начинаться с цифры.

5. Ввод типа поля.
    ```
    0 - uuid
    1 - increnemts
    2 - bigIncrenemts
    3 - boolean
    4 - tinyInteger
    5 - smallInteger
    6 - integer
    7 - bigInteger
    8 - float
    9 - double
    10 - time
    11 - timestamp
    12 - timestampTz
    13 - string
    14 - text
    15 - mediumText
    16 - longText
    Enter field type [0-16] [0]:
    > 
    ```

    > Допустимы только числа из указанного интервала. При вводе любого другого значения будет использовано значение по умолчанию.

    Дальнейшие шаги могут быть пропущены в зависимости от типа поля.

6. Является ли поле первичным ключом?

    ```
    It is primary key? (yes/no) [no]:
    > 
    ```

    > Допустимы только значения "yes" и "no" (!!!Регистрозависимо!!!)
    
    Вопрос не будет задан для полей типов `text`, `mediumText`, `longText`

7. Является ли поле обязательным
    
    ```
    It is required? (yes/no) [no]:
    > 
    ```
    > Допустимы только значения "yes" и "no" (!!!Регистрозависимо!!!)
    
8. Должны ли быть значения поля уникальными?
 
     ```
     It is unique? (yes/no) [no]:
     > 
     ```
 
     > Допустимы только значения "yes" и "no" (!!!Регистрозависимо!!!)
     
     Вопрос не будет задан для полей типов `text`, `mediumText`, `longText`, но только в том случае, если поле не является первичным ключом и является обязательным.

9. Запрос на сохранение файла. Данный шаг аналогичен всем последующим.
    
    ```
    File path/to/file.php
    ============================================================================
    <?php
    
    /*
     * Some PHP code
     */
    ============================================================================
    Write it? (yes/no) [yes]:

    ```
    
    > Допустимы только значения "yes" и "no" (!!!Регистрозависимо!!!)
    
    На экран будет выведен путь к сохраняемому файлу, а так же листинг этого файла. При выборе варианта "yes" и наличии файла возникнет вопрос о перезаписи существующего файла.

10. После завершения генерации сервиса репозитория необходимо обновить права для новых файлов. Выполните команду
    
    ```
    docker-compose run app /var/www/permissions.sh
