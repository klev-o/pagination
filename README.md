Pagination - Simple awesome pagination module
=============================

Pagination with link pager rendering and support for working with mysql and oracle. link pager class enables bootstrapping for basic style. You can set your class and customize for yourself

Installing
------------
1  Add next into `composer.json` `require`
```
"konstantinLev/pagination": "*"
```
2 Add next into `composer.json` `repositories` 
```
{
  "type": "git",
  "url": "https://github.com/KonstantinLev/pagination.git"
}
```
3. Run `composer update`

Configuration
-----
You can use next settings:
- `countOnPage` -  Number of records to display on the page(default: `0`)
- `totalCount` -  Total number of records(default: `0`)
- `className` -  Class name to configure css-properties(default: `''`)
- `leftRightNum` -  Number of elements to the right and left of the active in the link pager(default: `4`)
- `db` -  Database type used in the project(default: `mysql`)
- `controls` -  array Link pager controls - array(default: `['«','»']`)

How to use
-----
```php
$query = 'select * from test ORDER BY id desc';
$countAll = '' // calculate how you will be comfortable
$pag = new Pagination($query, [
    'countOnPage' => 5,
    'totalCount' => $countAll,
    'className' => 'my-class',
    'leftRightNum' => 2,
    'db' => 'oracle',
    'controls' => [
        '<span class="glyphicon glyphicon-arrow-left"></span>',
        '<span class="glyphicon glyphicon-arrow-right"></span>'
    ],
]);
$queryWithPag = $pag->getQuery();
$params = $pag->getParamsForQuery();
// You receive a prepared request with regard to pagination and parameters for it. 
// Next, execute the request by means of the specifics of your project.
$pag->drawLinkPager() // In view start drawing the link of the pager where you need it
```
See [demo](https://github.com/KonstantinLev/pagination/tree/develop/demo) directory.

Methods
-----
- `getQuery(): string` - Getting a prepared query.
- `getParams(): array` - Getting parameters for prepared query execution and drawing link-pager.
- `getParamsForQuery(): array` - Getting parameters only for prepared query execution.
- `drawLinkPager(): string` - Link pager drawing.