# Installation

Run

````sh
composer require doctrs/sonata-import-bundle
````

Register the bundle in your `AppKernel.php`

````php
new Doctrs\SonataImportBundle\DoctrsSonataImportBundle()
````

If you have not bundle `white-october/pagerfanta-bundle` register this in `AppKernel.php` too.

```php
new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
```

Add orm mapping in your config.yml

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
Add settings of bundle
```yaml
doctrs_sonata_import:
    #mappings:
    #    - { name: center_point, class: doctrs.form_format.point}
    #    - { name: city_autocomplete, class: doctrs.form_format.city_pa}
    upload_dir: %kernel.root_dir%/../web/uploads    
    class_loaders:
        - { name: CSV, class: Doctrs\SonataImportBundle\Loaders\CsvFileLoader}
    #    - { name: XLS, class: AppBundle\Loader\Doctrs\XlsFileLoader}
    encode:
        default: utf8
        list:
            - cp1251
            - utf8
            - koir8
```

Update your database

```
php app/console doctrine:migrations:diff
php app/console doctrine:migrations:migrate
```
If you have not migrations run this
```
php app/console doctrine:schema:update --force
```

Installation of bundle success

## Change sonataAdminBundle entities

Add or change methods `configureRoutes` and `getDashboardActions` in sonata admin classes

```php
use Sonata\AdminBundle\Admin\AbstractAdmin;
...
class AnyClassAdmin extends AbstractAdmin {
...
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('import', 'import', [
            '_controller' => 'DoctrsSonataImportBundle:Default:index'
        ]);
        $collection->add('upload', '{id}/upload', [
            '_controller' => 'DoctrsSonataImportBundle:Default:upload'
        ]);
        $collection->add('importStatus', '{id}/upload/status', [
            '_controller' => 'DoctrsSonataImportBundle:Default:importStatus'
        ]);
    }
    ...
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
If you don't redefined these methods you can use trait

```php
use Doctrs\SonataImportBundle\Admin\AdminImportTrait;
use Sonata\AdminBundle\Admin\AbstractAdmin;
...
class AnyClassAdmin extends AbstractAdmin {
...
use AdminImportTrait;
```
Both methods add in `AdminImportTrait`
