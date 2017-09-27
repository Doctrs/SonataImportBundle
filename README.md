# Установка

````sh
composer require doctrs/sonata-import-bundle
````

Добавляем бандл в `AppKernel.php`

````php
new Doctrs\SonataImportBundle\DoctrsSonataImportBundle()
````

Добавляем mapping в файл config.yml

````yaml
doctrine:
    # ...
    orm:
        # ...
        entity_managers:
            default:
                mappings:
                    DoctrsSonataImportBundle: ~
````
и сами настройки бандла
```yaml
doctrs_sonata_import:
    mappings:
        - { name: center_point, class: dcotr.form_format.point}
        - { name: city_autocomplete, class: doctrs.form_format.city_pa}
    upload_dir: %kernel.root_dir%/../web/uploads    
    class_loader: Doctrs\SonataImportBundle\Loaders\CsvFileLoader
    encode:
        default: utf8
        list:
            - cp1251
            - utf8
            - koir8
```

Создаем базу

```
php app/console doctrine:migrations:diff
php app/console doctrine:migrations:migrate
```
Или, если нет миграций
```
php app/console doctrine:schema:update --force
```

Установка бандла завершена

## Изменение сущностей sonataAdminBundle

Добавляем или изменяем метод `configureRoutes` в классах, на основе которых создается sonata admin

```php
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('import', 'import', [
            '_controller' => 'DoctrsSonataImportBundle:Default:index'
        ]);
        $collection->add('upload', '{id}/upload', [
            '_controller' => 'DoctrsSonataImportBundle:Default:upload'
        ]);
    }
```

В каждый класс, который предстоит импортировать, нужно будет изменить метод соответствующим образом.

По данным ссылкам можно переходить либо напрямую в URL, либо добавить ссылки на главную

Для добавления ссылки на главной, можно изменить/добавить метод `getDashboardActions` следующим образом

```php
    public function getDashboardActions()
    {
        $actions = parent::getDashboardActions();

        $actions['import'] = array(
            'label'              => 'Import',
            'url'                => $this->generateUrl('import'),
            'icon'               => 'upload',
            'template'           => 'SonataAdminBundle:CRUD:dashboard__action.html.twig', // optional
        );

        return $actions;
    }
```
Если у вас эти методы не определены в админ классах, то можно просто использовать трейт

```php
use Doctrs\SonataImportBundle\Admin\AdminImportTrait;
...
class EntityAdmin extends AbstractAdmin {
...
use AdminImportTrait;
```
Оба метода описанные выше добавленны в `AdminImportTrait`