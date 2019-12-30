# Tiles proxy

Yii2 action for OSM tiles proxy

## Configuration
Add _Proxy_ action to controller:

```php
    public function actions ()
    {
        return [
            'index' => [
                'class' => Proxy::class
            ],
        ];
    }
```

Configuration is stored into params configuration.


```php
'tiles-proxy' => [
            'source-dir' => "@app/web/tiles/:source",
            'default-source' => 'mapnik',
            'sources' => [
                'mapnik' => [
                    'https://a.tile.openstreetmap.org/:z/:x/:y.png',
                    'https://b.tile.openstreetmap.org/:z/:x/:y.png',
                    'https://c.tile.openstreetmap.org/:z/:x/:y.png'
                ],
                'topo' => [
                    'https://a.tile.opentopomap.org/:z/:x/:y.png',
                    'https://b.tile.opentopomap.org/:z/:x/:y.png',
                    'https://c.tile.opentopomap.org/:z/:x/:y.png',
                ],
            ]
        ]
```
