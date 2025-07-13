# Mini‑DI

Легкая версия контейнера внедрения зависимостей, совместамая с [PSR‑11](https://www.php-fig.org/psr/psr-11/).<br>
Реализует регистрацию сервисов по имени, интерфейсу или конкретному классу, автосбор зависимостей через type‑hint‑ы конструктора и два жизненных цикла объектов: `singleton` и `transient`.

## Установка
```bash
composer require mini/di
```

## Быстрый старт
```php
use MiniDI\Container;
use MiniDI\Scope;

$di = new Container();

$di->singleton(Logger::class, FileLogger::class);          // singleton
$di->bind('mailer', Mailer::class, Scope::TRANSIENT);      // alias

$mailer = $di->make('mailer');
$mailer->send('user@example.com', 'Привет из Mini-DI!');
```

## Команды разработчика
| Команда | Действие |
|---------|----------|
| `composer test` | Запуск PHPUnit‑тестов |
## Тесты
Контейнер покрыт unit‑тестами (регистрация, singleton, transient, автосбор, защита от циклов).

---